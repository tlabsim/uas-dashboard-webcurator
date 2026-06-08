<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use UasDashboard\WebCurator\Concerns\InteractsWithWebApi;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    use InteractsWithWebApi;

    public function index(Request $request)
    {
        return redirect()->route('dashboard.web_curator.media.index', [
            'tab' => 'galleries',
        ]);
    }

    public function create(Request $request)
    {
        return redirect()->route('dashboard.web_curator.media.index', [
            'tab' => 'galleries',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateGalleryRequest($request);
        $items = $this->normalizeGalleryItems($validated['selected_items_payload'] ?? '[]');
        unset($validated['selected_items_payload']);

        $payload = array_merge($validated, [
            'items' => $items,
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $response = $this->webApiClient($request)->post($this->webApiUrl('editor/galleries'), $payload);

        if ($response->successful()) {
            $galleryId = $this->responseData($response, [])['id'] ?? null;

            if ($galleryId) {
                return redirect()->route('dashboard.web_curator.galleries.edit', $galleryId)
                    ->with('success', 'Gallery created successfully.');
            }

            return redirect()->route('dashboard.web_curator.galleries.index')
                ->with('success', 'Gallery created successfully.');
        }

        return redirect()->back()
            ->withErrors($this->flattenErrors($response, 'Failed to create gallery.'))
            ->withInput();
    }

    public function edit(Request $request, int $id)
    {
        return redirect()->route('dashboard.web_curator.media.index', [
            'tab' => 'galleries',
            'gallery_id' => $id,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateGalleryRequest($request, false);
        $selectedItems = $this->normalizeGalleryItems($validated['selected_items_payload'] ?? '[]');
        unset($validated['selected_items_payload']);

        $metadataPayload = array_merge($validated, [
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $updateResponse = $this->webApiClient($request)->put($this->webApiUrl("editor/galleries/{$id}"), $metadataPayload);

        if (!$updateResponse->successful()) {
            return redirect()->back()
                ->withErrors($this->flattenErrors($updateResponse, 'Failed to update gallery.'))
                ->withInput();
        }

        $currentResponse = $this->webApiClient($request)->get($this->webApiUrl("editor/galleries/{$id}"));

        if (!$currentResponse->successful()) {
            return redirect()->back()->with('warning', 'Gallery updated, but item synchronization could not be verified.');
        }

        $currentGallery = $this->responseData($currentResponse, []);
        $existingByMediaId = collect($currentGallery['items'] ?? [])
            ->mapWithKeys(function ($item) {
                $mediaId = (int) ($item['media_item_id'] ?? data_get($item, 'media_item.id'));
                return $mediaId > 0 ? [$mediaId => $item] : [];
            });

        $selectedMediaIds = collect($selectedItems)->pluck('media_item_id')->map(fn ($id) => (int) $id)->all();

        foreach ($existingByMediaId as $mediaId => $item) {
            if (!in_array((int) $mediaId, $selectedMediaIds, true)) {
                $this->webApiClient($request)->delete($this->webApiUrl("editor/galleries/{$id}/items/" . $item['id']));
            }
        }

        $newItems = [];

        foreach ($selectedItems as $index => $item) {
            $payload = [
                'caption_override' => $item['caption_override'] ?? null,
                'alt_override' => $item['alt_override'] ?? null,
                'sort_order' => $index,
            ];

            $mediaItemId = (int) ($item['media_item_id'] ?? 0);
            $existing = $existingByMediaId->get($mediaItemId);

            if ($existing) {
                $this->webApiClient($request)->put(
                    $this->webApiUrl("editor/galleries/{$id}/items/" . $existing['id']),
                    $payload
                );
            } else {
                $newItems[] = array_merge($payload, ['media_item_id' => $mediaItemId]);
            }
        }

        if (!empty($newItems)) {
            $this->webApiClient($request)->post($this->webApiUrl("editor/galleries/{$id}/items"), [
                'items' => $newItems,
            ]);
        }

        return redirect()->route('dashboard.web_curator.galleries.edit', $id)
            ->with('success', 'Gallery updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $response = $this->webApiClient($request)->delete($this->webApiUrl("editor/galleries/{$id}"));

        if ($response->successful()) {
            return redirect()->route('dashboard.web_curator.galleries.index')
                ->with('success', 'Gallery deleted successfully.');
        }

        return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to delete gallery.'));
    }

    private function editorPayload(Request $request): ?array
    {
        $context = $this->entityContext($request);

        if (!$context['entity_id']) {
            return null;
        }

        $foldersResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/media/folders'));
        $mediaResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/media/items'), [
            'fetch_all' => 1,
        ]);

        if (!$foldersResponse->successful() || !$mediaResponse->successful()) {
            return null;
        }

        $folderPayload = $this->responseData($foldersResponse, []);
        $folderTree = $this->annotateFolderTreeCounts(collect($folderPayload['tree'] ?? []));

        return [
            'context' => $context,
            'folderTree' => $folderTree,
            'foldersFlat' => $this->withFolderDepths(collect($folderPayload['flat'] ?? []), $folderTree),
            'mediaItems' => collect($this->responseData($mediaResponse, []))->values(),
        ];
    }

    private function validateGalleryRequest(Request $request, bool $isCreate = true): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'cover_media_item_id' => 'nullable|integer',
            'gallery_status' => 'required|in:Draft,Published,Withdrawn',
            'is_featured' => 'nullable|boolean',
            'author' => 'nullable|string|max:240',
            'published_at' => 'nullable|date',
            'selected_items_payload' => 'nullable|string',
        ]);
    }

    private function normalizeGalleryItems(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(function ($item, $index) {
                return [
                    'media_item_id' => (int) ($item['media_item_id'] ?? 0),
                    'caption_override' => $item['caption_override'] ?? null,
                    'alt_override' => $item['alt_override'] ?? null,
                    'sort_order' => $index,
                ];
            })
            ->filter(fn ($item) => $item['media_item_id'] > 0)
            ->values()
            ->all();
    }
}
