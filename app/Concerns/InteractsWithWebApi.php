<?php

namespace UasDashboard\WebCurator\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

trait InteractsWithWebApi
{
    protected function webApiClient(Request $request): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ]);
    }

    protected function webApiUrl(string $path): string
    {
        return rtrim((string) config('web-api.api_base_url'), '/') . '/' . ltrim($path, '/');
    }

    protected function currentEntityScope(Request $request): ?int
    {
        $scope = $request->attributes->get('current_role_scope');

        return $scope ? (int) $scope : null;
    }

    protected function entityContext(Request $request): array
    {
        $currentRoleId = session('ims_user.current_db_role_id', null);
        $allRoles = collect(session('ims_user.db_roles', []));
        $currentRole = $allRoles->firstWhere('assignment_id', $currentRoleId);

        return [
            'entity_id' => $this->currentEntityScope($request),
            'entity_name' => $currentRole['scope_entity_name'] ?? 'Unknown Entity',
            'entity_slug' => $currentRole['scope_entity_slug'] ?? null,
        ];
    }

    protected function responseData($response, $default = [])
    {
        return $response->json('data', $default);
    }

    protected function responseMessage($response, string $fallback): string
    {
        $message = (string) ($response->json('message')
            ?? $response->json('error')
            ?? $fallback);

        return $this->sanitizeApiMessage($message, $fallback);
    }

    protected function responseErrors($response): array
    {
        return (array) ($response->json('errors') ?? []);
    }

    protected function flattenErrors($response, string $fallback): array
    {
        $errors = $this->responseErrors($response);

        if (empty($errors)) {
            return ['api' => [$this->responseMessage($response, $fallback)]];
        }

        return collect($errors)
            ->map(function ($messages) {
                return is_array($messages) ? $messages : [(string) $messages];
            })
            ->toArray();
    }

    protected function failureStatus($response): int
    {
        return $response->status() ?: Response::HTTP_BAD_REQUEST;
    }

    protected function sanitizeApiMessage(string $message, string $fallback): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return $fallback;
        }

        if (
            str_contains($trimmed, 'SQLSTATE[')
            || str_contains($trimmed, 'Integrity constraint violation')
            || str_contains($trimmed, 'QueryException')
        ) {
            return $fallback;
        }

        return $trimmed;
    }

    protected function withFolderDepths(Collection $folders, ?Collection $tree = null): Collection
    {
        $orderedFolders = $tree ? $this->flattenFolderTree($tree) : $folders->values();
        $folderMap = $orderedFolders->keyBy('id');

        return $orderedFolders->map(function ($folder) use ($folderMap) {
            $depth = 0;
            $parentId = data_get($folder, 'parent_id');

            while ($parentId) {
                $depth++;
                $parent = $folderMap->get($parentId);
                $parentId = data_get($parent, 'parent_id');
            }

            if (is_array($folder)) {
                $folder['depth'] = $depth;
                return $folder;
            }

            $folder->depth = $depth;
            return $folder;
        })->values();
    }

    protected function annotateFolderTreeCounts(Collection $tree): Collection
    {
        return $tree->map(function ($node) {
            $children = $this->annotateFolderTreeCounts(collect(data_get($node, 'children_tree', [])));
            $directCount = (int) data_get($node, 'media_items_count', 0);
            $totalCount = $directCount + $children->sum(fn ($child) => (int) data_get($child, 'total_media_items_count', 0));

            if (is_array($node)) {
                $node['children_tree'] = $children->values()->all();
                $node['total_media_items_count'] = $totalCount;
                return $node;
            }

            $node->children_tree = $children->values();
            $node->total_media_items_count = $totalCount;
            return $node;
        })->values();
    }

    protected function flattenFolderTree(Collection $tree): Collection
    {
        $items = collect();

        $walk = function (Collection $nodes) use (&$walk, &$items) {
            $nodes->each(function ($node) use (&$walk, &$items) {
                $items->push($node);

                $children = collect(data_get($node, 'children_tree', []));
                if ($children->isNotEmpty()) {
                    $walk($children);
                }
            });
        };

        $walk($tree);

        return $items->values();
    }
}
