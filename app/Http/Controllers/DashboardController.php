<?php

// Dashboard Controller for Web Curator
namespace UasDashboard\WebCurator\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    /**
     * Display the dashboard index page.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');
        $imsUser = session('ims_user') ?? ['name' => 'Guest'];

        \Log::debug('Web Curator Dashboard accessed', [
            'user' => $imsUser,
            'entity_id' => $entityId,
            'timestamp' => now(),
        ]);

        // Fetch entity information and statistics
        $entityInfo = null;
        $statistics = [
            'total_pages' => 0,
            'total_posts' => 0,
            'total_categories' => 0,
            'total_snippets' => 0,
            'total_media' => 0,
            'image_media' => 0,
            'video_media' => 0,
            'total_galleries' => 0,
            'published_galleries' => 0,
            'draft_galleries' => 0,
            'published_posts' => 0,
            'draft_posts' => 0,
        ];
        $recentPosts = [];
        $recentGalleries = [];
        $postsByCategory = [];
        $categories = [];

        try {
            $headers = [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ];

            // Get entity profile
            $response = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/entity/profile', [
                    'entity_id' => $entityId,
                ]);

            if ($response->successful()) {
                $entityInfo = $response->json()['data'] ?? null;
            }

            // Get all pages
            $pagesResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/editor/pages', [
                    'entity_id' => $entityId,
                ]);

            if ($pagesResponse->successful()) {
                $statistics['total_pages'] = count($pagesResponse->json()['data'] ?? []);
            }

            // Get all posts with detailed information
            // Add query parameters for better data retrieval
            $postsResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/editor/posts', [
                    'entity_id' => $entityId,
                    'per_page' => 100,  // Get more posts for statistics
                    'sort' => 'updated_at',
                    'direction' => 'desc',
                ]);

            if ($postsResponse->successful()) {
                $responseData = $postsResponse->json();
                
                // Extract posts from nested structure (Laravel pagination)
                $paginationData = $responseData['data'] ?? [];
                $allPosts = $paginationData['data'] ?? [];
                
                // Use total from pagination for accurate count
                $statistics['total_posts'] = $paginationData['total'] ?? count($allPosts);
                
                // Count by status (post_status can be 'Draft', 'Published', 'Withdrawn')
                $statistics['published_posts'] = collect($allPosts)->where('post_status', 'Published')->count();
                $statistics['draft_posts'] = collect($allPosts)->where('post_status', 'Draft')->count();
                
                // Get recent posts (already sorted by updated_at desc, just take first 5)
                $recentPosts = collect($allPosts)
                    ->take(5)
                    ->values()
                    ->toArray();
                
                // Group posts by category
                $postsByCategory = collect($allPosts)
                    ->groupBy('category_id')
                    ->map(function ($posts) {
                        return count($posts);
                    })
                    ->toArray();
            }

            // Get post categories
            $categoriesResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/post-categories', [
                    'entity_id' => $entityId,
                    'is_active' => 1,
                ]);

            if ($categoriesResponse->successful()) {
                $categories = $categoriesResponse->json()['data'] ?? [];
                $statistics['total_categories'] = count($categories);
            }

            // Get snippets
            $snippetsResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/editor/snippets', [
                    'entity_id' => $entityId,
                ]);

            if ($snippetsResponse->successful()) {
                $statistics['total_snippets'] = count($snippetsResponse->json()['data'] ?? []);
            }

            $mediaResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/editor/media/items', [
                    'fetch_all' => 1,
                ]);

            if ($mediaResponse->successful()) {
                $mediaItems = collect($mediaResponse->json()['data'] ?? []);
                $statistics['total_media'] = $mediaItems->count();
                $statistics['image_media'] = $mediaItems->where('media_type', 'image')->count();
                $statistics['video_media'] = $mediaItems->where('media_type', 'video')->count();
            }

            $galleriesResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/editor/galleries', [
                    'per_page' => 4,
                ]);

            if ($galleriesResponse->successful()) {
                $galleryPayload = $galleriesResponse->json()['data'] ?? [];
                $recentGalleries = $galleryPayload['data'] ?? [];
            }

            $allGalleriesResponse = Http::withHeaders($headers)
                ->get(config('web-api.api_base_url') . '/editor/galleries', [
                    'fetch_all' => 1,
                ]);

            if ($allGalleriesResponse->successful()) {
                $allGalleries = collect($allGalleriesResponse->json()['data'] ?? []);
                $statistics['total_galleries'] = $allGalleries->count();
                $statistics['published_galleries'] = $allGalleries->where('gallery_status', 'Published')->count();
                $statistics['draft_galleries'] = $allGalleries->where('gallery_status', 'Draft')->count();
            }

        } catch (\Exception $e) {
            \Log::error('Error fetching dashboard data', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
        }

        return view('web_curator::index', [
            'imsUser' => $imsUser,
            'entityInfo' => $entityInfo,
            'statistics' => $statistics,
            'recentPosts' => $recentPosts,
            'recentGalleries' => $recentGalleries,
            'postsByCategory' => $postsByCategory,
            'categories' => $categories,
        ]);
    }

    // Add more methods for handling menus, pages, posts, etc.
}
