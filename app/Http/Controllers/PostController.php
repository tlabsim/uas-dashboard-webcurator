<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;

class PostController extends Controller
{
    private const ATTACHMENT_ACCEPT_MAP = [
        'image' => '.jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.tif,.tiff,image/*',
        'video' => '.mp4,.mov,.avi,.mkv,.webm,.m4v,.3gp,video/*',
        'document' => '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.odt,.ods,.odp',
        'compressed' => '.zip,.rar,.7z,.tar,.gz,.tgz',
        'audio' => '.mp3,.wav,.ogg,.m4a,.aac,audio/*',
    ];

    public function index(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            $search = $request->input('search');
            $status = $request->input('status');
            $categoryId = $request->input('category_id');
            $isFeatured = $request->input('is_featured');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sort = $request->input('sort', 'updated_at'); // Default sort by updated_at (recently modified)
            $direction = $request->input('direction', 'desc'); // Sort direction
            $page = $request->input('page', 1); // Current page
            $perPage = 15; // Items per page

            $queryParams = array_filter([
                'entity_id' => $entityId,
                'search' => $search,
                'status' => $status,
                'category_id' => $categoryId,
                'is_featured' => $isFeatured,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'direction' => $direction,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            \Log::info('Posts API Request', ['params' => $queryParams]);

            // Fetch posts from API with filters
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/posts', $queryParams);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to load posts. Please try again.');
            }

            $responseData = $response->json();
            
            // API returns paginated data: {data: {data: [...posts], total, per_page, current_page, etc}}
            $paginationData = $responseData['data'] ?? [];
            $postsData = $paginationData['data'] ?? [];
            $featuredPreviewMap = $this->fetchFeaturedImagePreviewMap($request, $postsData);
            $postsData = collect($postsData)->map(function ($post) use ($featuredPreviewMap) {
                $featuredImage = $post['featured_image_uri'] ?? null;
                $post['featured_image_preview_uri'] = $featuredPreviewMap[$featuredImage] ?? $featuredImage;

                return $post;
            })->values()->all();
            $total = $paginationData['total'] ?? 0;
            $currentPage = $paginationData['current_page'] ?? 1;

            // Fetch categories for filter dropdown
            $categoriesResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/post-categories', [
                'is_active' => 1,
            ]);

            $categories = $categoriesResponse->successful() 
                ? collect($categoriesResponse->json()['data'] ?? [])
                : collect();

            $entities = $this->fetchTaggableEntities($request, $entityId);

            // Create paginator with API data
            $posts = new LengthAwarePaginator(
                collect($postsData),
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('web_curator::posts.index', compact('posts', 'categories', 'entities'));

        } catch (\Exception $e) {
            \Log::error('Error fetching posts', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to load posts.');
        }
    }

    public function create(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        $entities = $this->fetchTaggableEntities($request, $entityId);

        // Fetch active post categories from API
        $categoriesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/post-categories', [
            'is_active' => 1,
        ]);

        $categories = $categoriesResponse->successful() 
            ? collect($categoriesResponse->json()['data'] ?? [])->values() 
            : collect();

        if ($categories->isEmpty()) {
            return redirect()->back()->with('error', 'No post categories available. Please contact administrator.');
        }

        $commonTagsByCategory = $this->fetchCommonTagsByCategory($request, $entityId, $categories);

        return view('web_curator::posts.create', compact('entities', 'categories', 'commonTagsByCategory'));
    }

    public function preview(Request $request, $id)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/posts/' . $id);

        if (!$response->successful()) {
            abort($response->status() === 404 ? 404 : 502, 'Failed to load post preview.');
        }

        $post = $response->json()['data'] ?? null;

        if (!$post) {
            abort(404, 'Post not found.');
        }

        return view('web_curator::posts.preview', compact('post'));
    }

    public function store(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        // Fetch categories and entities for validation error handling
        $categoriesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/post-categories', [
            'is_active' => 1,
        ]);

        $categories = $categoriesResponse->successful() 
            ? ($categoriesResponse->json()['data'] ?? []) 
            : [];

        $entities = $this->fetchTaggableEntities($request, $entityId)->toArray();
        $commonTagsByCategory = $this->fetchCommonTagsByCategory($request, $entityId, $categories);

        // Clean metadata - ensure all values are strings, not arrays
        $metadata = $request->input('metadata', []);
        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                if (is_array($value)) {
                    $metadata[$key] = !empty($value) ? (is_string($value[0] ?? null) ? $value[0] : json_encode($value)) : '';
                }
            }
        }
        
        // Replace metadata in request
        $request->merge(['metadata' => $metadata]);

        try {
            $validated = $request->validate([
            'post_title' => 'required|string|max:255',
            'post_excerpt' => 'nullable|string',
            'post_content' => 'required|string',
            'category_id' => 'required|integer', // Removed exists check - API will validate
            'featured_image_uri' => 'nullable|string',
            'author' => 'nullable|string|max:240',
            'tags' => 'nullable|string|max:1024',
            'post_status' => 'required|in:Draft,Published,Withdrawn',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'tagged_entities' => 'nullable|array',
            'tagged_entities.*' => 'integer',
            'metadata' => 'nullable|array',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return to create page with proper data
            return redirect()
                ->route('dashboard.web_curator.posts.create')
                ->withErrors($e->errors())
                ->withInput()
                ->with('categories', $categories)
                ->with('entities', $entities)
                ->with('commonTagsByCategory', $commonTagsByCategory);
        }

        // Add owner_entity_id to the post data
        $validated['owner_entity_id'] = $entityId;

        // Ensure published_at is null if empty (not empty string)
        if (empty($validated['published_at'])) {
            $validated['published_at'] = null;
        }

        // Rename 'metadata' to 'meta' for API compatibility
        if (isset($validated['metadata'])) {
            $validated['meta'] = $validated['metadata'];
            unset($validated['metadata']);
        }

        // Debug: Log what metadata we're sending
        \Log::info('Metadata being sent to API:', ['meta' => $validated['meta'] ?? 'none']);

        // Extract tagged entities before sending to API
        $taggedEntities = $validated['tagged_entities'] ?? [];
        unset($validated['tagged_entities']);

        \Log::info('Full data being sent to API:', ['data' => $validated]);

        $response = $this->buildMultipartApiRequest($request)
            ->post(config('web-api.api_base_url') . '/posts', $this->buildPostPayload($validated));

        if ($response->successful()) {
            $postId = $response->json()['data']['id'] ?? null;

            $tagSyncResult = $postId
                ? $this->syncTaggedEntities($request, (int) $postId, $taggedEntities)
                : ['errors' => ['Tagged entities could not be synced because the created post ID was missing.']];

            $message = 'Post created successfully.';
            if (!empty($taggedEntities)) {
                $message .= ' Tagged entities have been requested for approval.';
            }
            if (!empty($tagSyncResult['errors'])) {
                $message .= ' Some tagged entities could not be synced.';
            }

            return redirect()->route('dashboard.web_curator.posts.edit', $postId)
                ->with('success', $message);
        }

        // Fetch categories again for the form - convert to array
        $categoriesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/post-categories', [
            'is_active' => 1,
        ]);

        $categories = $categoriesResponse->successful() 
            ? ($categoriesResponse->json()['data'] ?? []) 
            : [];

        $entities = $this->fetchTaggableEntities($request, $entityId)->toArray();

        return back()
            ->withInput()
            ->with('error', 'Failed to create post: ' . ($response->json()['message'] ?? 'Unknown error'))
            ->with('categories', $categories)
            ->with('entities', $entities)
            ->with('commonTagsByCategory', $commonTagsByCategory);
    }

    private function fetchCommonTagsByCategory(Request $request, $entityId, $categories = []): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/posts', [
                'entity_id' => $entityId,
                'per_page' => 100,
                'sort' => 'updated_at',
                'direction' => 'desc',
            ]);

            if (!$response->successful()) {
                return [];
            }

            $posts = data_get($response->json(), 'data.data', []);

            $groupedTagCounts = [];

            foreach ($posts as $post) {
                $categoryId = (string) ($post['category_id'] ?? '');
                if ($categoryId === '') {
                    continue;
                }

                $rawTags = $post['tags'] ?? '';
                if (!$rawTags) {
                    continue;
                }

                foreach (explode(',', $rawTags) as $tag) {
                    $normalized = trim(preg_replace('/\s+/', ' ', $tag));
                    if ($normalized === '') {
                        continue;
                    }

                    $key = mb_strtolower($normalized);

                    if (!isset($groupedTagCounts[$categoryId])) {
                        $groupedTagCounts[$categoryId] = [];
                    }

                    if (!isset($groupedTagCounts[$categoryId][$key])) {
                        $groupedTagCounts[$categoryId][$key] = [
                            'label' => $normalized,
                            'count' => 0,
                        ];
                    }

                    $groupedTagCounts[$categoryId][$key]['count']++;
                }
            }

            $frequentTagsByCategory = collect($groupedTagCounts)
                ->map(function ($tagCounts) {
                    uasort($tagCounts, function ($a, $b) {
                        return $b['count'] <=> $a['count'] ?: strcmp($a['label'], $b['label']);
                    });

                    return collect($tagCounts)
                        ->take(20)
                        ->pluck('label')
                        ->values()
                        ->all();
                })
                ->all();

            return $this->mergePredefinedCategoryTags($frequentTagsByCategory, $categories);
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch category-wise common tags for post create', [
                'entity_id' => $entityId,
                'exception' => $e->getMessage(),
            ]);

            return $this->mergePredefinedCategoryTags([], $categories);
        }
    }

    private function mergePredefinedCategoryTags(array $frequentTagsByCategory, $categories = []): array
    {
        $categories = collect($categories);

        return $categories
            ->mapWithKeys(function ($category) use ($frequentTagsByCategory) {
                $category = is_array($category) ? (object) $category : $category;
                $categoryId = (string) ($category->id ?? '');

                if ($categoryId === '') {
                    return [];
                }

                $frequentTags = $frequentTagsByCategory[$categoryId] ?? [];
                $predefinedTags = $this->getPredefinedTagsForCategory($category);
                $existingKeys = collect($frequentTags)
                    ->mapWithKeys(fn ($tag) => [mb_strtolower($tag) => true]);

                $merged = collect($frequentTags);

                foreach ($predefinedTags as $tag) {
                    $normalizedKey = mb_strtolower($tag);
                    if (!$existingKeys->has($normalizedKey)) {
                        $merged->push($tag);
                        $existingKeys->put($normalizedKey, true);
                    }
                }

                return [$categoryId => $merged->take(20)->values()->all()];
            })
            ->all();
    }

    private function getPredefinedTagsForCategory($category): array
    {
        $name = mb_strtolower(trim((string) ($category->name ?? '')));
        $slug = mb_strtolower(trim((string) ($category->slug ?? '')));
        $haystack = trim($name . ' ' . $slug);

        $tagSets = [
            'notice' => ['Official', 'Announcement', 'Urgent', 'Scholarship', 'Admission'],
            'news' => ['News', 'Update', 'Campus', 'Official', 'Featured'],
            'event' => ['Event', 'Program', 'Registration', 'Schedule', 'Participants'],
            'seminar' => ['Seminar', 'Speaker', 'Session', 'Registration', 'Participants'],
            'workshop' => ['Workshop', 'Training', 'Registration', 'Participants', 'Certificate'],
            'conference' => ['Conference', 'Call for Papers', 'Registration', 'Participants', 'Schedule'],
            'admission' => ['Admission', 'Application', 'Eligibility', 'Deadline', 'Notice'],
            'exam' => ['Exam', 'Routine', 'Result', 'Instruction', 'Notice'],
            'result' => ['Result', 'Published', 'Official', 'Notice'],
            'scholarship' => ['Scholarship', 'Application', 'Eligibility', 'Deadline', 'Notice'],
            'research' => ['Research', 'Publication', 'Grant', 'Project', 'Innovation'],
            'achievement' => ['Achievement', 'Award', 'Recognition', 'Success', 'Featured'],
        ];

        foreach ($tagSets as $keyword => $tags) {
            if (str_contains($haystack, $keyword)) {
                return $tags;
            }
        }

        return [];
    }

    public function edit(Request $request, $id)
    {
        $entityId = $request->attributes->get('current_role_scope');

        // Fetch the post
        $postResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/posts/' . $id);

        if (!$postResponse->successful()) {
            return redirect()->route('dashboard.web_curator.posts.index')
                ->with('error', 'Failed to load post.');
        }

        $post = $postResponse->json()['data'] ?? null;

        if (!$post) {
            return redirect()->route('dashboard.web_curator.posts.index')
                ->with('error', 'Post not found.');
        }

        // Fix: API returns 'metadata_organized' but we need 'organized_metadata'
        if (isset($post['metadata_organized'])) {
            $post['organized_metadata'] = $post['metadata_organized'];
        }

        $entities = $this->fetchTaggableEntities($request, $entityId);

        // Fetch tagged entities for this post
        $taggedEntities = $this->fetchTaggedEntityRecords($request, (int) $id)
            ->map(function ($taggedEntity) {
                return [
                    'id' => (int) ($taggedEntity['entity_id'] ?? 0),
                    'tag_id' => (int) ($taggedEntity['id'] ?? 0),
                    'status' => $taggedEntity['status'] ?? 'Pending',
                    'approved_by' => $taggedEntity['approved_by'] ?? null,
                ];
            })
            ->filter(fn ($taggedEntity) => $taggedEntity['id'] > 0)
            ->values()
            ->all();

        // Fetch active post categories from API
        $categoriesResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/post-categories', [
            'is_active' => 1,
        ]);

        $categories = $categoriesResponse->successful() 
            ? collect($categoriesResponse->json()['data'] ?? []) 
            : collect();

        $commonTagsByCategory = $this->fetchCommonTagsByCategory($request, $entityId, $categories);

        return view('web_curator::posts.edit', compact('post', 'entities', 'taggedEntities', 'categories', 'commonTagsByCategory'));
    }

    public function updateTaggedEntities(Request $request, $id)
    {
        $validated = $request->validate([
            'tagged_entities' => 'nullable|array',
            'tagged_entities.*' => 'integer',
        ]);

        $tagSyncResult = $this->syncTaggedEntities($request, (int) $id, $validated['tagged_entities'] ?? []);

        $message = 'Tagged entities updated successfully.';
        if (!empty($tagSyncResult['errors'])) {
            $message .= ' Some tagged entities could not be synced.';
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => empty($tagSyncResult['errors']),
                'message' => $message,
                'errors' => $tagSyncResult['errors'],
            ], empty($tagSyncResult['errors']) ? 200 : 207);
        }

        return redirect()
            ->route('dashboard.web_curator.posts.edit', $id)
            ->with(!empty($tagSyncResult['errors']) ? 'warning' : 'success', $message);
    }

    public function update(Request $request, $id)
    {
        // Clean metadata - ensure all values are strings, not arrays
        $metadata = $request->input('metadata', []);
        if (is_array($metadata)) {
            $metadata = array_map(function($value) {
                // If value is array, take the first element or convert to JSON
                if (is_array($value)) {
                    return !empty($value) ? (is_string($value[0]) ? $value[0] : json_encode($value)) : '';
                }
                return $value;
            }, $metadata);
        }
        
        // Replace metadata in request
        $request->merge(['metadata' => $metadata]);

        $validated = $request->validate([
            'post_title' => 'required|string|max:255',
            'post_excerpt' => 'nullable|string',
            'post_content' => 'required|string',
            'category_id' => 'required|integer', // Removed exists check - API will validate
            'featured_image_uri' => 'nullable|string',
            'author' => 'nullable|string|max:240',
            'tags' => 'nullable|string|max:1024',
            'post_status' => 'required|in:Draft,Published,Withdrawn',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'tagged_entities' => 'nullable|array',
            'tagged_entities.*' => 'integer',
            'metadata' => 'nullable|array',
            'delete_attachments' => 'nullable|array',
            'delete_attachments.*' => 'integer',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max
        ]);

        // Ensure published_at is null if empty (not empty string)
        if (empty($validated['published_at'])) {
            $validated['published_at'] = null;
        }

        // Rename 'metadata' to 'meta' for API compatibility
        if (isset($validated['metadata'])) {
            $validated['meta'] = $validated['metadata'];
            unset($validated['metadata']);
        }

        // Extract tagged entities
        $newTaggedEntities = $validated['tagged_entities'] ?? [];
        unset($validated['tagged_entities']);

        $response = $this->buildMultipartApiRequest($request)
            ->post(config('web-api.api_base_url') . '/posts/' . $id, array_merge(
                $this->buildPostPayload($validated),
                ['_method' => 'PUT']
            ));

        if ($response->successful()) {
            $tagSyncResult = $this->syncTaggedEntities($request, (int) $id, $newTaggedEntities);
            $message = $request->has('quick_save')
                ? 'Post saved successfully.'
                : 'Post updated successfully.';
            if (!empty($newTaggedEntities)) {
                $message .= ' Tagged entity approvals have been kept or updated.';
            }
            if (!empty($tagSyncResult['errors'])) {
                $message .= ' Some tagged entities could not be synced.';
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => empty($tagSyncResult['errors']),
                    'message' => $message,
                    'errors' => $tagSyncResult['errors'],
                    'post_id' => (int) $id,
                ], empty($tagSyncResult['errors']) ? 200 : 207);
            }

            if ($request->has('quick_save')) {
                return redirect()->route('dashboard.web_curator.posts.edit', $id)
                    ->with('success', $message);
            }

            return redirect()->route('dashboard.web_curator.posts.index')
                ->with('success', $message);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post: ' . ($response->json()['message'] ?? 'Unknown error'),
            ], 422);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to update post: ' . ($response->json()['message'] ?? 'Unknown error'));
    }

    public function destroy(Request $request, $id)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->delete(config('web-api.api_base_url') . '/posts/' . $id);

        if ($response->successful()) {
            return redirect()->route('dashboard.web_curator.posts.index')
                ->with('success', 'Post deleted successfully.');
        }

        return redirect()->back()
            ->with('error', 'Failed to delete post.');
    }

    public function tagged(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            $status = $request->input('status');
            $search = trim((string) $request->input('search', ''));
            $sourceEntityId = (int) $request->input('source_entity_id', 0);
            $dateRange = (string) $request->input('date_range', '3m');
            $entities = $this->fetchTaggableEntities($request, $entityId);

            $response = $this->apiClient($request)->get(config('web-api.api_base_url') . '/post-tagged-entities', [
                'entity_id' => $entityId,
            ]);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to load tagged posts. Please try again.');
            }

            $allTaggedPosts = collect($response->json()['data'] ?? [])
                ->filter(fn ($tag) => !empty($tag['post']));

            $cutoffDate = match ($dateRange) {
                '1m' => Carbon::now()->subMonth(),
                '1y' => Carbon::now()->subYear(),
                'all' => null,
                default => Carbon::now()->subMonths(3),
            };

            if ($cutoffDate) {
                $allTaggedPosts = $allTaggedPosts->filter(function ($tag) use ($cutoffDate) {
                    $createdAt = data_get($tag, 'created_at');

                    if (!$createdAt) {
                        return false;
                    }

                    try {
                        return Carbon::parse($createdAt)->greaterThanOrEqualTo($cutoffDate);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })->values();
            }

            $tagCounts = [
                'All' => $allTaggedPosts->count(),
                'Pending' => $allTaggedPosts->where('status', 'Pending')->count(),
                'Approved' => $allTaggedPosts->where('status', 'Approved')->count(),
                'Denied' => $allTaggedPosts->where('status', 'Denied')->count(),
                'Withdrawn' => $allTaggedPosts->where('status', 'Withdrawn')->count(),
            ];

            $filteredTaggedPosts = $allTaggedPosts
                ->when($status, fn ($collection) => $collection->where('status', $status))
                ->when($sourceEntityId > 0, fn ($collection) => $collection->filter(
                    fn ($tag) => (int) data_get($tag, 'post.owner_entity_id', data_get($tag, 'post.entity.id', 0)) === $sourceEntityId
                ))
                ->when($search !== '', function ($collection) use ($search) {
                    $searchNeedle = mb_strtolower($search);

                    return $collection->filter(function ($tag) use ($searchNeedle) {
                        $haystack = implode(' ', array_filter([
                            data_get($tag, 'post.post_title'),
                            data_get($tag, 'post.post_excerpt'),
                            data_get($tag, 'post.author'),
                            data_get($tag, 'post.tags'),
                            data_get($tag, 'post.post_status'),
                            data_get($tag, 'post.post_category.name'),
                            data_get($tag, 'post.entity.cached_data.full_name'),
                            data_get($tag, 'post.entity.cached_data.name'),
                            data_get($tag, 'post.entity.cached_data.short_name'),
                        ]));

                        return str_contains(mb_strtolower($haystack), $searchNeedle);
                    });
                })
                ->values();

            // Paginate
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $items = $filteredTaggedPosts->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $taggedPosts = new LengthAwarePaginator(
                $items,
                $filteredTaggedPosts->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('web_curator::posts.tagged', compact('taggedPosts', 'tagCounts', 'search', 'entities', 'sourceEntityId', 'dateRange'));

        } catch (\Exception $e) {
            \Log::error('Error fetching tagged posts', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to load tagged posts.');
        }
    }

    public function approveTag(Request $request, $tagId)
    {
        try {
            $taggedEntity = $this->fetchTaggedEntity($request, (int) $tagId);
            if (!$taggedEntity) {
                return redirect()->back()->with('error', 'Tagged post entry not found.');
            }

            if ((int) ($taggedEntity['entity_id'] ?? 0) !== (int) $request->attributes->get('current_role_scope')) {
                return redirect()->back()->with('error', 'You are not allowed to approve this tagged post.');
            }

            $approver = $this->resolveApproverName($request);

            $response = $this->apiClient($request)->put(config('web-api.api_base_url') . '/post-tagged-entities/' . $tagId, [
                'status' => 'Approved',
                'approved_by' => $approver,
            ]);

            if (!$response->successful()) {
                \Log::warning('Failed to approve tagged post', [
                    'tag_id' => (int) $tagId,
                    'entity_id' => $request->attributes->get('current_role_scope'),
                    'approver' => $approver,
                    'status' => $response->status(),
                    'response' => $response->json() ?: $response->body(),
                ]);
                return redirect()->back()->with('error', 'Failed to approve tag. Please try again.');
            }

            return redirect()->back()->with('success', 'Tagged post approved successfully.');

        } catch (\Exception $e) {
            \Log::error('Error approving post tag', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to approve tag.');
        }
    }

    public function denyTag(Request $request, $tagId)
    {
        try {
            $taggedEntity = $this->fetchTaggedEntity($request, (int) $tagId);
            if (!$taggedEntity) {
                return redirect()->back()->with('error', 'Tagged post entry not found.');
            }

            if ((int) ($taggedEntity['entity_id'] ?? 0) !== (int) $request->attributes->get('current_role_scope')) {
                return redirect()->back()->with('error', 'You are not allowed to deny this tagged post.');
            }

            $response = $this->apiClient($request)->put(config('web-api.api_base_url') . '/post-tagged-entities/' . $tagId, [
                'status' => 'Denied',
                'approved_by' => null,
                'is_featured_override' => null,
            ]);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to deny tag. Please try again.');
            }

            return redirect()->back()->with('success', 'Tagged post denied.');

        } catch (\Exception $e) {
            \Log::error('Error denying post tag', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to deny tag.');
        }
    }

    public function withdrawTag(Request $request, $tagId)
    {
        try {
            $taggedEntity = $this->fetchTaggedEntity($request, (int) $tagId);
            if (!$taggedEntity) {
                return redirect()->back()->with('error', 'Tagged post entry not found.');
            }

            if ((int) ($taggedEntity['entity_id'] ?? 0) !== (int) $request->attributes->get('current_role_scope')) {
                return redirect()->back()->with('error', 'You are not allowed to withdraw this approval.');
            }

            $response = $this->apiClient($request)->put(config('web-api.api_base_url') . '/post-tagged-entities/' . $tagId, [
                'status' => 'Withdrawn',
                'approved_by' => null,
                'is_featured_override' => null,
            ]);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to withdraw approval. Please try again.');
            }

            return redirect()->back()->with('success', 'Tagged post approval withdrawn.');

        } catch (\Exception $e) {
            \Log::error('Error withdrawing post tag', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to withdraw approval.');
        }
    }

    public function toggleTaggedFeatured(Request $request, $tagId)
    {
        try {
            $taggedEntity = $this->fetchTaggedEntity($request, (int) $tagId);
            if (!$taggedEntity) {
                return redirect()->back()->with('error', 'Tagged post entry not found.');
            }

            if ((int) ($taggedEntity['entity_id'] ?? 0) !== (int) $request->attributes->get('current_role_scope')) {
                return redirect()->back()->with('error', 'You are not allowed to change featured state for this tagged post.');
            }

            if (($taggedEntity['status'] ?? 'Pending') !== 'Approved') {
                return redirect()->back()->with('error', 'Only approved tagged posts can be featured.');
            }

            $currentlyFeatured = filter_var(
                $taggedEntity['effective_is_featured'] ?? $taggedEntity['is_featured_override'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );

            $response = $this->apiClient($request)->put(config('web-api.api_base_url') . '/post-tagged-entities/' . $tagId, [
                'is_featured_override' => !$currentlyFeatured,
            ]);

            if (!$response->successful()) {
                \Log::warning('Failed to toggle featured state for tagged post', [
                    'tag_id' => (int) $tagId,
                    'entity_id' => $request->attributes->get('current_role_scope'),
                    'status' => $response->status(),
                    'response' => $response->json() ?: $response->body(),
                ]);

                return redirect()->back()->with('error', 'Failed to update featured state.');
            }

            return redirect()->back()->with(
                'success',
                $currentlyFeatured ? 'Tagged post removed from featured list.' : 'Tagged post marked as featured for your entity.'
            );
        } catch (\Exception $e) {
            \Log::error('Error toggling tagged post featured state', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to update featured state.');
        }
    }

    private function apiClient(Request $request): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ]);
    }

    public static function attachmentAcceptTokens(array $allowedTypes = []): string
    {
        $tokens = collect($allowedTypes)
            ->flatMap(function ($type) {
                $mapped = self::ATTACHMENT_ACCEPT_MAP[(string) $type] ?? null;

                if ($mapped) {
                    return explode(',', $mapped);
                }

                return [str_starts_with((string) $type, '.') ? (string) $type : '.' . ltrim((string) $type, '.')];
            })
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values();

        return $tokens->implode(',');
    }

    private function buildMultipartApiRequest(Request $request): PendingRequest
    {
        $client = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ]);

        foreach ((array) $request->file('attachments', []) as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $client = $client->attach(
                'attachments[]',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName(),
                ['Content-Type' => $file->getMimeType() ?: 'application/octet-stream']
            );
        }

        return $client;
    }

    private function buildPostPayload(array $validated): array
    {
        $payload = $validated;
        unset($payload['attachments']);

        if (isset($payload['meta']) && is_array($payload['meta'])) {
            foreach ($payload['meta'] as $key => $value) {
                $payload["meta[{$key}]"] = is_scalar($value) || $value === null ? (string) ($value ?? '') : json_encode($value);
            }
            unset($payload['meta']);
        }

        foreach (['delete_attachments'] as $arrayField) {
            if (!isset($payload[$arrayField]) || !is_array($payload[$arrayField])) {
                continue;
            }

            foreach ($payload[$arrayField] as $index => $value) {
                $payload["{$arrayField}[{$index}]"] = $value;
            }

            unset($payload[$arrayField]);
        }

        foreach ($payload as $key => $value) {
            if (is_bool($value)) {
                $payload[$key] = $value ? '1' : '0';
            } elseif ($value === null) {
                $payload[$key] = '';
            }
        }

        return $payload;
    }

    private function resolveApproverName(Request $request): ?string
    {
        $sessionUser = session('ims_user', []);

        return $sessionUser['display_name']
            ?? $sessionUser['name']
            ?? $sessionUser['email']
            ?? optional($request->user())->name
            ?? optional($request->user())->email
            ?? null;
    }

    private function fetchTaggableEntities(Request $request, int|string|null $entityId)
    {
        $entitiesData = [];
        $page = 1;
        $lastPage = 1;

        do {
            $response = $this->apiClient($request)->get(config('ims.api_base_url') . '/entities', [
                'fetch_all' => 1,
                'per_page' => 100,
                'page' => $page,
                'include' => 'type',
                'sort' => 'name',
            ]);

            if (!$response->successful()) {
                break;
            }

            $payload = $response->json();
            $entitiesData = array_merge($entitiesData, $payload['data'] ?? []);
            $lastPage = (int) data_get($payload, 'meta.last_page', 1);
            $page++;
        } while ($page <= $lastPage);

        return collect($entitiesData)
            ->map(function ($entity) {
                $entity = (object) $entity;

                return (object) [
                    'id' => (int) ($entity->id ?? 0),
                    'entity_name' => (string) ($entity->display_name ?? $entity->full_name ?? $entity->name ?? 'Unknown Entity'),
                    'entity_type' => (string) data_get((array) $entity, 'type.name', $entity->type_name ?? 'Entity'),
                    'display_name' => (string) ($entity->display_name ?? $entity->full_name ?? $entity->name ?? 'Unknown Entity'),
                ];
            })
            ->filter(fn ($entity) => $entity->id > 0 && (string) $entity->id !== (string) $entityId)
            ->unique('id')
            ->sortBy('display_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function fetchTaggedEntityRecords(Request $request, int $postId)
    {
        $response = $this->apiClient($request)->get(config('web-api.api_base_url') . '/post-tagged-entities', [
            'post_id' => $postId,
        ]);

        if (!$response->successful()) {
            return collect();
        }

        return collect($response->json()['data'] ?? []);
    }

    private function fetchTaggedEntity(Request $request, int $tagId): ?array
    {
        $response = $this->apiClient($request)->get(config('web-api.api_base_url') . '/post-tagged-entities/' . $tagId);

        if (!$response->successful()) {
            return null;
        }

        return $response->json()['data'] ?? null;
    }

    private function syncTaggedEntities(Request $request, int $postId, array $selectedEntityIds): array
    {
        $selectedIds = collect($selectedEntityIds)
            ->map(fn ($entityId) => (int) $entityId)
            ->filter(fn ($entityId) => $entityId > 0)
            ->unique()
            ->values();

        $existingTaggedEntities = $this->fetchTaggedEntityRecords($request, $postId)
            ->keyBy(fn ($taggedEntity) => (int) ($taggedEntity['entity_id'] ?? 0));

        $errors = [];
        $created = 0;
        $deleted = 0;

        $selectedIdLookup = $selectedIds->flip();

        $entitiesToDelete = $existingTaggedEntities
            ->reject(fn ($taggedEntity, $entityId) => $selectedIdLookup->has((int) $entityId));

        foreach ($entitiesToDelete as $taggedEntity) {
            $deleteResponse = $this->apiClient($request)->delete(
                config('web-api.api_base_url') . '/post-tagged-entities/' . $taggedEntity['id']
            );

            if ($deleteResponse->successful()) {
                $deleted++;
            } else {
                $errors[] = 'Could not remove tagged entity ID ' . ($taggedEntity['entity_id'] ?? 'unknown') . '.';
            }
        }

        $entitiesToCreate = $selectedIds
            ->reject(fn ($entityId) => $existingTaggedEntities->has($entityId));

        foreach ($entitiesToCreate as $entityId) {
            $createResponse = $this->apiClient($request)->post(config('web-api.api_base_url') . '/post-tagged-entities', [
                'post_id' => $postId,
                'entity_id' => $entityId,
                'status' => 'Pending',
            ]);

            if ($createResponse->successful()) {
                $created++;
            } else {
                $errors[] = 'Could not tag entity ID ' . $entityId . '.';
            }
        }

        return [
            'created' => $created,
            'deleted' => $deleted,
            'errors' => $errors,
        ];
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
            \Log::warning('Failed to build post featured-image thumbnail map', ['exception' => $e]);
            return [];
        }
    }
}
