<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            // Fetch pages from API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/pages', [
                'entity_id' => $entityId,
            ]);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to load pages. Please try again.');
            }

            $pagesData = $response->json()['data'] ?? [];
            $featuredPreviewMap = $this->fetchFeaturedImagePreviewMap($request, $pagesData);
            $pagesData = collect($pagesData)->map(function ($page) use ($featuredPreviewMap) {
                $featuredImage = $page['featured_image_uri'] ?? null;
                $page['featured_image_preview_uri'] = $featuredPreviewMap[$featuredImage] ?? $featuredImage;

                return $page;
            })->values()->all();

            // Fetch categories for filter dropdown and category-aware sorting
            $categoriesResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/categories', [
                'entity_id' => $entityId,
            ]);

            $categories = $categoriesResponse->successful() ?
                collect($categoriesResponse->json()['data'] ?? []) : collect([]);

            $categoryNameMap = $categories->mapWithKeys(function ($cat) {
                $catId = is_array($cat) ? ($cat['id'] ?? null) : ($cat->id ?? null);
                $catName = is_array($cat) ? ($cat['category_name'] ?? '') : ($cat->category_name ?? '');

                return $catId ? [$catId => $catName] : [];
            });

            // Filter by search and status if provided
            $search = $request->input('search');
            $status = $request->input('status');
            $category = $request->input('category');
            $sort = $request->input('sort', 'updated_at');
            $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

            $filtered = collect($pagesData)->filter(function ($page) use ($search, $status, $category) {
                $matchSearch = !$search || 
                    str_contains(strtolower($page['page_title']), strtolower($search)) ||
                    str_contains(strtolower($page['page_slug']), strtolower($search));
                
                $matchStatus = !$status || $page['page_status'] === $status;
                $matchCategory = !$category || $page['page_category'] == $category;

                return $matchSearch && $matchStatus && $matchCategory;
            });

            $statusOrder = [
                'Published' => 1,
                'Draft' => 2,
                'Withdrawn' => 3,
            ];

            $sortableColumns = [
                'page_title' => fn ($page) => mb_strtolower((string) ($page['page_title'] ?? '')),
                'page_slug' => fn ($page) => mb_strtolower((string) ($page['page_slug'] ?? '')),
                'page_status' => fn ($page) => $statusOrder[$page['page_status'] ?? ''] ?? 99,
                'updated_at' => fn ($page) => strtotime((string) ($page['updated_at'] ?? '')) ?: 0,
                'page_category' => fn ($page) => mb_strtolower((string) $categoryNameMap->get($page['page_category'] ?? null, '')),
            ];

            if (isset($sortableColumns[$sort])) {
                $filtered = $filtered->sortBy(
                    $sortableColumns[$sort],
                    SORT_NATURAL | SORT_FLAG_CASE,
                    $direction === 'desc'
                )->values();
            }

            // Paginate
            $perPage = 15;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $items = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $pages = new LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('web_curator::pages.index', compact('pages', 'categories'));

        } catch (\Exception $e) {
            \Log::error('Error fetching pages', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to load pages.');
        }
    }

    public function create(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        // Fetch categories and subcategories
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/categories', [
            'entity_id' => $entityId,
        ]);

        $categoriesData = $response->successful() ? ($response->json()['data'] ?? []) : [];
        
        $categories = collect($categoriesData)->map(function ($cat) {
            $cat['subcategories'] = collect($cat['subcategories'] ?? [])->map(fn($sub) => (object) $sub);
            return (object) $cat;
        });

        return view('web_curator::pages.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        $validated = $request->validate([
            'page_title' => 'required|string|max:255',
            'page_slug' => 'nullable|string|max:240',
            'page_excerpt' => 'nullable|string',
            'page_content' => 'required|string',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'page_category' => 'nullable|integer',
            'page_subcategory' => 'nullable|integer',
            'is_menu' => 'nullable|boolean',
            'menu_text' => 'nullable|string|max:100',
            'menu_order' => 'nullable|integer|min:0',
            'page_status' => 'required|in:Draft,Published,Withdrawn',
            'featured_image_uri' => 'nullable|string',
        ]);

        // Convert is_menu checkbox to boolean
        $validated['is_menu'] = $request->has('is_menu') ? true : false;
        
        // Set default menu_order if not provided
        if (!isset($validated['menu_order'])) {
            $validated['menu_order'] = 999;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->post(config('web-api.api_base_url') . '/pages', array_merge($validated, [
            'entity_id' => $entityId,
        ]));

        if ($response->successful()) {
            \Log::info('Page created successfully', ['page_id' => $response->json()['data']['id'] ?? null]);
            return redirect()->route('dashboard.web_curator.pages.index')
                ->with('success', 'Page created successfully.' . 
                    ($validated['is_menu'] ? ' This page will appear in the navigation menu.' : ''));
        }

        \Log::error('Failed to create page', ['response' => $response->body()]);
        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to create page: ' . ($response->json()['message'] ?? 'Unknown error'));
    }

    public function edit(Request $request, $id)
    {
        $entityId = $request->attributes->get('current_role_scope');

        // Fetch the page
        $pagesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/pages', [
            'entity_id' => $entityId,
        ]);

        if (!$pagesResponse->successful()) {
            return redirect()->route('dashboard.web_curator.pages.index')
                ->with('error', 'Failed to load page.');
        }

        $page = collect($pagesResponse->json()['data'] ?? [])
            ->firstWhere('id', $id);

        if (!$page) {
            return redirect()->route('dashboard.web_curator.pages.index')
                ->with('error', 'Page not found.');
        }

        // Fetch categories
        $categoriesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/categories', [
            'entity_id' => $entityId,
        ]);

        $categoriesData = $categoriesResponse->successful() ? ($categoriesResponse->json()['data'] ?? []) : [];
        
        $categories = collect($categoriesData)->map(function ($cat) {
            $cat['subcategories'] = collect($cat['subcategories'] ?? [])->map(fn($sub) => (object) $sub);
            return (object) $cat;
        });

        return view('web_curator::pages.edit', compact('page', 'categories'));
    }

    public function preview(Request $request, $id)
    {
        $entityId = $request->attributes->get('current_role_scope');

        $pagesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/pages', [
            'entity_id' => $entityId,
        ]);

        if (!$pagesResponse->successful()) {
            abort(502, 'Failed to load page preview.');
        }

        $page = collect($pagesResponse->json()['data'] ?? [])
            ->firstWhere('id', $id);

        if (!$page) {
            abort(404, 'Page not found.');
        }

        return view('web_curator::pages.preview', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'page_title' => 'required|string|max:255',
            'page_slug' => 'nullable|string|max:240',
            'page_excerpt' => 'nullable|string',
            'page_content' => 'required|string',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'page_category' => 'nullable|integer',
            'page_subcategory' => 'nullable|integer',
            'is_menu' => 'nullable|boolean',
            'menu_text' => 'nullable|string|max:100',
            'menu_order' => 'nullable|integer|min:0',
            'page_status' => 'required|in:Draft,Published,Withdrawn',
            'featured_image_uri' => 'nullable|string',
        ]);

        // Convert is_menu checkbox to boolean
        $validated['is_menu'] = $request->has('is_menu') ? true : false;
        
        // Set default menu_order if not provided
        if (!isset($validated['menu_order'])) {
            $validated['menu_order'] = 999;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->put(config('web-api.api_base_url') . '/pages/' . $id, $validated);

        if ($response->successful()) {
            \Log::info('Page updated successfully', ['page_id' => $id]);

            $successMessage = ($request->has('quick_save') ? 'Page saved successfully.' : 'Page updated successfully.') .
                ($validated['is_menu'] ? ' This page will appear in the navigation menu.' : '');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'page_id' => $id,
                ]);
            }

            return redirect()->route('dashboard.web_curator.pages.edit', $id)
                ->with('success', $successMessage);
        }

        \Log::error('Failed to update page', ['response' => $response->body()]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update page: ' . ($response->json()['message'] ?? 'Unknown error'),
            ], $response->status() >= 400 ? $response->status() : 422);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to update page: ' . ($response->json()['message'] ?? 'Unknown error'));
    }

    public function destroy(Request $request, $id)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->delete(config('web-api.api_base_url') . '/pages/' . $id);

        if ($response->successful()) {
            return redirect()->route('dashboard.web_curator.pages.index')
                ->with('success', 'Page deleted successfully.');
        }

        return redirect()->back()
            ->with('error', 'Failed to delete page.');
    }

    private function fetchFeaturedImagePreviewMap(Request $request, array $records): array
    {
        $featuredImages = collect($records)
            ->pluck('featured_image_uri')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->unique()
            ->values();

        if ($featuredImages->isEmpty()) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/editor/media/items', [
                'fetch_all' => 1,
                'media_type' => 'image',
            ]);

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json()['data'] ?? [])
                ->mapWithKeys(function ($item) {
                    $fullUrl = $item['full_url'] ?? null;
                    $thumbnailUrl = $item['thumbnail_full_url'] ?? null;

                    return ($fullUrl && $thumbnailUrl) ? [$fullUrl => $thumbnailUrl] : [];
                })
                ->only($featuredImages->all())
                ->all();
        } catch (\Throwable $e) {
            \Log::warning('Failed to build page featured-image thumbnail map', ['exception' => $e]);
            return [];
        }
    }
}
