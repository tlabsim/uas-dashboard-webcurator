<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use UasDashboard\WebCurator\Concerns\InteractsWithWebApi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    use InteractsWithWebApi;

    public function index(Request $request)
    {
        $context = $this->entityContext($request);
        $entityId = $context['entity_id'];

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            $foldersResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/media/folders'));
            $galleriesResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/galleries'), [
                'fetch_all' => 1,
            ]);

            if (!$foldersResponse->successful() || !$galleriesResponse->successful()) {
                if ($this->wantsJson($request)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to load media workspace.',
                    ], 502);
                }

                return redirect()->back()->with('error', 'Failed to load media workspace.');
            }

            $folderPayload = $this->responseData($foldersResponse, []);
            $baseFolderTree = $this->annotateFolderTreeCounts(collect($folderPayload['tree'] ?? []));
            $folderTree = $this->sortFolderTree(
                $baseFolderTree,
                $request->input('folder_sort', 'name')
            );
            $foldersFlat = $this->withFolderDepths(collect($folderPayload['flat'] ?? []), $folderTree);
            $foldersFlatNatural = $this->withFolderDepths(collect($folderPayload['flat'] ?? []), $baseFolderTree);
            $tab = in_array($request->input('tab'), ['folders', 'galleries'], true)
                ? $request->input('tab')
                : 'folders';
            $galleries = $this->sortGalleries(
                collect($this->responseData($galleriesResponse, [])),
                $request->input('gallery_sort', 'name')
            );

            $currentFolder = $foldersFlat->firstWhere('id', (int) $request->input('folder_id'));
            $activeGallery = null;

            if ($tab === 'galleries' && $galleries->isNotEmpty()) {
                $requestedGalleryId = (int) $request->input('gallery_id');
                $activeGallery = $requestedGalleryId > 0
                    ? $galleries->firstWhere('id', $requestedGalleryId)
                    : $galleries->first();
            }

            if ($tab === 'galleries') {
                $mediaItems = $activeGallery
                    ? $this->fetchGalleryMediaItems($request, (int) data_get($activeGallery, 'id'))
                    : collect();
            } else {
                $mediaItems = $this->fetchFolderMediaItems($request, $currentFolder);
            }
            $libraryMediaItems = $this->fetchAllMediaItems($request);
            $typeStats = $this->fetchTypeStats($request);
            $galleryCount = $this->fetchGalleryCount($request);
            $payload = [
                'context' => $context,
                'folderTree' => $folderTree,
                'foldersFlat' => $foldersFlat,
                'foldersFlatNatural' => $foldersFlatNatural,
                'mediaItems' => $mediaItems,
                'libraryMediaItems' => $libraryMediaItems,
                'galleries' => $galleries,
                'activeGallery' => $activeGallery,
                'currentFolder' => $currentFolder,
                'typeStats' => $typeStats,
                'galleryCount' => $galleryCount,
                'filters' => [
                    'tab' => $tab,
                    'folder_id' => $request->input('folder_id'),
                    'folder_sort' => $request->input('folder_sort', 'name'),
                    'gallery_id' => $activeGallery['id'] ?? null,
                    'gallery_sort' => $request->input('gallery_sort', 'name'),
                    'media_sort' => $request->input('media_sort', 'modified'),
                    'media_type' => $request->input('media_type', ''),
                    'search' => $request->input('search', ''),
                    'unused_only' => $request->boolean('unused_only'),
                    'used_in_galleries_only' => $request->boolean('used_in_galleries_only'),
                    'thumb' => in_array($request->input('thumb'), ['sm', 'md', 'lg'], true) ? $request->input('thumb') : 'md',
                ],
            ];

            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'data' => $payload,
                ]);
            }

            return view('web_curator::media.index', $payload);
        } catch (\Throwable $e) {
            \Log::error('Failed to load web curator media workspace', ['exception' => $e]);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load media workspace.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to load media workspace.');
        }
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'folder_name' => 'required|string|max:150',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'slug' => 'nullable|string|max:180',
        ]);

        $response = $this->webApiClient($request)->post($this->webApiUrl('editor/media/folders'), $validated);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Folder created successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->back()->with('success', 'Folder created successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to create folder.'),
                'errors' => $this->flattenErrors($response, 'Failed to create folder.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()
            ->withErrors($this->flattenErrors($response, 'Failed to create folder.'))
            ->withInput();
    }

    public function updateFolder(Request $request, int $id)
    {
        $validated = $request->validate([
            'folder_name' => 'required|string|max:150',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'slug' => 'nullable|string|max:180',
        ]);

        $response = $this->webApiClient($request)->put($this->webApiUrl("editor/media/folders/{$id}"), $validated);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Folder updated successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->back()->with('success', 'Folder updated successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to update folder.'),
                'errors' => $this->flattenErrors($response, 'Failed to update folder.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()
            ->withErrors($this->flattenErrors($response, 'Failed to update folder.'))
            ->withInput();
    }

    public function destroyFolder(Request $request, int $id)
    {
        $payload = $request->validate([
            'content_strategy' => 'nullable|in:keep,delete',
        ]);

        $response = $this->webApiClient($request)->delete($this->webApiUrl("editor/media/folders/{$id}"), $payload);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Folder deleted successfully.',
                ]);
            }

            return redirect()->back()->with('success', 'Folder deleted successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to delete folder.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to delete folder.'));
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:20480',
            'folder_id' => 'nullable|integer',
            'storage_context' => 'nullable|string|max:80',
        ]);

        $uploadedCount = 0;
        $uploadedItems = [];
        $failedMessages = [];

        foreach ($request->file('files', []) as $file) {
            $apiRequest = $this->webApiClient($request)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName());

            $response = $apiRequest->post($this->webApiUrl('editor/media/items/upload'), array_filter([
                'folder_id' => $validated['folder_id'] ?? null,
                'storage_context' => $validated['storage_context'] ?? 'gallery',
            ], fn ($value) => $value !== null && $value !== ''));

            if ($response->successful()) {
                $uploadedCount++;
                $uploadedItems[] = $this->responseData($response, []);
            } else {
                $failedMessages[] = $this->responseMessage($response, "Failed to upload {$file->getClientOriginalName()}.");
            }
        }

        if ($uploadedCount > 0 && empty($failedMessages)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => "{$uploadedCount} " . Str::plural('file', $uploadedCount) . ' uploaded successfully.',
                    'data' => $uploadedItems,
                ]);
            }

            return redirect()->back()->with('success', "{$uploadedCount} " . Str::plural('file', $uploadedCount) . ' uploaded successfully.');
        }

        if ($uploadedCount > 0) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'warning',
                    'message' => "{$uploadedCount} file(s) uploaded. " . implode(' ', $failedMessages),
                    'data' => $uploadedItems,
                ]);
            }

            return redirect()->back()->with('warning', "{$uploadedCount} file(s) uploaded. " . implode(' ', $failedMessages));
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $failedMessages[0] ?? 'Failed to upload media files.',
                'errors' => ['files' => $failedMessages ?: ['Failed to upload media files.']],
            ], 422);
        }

        return redirect()->back()
            ->withErrors(['files' => $failedMessages ?: ['Failed to upload media files.']])
            ->withInput();
    }

    public function libraryItems(Request $request)
    {
        $validated = $request->validate([
            'media_type' => 'nullable|string|in:image,video,document,other',
            'search' => 'nullable|string|max:150',
            'folder_id' => 'nullable|integer',
            'root_only' => 'nullable|boolean',
            'include_folders' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:60',
            'page' => 'nullable|integer|min:1',
        ]);

        $params = array_filter([
            'media_type' => $validated['media_type'] ?? null,
            'search' => $validated['search'] ?? null,
            'folder_id' => $validated['folder_id'] ?? null,
            'root_only' => $request->boolean('root_only') ? 1 : null,
            'per_page' => $validated['per_page'] ?? 24,
            'page' => $validated['page'] ?? 1,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->webApiClient($request)->get($this->webApiUrl('editor/media/items'), $params);

        if ($response->successful()) {
            $payload = $this->responseData($response, []);
            $items = collect(data_get($payload, 'data', []))->values();
            $pagination = [
                'current_page' => (int) data_get($payload, 'current_page', 1),
                'last_page' => (int) data_get($payload, 'last_page', 1),
                'per_page' => (int) data_get($payload, 'per_page', $validated['per_page'] ?? 24),
                'total' => (int) data_get($payload, 'total', $items->count()),
            ];

            $result = [
                'items' => $items,
                'pagination' => $pagination,
            ];

            if ($request->boolean('include_folders')) {
                $foldersResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/media/folders'));

                if ($foldersResponse->successful()) {
                    $folderPayload = $this->responseData($foldersResponse, []);
                    $folderTree = $this->annotateFolderTreeCounts(collect($folderPayload['tree'] ?? []));
                    $result['folders'] = $this->withFolderDepths(collect($folderPayload['flat'] ?? []), $folderTree)->values();
                } else {
                    $result['folders'] = [];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $this->responseMessage($response, 'Failed to load media items.'),
        ], $this->failureStatus($response));
    }

    public function updateItem(Request $request, int $id)
    {
        $validated = $request->validate([
            'folder_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
            'description' => 'nullable|string',
        ]);

        $response = $this->webApiClient($request)->put($this->webApiUrl("editor/media/items/{$id}"), $validated);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Media item updated successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->back()->with('success', 'Media item updated successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to update media item.'),
                'errors' => $this->flattenErrors($response, 'Failed to update media item.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()
            ->withErrors($this->flattenErrors($response, 'Failed to update media item.'))
            ->withInput();
    }

    public function downloadItem(Request $request, int $id)
    {
        $response = $this->webApiClient($request)->get($this->webApiUrl("editor/media/items/{$id}"));

        if (!$response->successful()) {
            abort($this->failureStatus($response), $this->responseMessage($response, 'Failed to download media item.'));
        }

        $item = (array) $this->responseData($response, []);
        $fileUrl = (string) ($item['full_url'] ?? $item['public_url'] ?? '');

        if ($fileUrl === '') {
            abort(404, 'Media file URL not found.');
        }

        $fileResponse = Http::withHeaders([
            'Accept' => '*/*',
        ])->get($fileUrl);

        if (!$fileResponse->successful()) {
            abort($fileResponse->status() ?: 502, 'Failed to fetch media file.');
        }

        $fileName = (string) ($item['original_name'] ?? $item['title'] ?? ('media-' . $id));
        $mimeType = (string) ($item['mime_type'] ?? $fileResponse->header('Content-Type') ?? 'application/octet-stream');
        $dispositionName = addcslashes($fileName, "\"\\");

        return response($fileResponse->body(), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$dispositionName}\"; filename*=UTF-8''" . rawurlencode($fileName),
            'Content-Length' => (string) strlen($fileResponse->body()),
        ]);
    }

    public function moveItem(Request $request, int $id)
    {
        $validated = $request->validate([
            'folder_id' => 'nullable|integer',
        ]);

        $response = $this->webApiClient($request)->post($this->webApiUrl("editor/media/items/{$id}/move"), $validated);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Media item moved successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->back()->with('success', 'Media item moved successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to move media item.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to move media item.'));
    }

    public function destroyItem(Request $request, int $id)
    {
        $response = $this->webApiClient($request)->delete($this->webApiUrl("editor/media/items/{$id}"));

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Media item deleted successfully.',
                ]);
            }

            return redirect()->back()->with('success', 'Media item deleted successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to delete media item.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to delete media item.'));
    }

    public function reorderFolders(Request $request)
    {
        $validated = $request->validate([
            'folders_payload' => 'required|string',
        ]);

        $decoded = json_decode($validated['folders_payload'], true);

        if (!is_array($decoded)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid folder hierarchy payload.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Invalid folder hierarchy payload.');
        }

        $response = $this->webApiClient($request)->post($this->webApiUrl('editor/media/folders/reorder'), [
            'folders' => $decoded,
        ]);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Folder hierarchy updated successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->back()->with('success', 'Folder hierarchy updated successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to update folder hierarchy.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to update folder hierarchy.'));
    }

    public function storeGallery(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'gallery_status' => 'nullable|in:Draft,Published,Withdrawn',
            'is_featured' => 'nullable|boolean',
            'author' => 'nullable|string|max:240',
        ]);

        $payload = array_merge($validated, [
            'gallery_status' => $validated['gallery_status'] ?? 'Draft',
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $response = $this->webApiClient($request)->post($this->webApiUrl('editor/galleries'), $payload);

        if ($response->successful()) {
            $galleryId = data_get($this->responseData($response, []), 'id');

            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Gallery created successfully.',
                    'data' => $this->responseData($response, []),
                    'meta' => ['gallery_id' => $galleryId],
                ]);
            }

            return redirect()->route('dashboard.web_curator.media.index', [
                'tab' => 'galleries',
                'gallery_id' => $galleryId,
            ])->with('success', 'Gallery created successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to create gallery.'),
                'errors' => $this->flattenErrors($response, 'Failed to create gallery.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()
            ->withErrors($this->flattenErrors($response, 'Failed to create gallery.'))
            ->withInput();
    }

    public function updateGallery(Request $request, int $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'gallery_status' => 'required|in:Draft,Published,Withdrawn',
            'is_featured' => 'nullable|boolean',
            'author' => 'nullable|string|max:240',
            'published_at' => 'nullable|date',
        ]);

        $payload = array_merge($validated, [
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $response = $this->webApiClient($request)->put($this->webApiUrl("editor/galleries/{$id}"), $payload);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Gallery updated successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->route('dashboard.web_curator.media.index', [
                'tab' => 'galleries',
                'gallery_id' => $id,
            ])->with('success', 'Gallery updated successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to update gallery.'),
                'errors' => $this->flattenErrors($response, 'Failed to update gallery.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()
            ->withErrors($this->flattenErrors($response, 'Failed to update gallery.'))
            ->withInput();
    }

    public function destroyGallery(Request $request, int $id)
    {
        $response = $this->webApiClient($request)->delete($this->webApiUrl("editor/galleries/{$id}"));

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Gallery deleted successfully.',
                ]);
            }

            return redirect()->route('dashboard.web_curator.media.index', [
                'tab' => 'galleries',
            ])->with('success', 'Gallery deleted successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to delete gallery.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to delete gallery.'));
    }

    public function addItemsToGallery(Request $request, int $id)
    {
        $validated = $request->validate([
            'media_item_ids' => 'required|array|min:1',
            'media_item_ids.*' => 'integer',
        ]);

        $galleryResponse = $this->webApiClient($request)->get($this->webApiUrl("editor/galleries/{$id}"));
        if (!$galleryResponse->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gallery not found.',
                ], 404);
            }

            return redirect()->back()->with('error', 'Gallery not found.');
        }

        $gallery = $this->responseData($galleryResponse, []);
        $existingMediaIds = collect(data_get($gallery, 'items', []))
            ->pluck('media_item_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $items = collect($validated['media_item_ids'])
            ->map(fn ($mediaId, $index) => (int) $mediaId)
            ->unique()
            ->reject(fn ($mediaId) => in_array($mediaId, $existingMediaIds, true))
            ->values()
            ->map(fn ($mediaId, $index) => [
                'media_item_id' => $mediaId,
                'sort_order' => count($existingMediaIds) + $index,
            ])
            ->all();

        if (empty($items)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Selected media item(s) are already in that gallery.',
                ]);
            }

            return redirect()->back()->with('warning', 'Selected media item(s) are already in that gallery.');
        }

        $response = $this->webApiClient($request)->post($this->webApiUrl("editor/galleries/{$id}/items"), [
            'items' => $items,
        ]);

        if ($response->successful()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Media added to gallery successfully.',
                    'data' => $this->responseData($response, []),
                ]);
            }

            return redirect()->route('dashboard.web_curator.media.index', [
                'tab' => 'galleries',
                'gallery_id' => $id,
            ])->with('success', 'Media added to gallery successfully.');
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->responseMessage($response, 'Failed to add media to gallery.'),
            ], $this->failureStatus($response));
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to add media to gallery.'));
    }

    public function removeItemsFromGallery(Request $request, int $id)
    {
        $validated = $request->validate([
            'gallery_item_ids' => 'required|array|min:1',
            'gallery_item_ids.*' => 'integer',
        ]);

        $failures = [];

        foreach ($validated['gallery_item_ids'] as $galleryItemId) {
            $response = $this->webApiClient($request)->delete($this->webApiUrl("editor/galleries/{$id}/items/{$galleryItemId}"));

            if (!$response->successful()) {
                $failures[] = $this->responseMessage($response, "Failed to remove gallery item {$galleryItemId}.");
            }
        }

        if (!empty($failures)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => $failures[0],
                    'errors' => $failures,
                ], 422);
            }

            return redirect()->back()->withErrors($failures);
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Selected media removed from gallery.',
            ]);
        }

        return redirect()->route('dashboard.web_curator.media.index', [
            'tab' => 'galleries',
            'gallery_id' => $id,
        ])->with('success', 'Selected media removed from gallery.');
    }

    private function fetchTypeStats(Request $request): array
    {
        $response = $this->webApiClient($request)->get($this->webApiUrl('editor/media/items'), [
            'fetch_all' => 1,
        ]);

        if (!$response->successful()) {
            return [
                'total' => 0,
                'image' => 0,
                'video' => 0,
                'document' => 0,
                'other' => 0,
            ];
        }

        $allItems = collect($this->responseData($response, []));

        return [
            'total' => $allItems->count(),
            'image' => $allItems->where('media_type', 'image')->count(),
            'video' => $allItems->where('media_type', 'video')->count(),
            'document' => $allItems->where('media_type', 'document')->count(),
            'other' => $allItems->where('media_type', 'other')->count(),
        ];
    }

    private function fetchGalleryCount(Request $request): int
    {
        $response = $this->webApiClient($request)->get($this->webApiUrl('editor/galleries'), [
            'fetch_all' => 1,
        ]);

        return $response->successful()
            ? collect($this->responseData($response, []))->count()
            : 0;
    }

    private function fetchFolderMediaItems(Request $request, $currentFolder): Collection
    {
        $params = array_filter([
            'folder_id' => data_get($currentFolder, 'id'),
            'root_only' => $currentFolder ? null : 1,
            'media_type' => $request->input('media_type'),
            'search' => $request->input('search'),
            'unused_only' => $request->boolean('unused_only') ? 1 : null,
            'used_in_galleries_only' => $request->boolean('used_in_galleries_only') ? 1 : null,
            'fetch_all' => 1,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->webApiClient($request)->get($this->webApiUrl('editor/media/items'), $params);

        return $response->successful()
            ? collect($this->responseData($response, []))->values()
            : collect();
    }

    private function fetchGalleryMediaItems(Request $request, int $galleryId): Collection
    {
        $response = $this->webApiClient($request)->get($this->webApiUrl("editor/galleries/{$galleryId}"));

        if (!$response->successful()) {
            return collect();
        }

        $gallery = $this->responseData($response, []);
        $items = collect(data_get($gallery, 'items', []))
            ->map(function ($galleryItem) {
                $mediaItem = data_get($galleryItem, 'media_item', []);

                if (!$mediaItem) {
                    return null;
                }

                return array_merge($mediaItem, [
                    'gallery_item_id' => data_get($galleryItem, 'id'),
                    'gallery_caption_override' => data_get($galleryItem, 'caption_override'),
                    'gallery_alt_override' => data_get($galleryItem, 'alt_override'),
                    'gallery_sort_order' => data_get($galleryItem, 'sort_order', 0),
                    'is_in_current_gallery' => true,
                ]);
            })
            ->filter()
            ->values();

        $search = trim((string) $request->input('search'));
        $type = (string) $request->input('media_type', '');

        if ($type !== '') {
            $items = $items->where('media_type', $type)->values();
        }

        if ($search !== '') {
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(
                    Str::lower(implode(' ', array_filter([
                        data_get($item, 'title'),
                        data_get($item, 'original_name'),
                        data_get($item, 'caption'),
                        data_get($item, 'gallery_caption_override'),
                    ]))),
                    Str::lower($search)
                );
            })->values();
        }

        return $items;
    }

    private function sortGalleries(Collection $galleries, string $sortBy): Collection
    {
        if ($sortBy === 'newest') {
            return $galleries->sortByDesc(fn ($gallery) => strtotime((string) data_get($gallery, 'created_at')) ?: 0)->values();
        }

        if ($sortBy === 'updated') {
            return $galleries->sortByDesc(fn ($gallery) => strtotime((string) data_get($gallery, 'updated_at')) ?: 0)->values();
        }

        return $galleries->sortBy(fn ($gallery) => Str::lower((string) data_get($gallery, 'title')))->values();
    }

    private function sortFolderTree(Collection $tree, string $sortBy): Collection
    {
        $sorted = $tree->map(function ($node) use ($sortBy) {
            $children = collect(data_get($node, 'children_tree', []));
            $sortedChildren = $this->sortFolderTree($children, $sortBy)->values();

            if (is_array($node)) {
                $node['children_tree'] = $sortedChildren->all();
                return $node;
            }

            $node->children_tree = $sortedChildren;
            return $node;
        });

        if ($sortBy === 'newest') {
            return $sorted->sortByDesc(fn ($folder) => strtotime((string) data_get($folder, 'created_at')) ?: 0)->values();
        }

        if ($sortBy === 'updated') {
            return $sorted->sortByDesc(fn ($folder) => strtotime((string) data_get($folder, 'updated_at')) ?: 0)->values();
        }

        return $sorted->sortBy(fn ($folder) => Str::lower((string) data_get($folder, 'folder_name')))->values();
    }

    private function fetchAllMediaItems(Request $request): Collection
    {
        $response = $this->webApiClient($request)->get($this->webApiUrl('editor/media/items'), [
            'fetch_all' => 1,
        ]);

        return $response->successful()
            ? collect($this->responseData($response, []))->values()
            : collect();
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->wantsJson();
    }
}
