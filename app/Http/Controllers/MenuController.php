<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Support\DummyData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope', null);

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {

            // Make a request to the API to get the categories for the entity
            $requestUrl = config('web-api.api_base_url') . '/categories';
            $response = \Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get($requestUrl, [
                'entity_id' => $entityId,
            ]);

            if (!$response->successful()) {
                \Log::error('Failed to fetch categories from API', [
                    'status' => $response->status(),
                    'url' => $requestUrl,
                    'entity_id' => $entityId,
                ]);
                return redirect()->back()->with('error', 'Failed to load categories. Please try again later.');
            }

            // If the response is successful, decode the JSON data
            $categoriesData = $response->json();
            //convert the categories data to a collection
            $categoriesArray = $categoriesData['data'] ?? [];

            // dump($categoriesArray);

            $categoriesCollection = collect($categoriesArray)->map(function ($cat) {
                $cat['subcategories'] = collect($cat['subcategories'] ?? [])->map(function ($sub) {
                    return (object) $sub;
                });
                return (object) $cat;
            });

            // Load categories for this entity
            $categories = $categoriesCollection->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'category_name' => $cat->category_name,
                    'category_slug' => $cat->category_slug,
                    'is_menu' => $cat->is_menu,
                    'menu_text' => $cat->menu_text,
                    'menu_order' => $cat->menu_order,
                    'link_url' => $cat->link_url,
                    'subcategories' => $cat->subcategories->sortBy('menu_order')->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'subcategory_name' => $sub->subcategory_name,
                            'subcategory_slug' => $sub->subcategory_slug,
                            'is_menu' => $sub->is_menu,
                            'menu_text' => $sub->menu_text,
                            'menu_order' => $sub->menu_order,
                            'link_url' => $sub->link_url,
                        ];
                    })->values(),
                ];
            });

            // Fetch static pages that are marked as menu items
            $pagesRequestUrl = config('web-api.api_base_url') . '/pages';
            $pagesResponse = \Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get($pagesRequestUrl, [
                'entity_id' => $entityId,
            ]);

            $staticPages = [];
            if ($pagesResponse->successful()) {
                $pagesData = $pagesResponse->json();
                $allPages = $pagesData['data'] ?? [];
                
                // Filter only pages that are menu items and published
                $staticPages = collect($allPages)
                    ->filter(function ($page) {
                        return ($page['is_menu'] ?? false) && ($page['page_status'] ?? '') === 'Published';
                    })
                    ->sortBy('menu_order')
                    ->map(function ($page) {
                        return [
                            'id' => $page['id'],
                            'page_title' => $page['page_title'],
                            'page_slug' => $page['page_slug'],
                            'menu_text' => $page['menu_text'] ?? $page['page_title'],
                            'menu_order' => $page['menu_order'] ?? 999,
                            'page_category' => $page['page_category'] ?? null,
                            'page_subcategory' => $page['page_subcategory'] ?? null,
                            'edit_url' => route('dashboard.web_curator.pages.edit', $page['id']),
                        ];
                    })
                    ->values()
                    ->toArray();
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching categories', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'entity_id' => $entityId,
            ]);
            return redirect()->route('dashboard.home')->with('error', 'Failed to load categories. Please try again later.');
        }

        return view('web_curator::menu_manager.index', [
            'categories' => $categories,
            'static_pages' => $staticPages,
            'entity_id' => $entityId,
        ]);
    }

    public function update(Request $request)
    {
        $entityId = $request->input('entity_id');
        $categories = $request->input('categories', []);

        try {
            $postUrl = config('web-api.api_base_url') . '/categories-all';
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])
                ->post($postUrl, [
                    'entity_id' => $entityId,
                    'categories' => $categories,
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Ensure the response has a proper success message
                if (!isset($responseData['message'])) {
                    $responseData['message'] = 'Menu and categories updated successfully!';
                }
                
                return response()->json($responseData);
            } else {
                $errorData = $response->json();
                \Log::error('Failed to update menus via API', [
                    'status' => $response->status(),
                    'response' => $errorData,
                    'entity_id' => $entityId,
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => $errorData['message'] ?? 'Failed to update menus. Please try again.',
                    'errors' => $errorData['errors'] ?? null,
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error('Exception while updating menus', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the menu. Please try again.',
            ], 500);
        }
    }
}
