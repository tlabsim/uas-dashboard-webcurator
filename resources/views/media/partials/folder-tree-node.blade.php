@php
    $level = $level ?? 0;
    $folderId = (int) data_get($folder, 'id');
    $folderName = (string) data_get($folder, 'folder_name', 'Untitled Folder');
    $children = collect(data_get($folder, 'children_tree', []));
    $itemCount = (int) data_get($folder, 'media_items_count', 0);
    $childCount = (int) data_get($folder, 'children_count', 0);
    $isActive = (int) ($currentFolderId ?? 0) === $folderId;
    $folderUrl = request()->fullUrlWithQuery([
        'folder_id' => $folderId,
        'page' => 1,
    ]);
@endphp

<div class="wc-folder-node" style="--folder-depth: {{ $level }};">
    <div class="wc-folder-row {{ $isActive ? 'is-active' : '' }}">
        <a href="{{ $folderUrl }}" class="wc-folder-link">
            <span class="wc-folder-icon">
                @if($isActive)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="currentColor" d="M35.32 13.74a1.71 1.71 0 0 0-1.45-.74h-22.7a2.59 2.59 0 0 0-2.25 1.52a1 1 0 0 0 0 .14L6 25V7h6.49l2.61 3.59a1 1 0 0 0 .81.41H32a2 2 0 0 0-2-2H16.42l-2.31-3.18A2 2 0 0 0 12.49 5H6a2 2 0 0 0-2 2v22.69A1.37 1.37 0 0 0 5.41 31h24.93a1 1 0 0 0 1-.72l4.19-15.1a1.64 1.64 0 0 0-.21-1.44M29.55 29H6.9l3.88-13.81a.66.66 0 0 1 .38-.24h22.33Z"/></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="currentColor" d="M30 9H16.42l-2.31-3.18A2 2 0 0 0 12.49 5H6a2 2 0 0 0-2 2v22a2 2 0 0 0 2 2h24a2 2 0 0 0 2-2V11a2 2 0 0 0-2-2m0 20H6V13h7.31a2 2 0 0 0 2-2H6V7h6.49l2.61 3.59a1 1 0 0 0 .81.41H30Z"/><path fill="none" d="M0 0h36v36H0z"/></svg>
                @endif
            </span>
            <span class="wc-folder-copy">
                <span class="wc-folder-name">{{ $folderName }}</span>
                <span class="wc-folder-meta">{{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}{{ $childCount ? ' • ' . $childCount . ' subfolder' . ($childCount === 1 ? '' : 's') : '' }}</span>
            </span>
        </a>

        <div class="wc-folder-actions">
            <button type="button" class="wc-folder-action-button" @click.stop.prevent='openFolderCreator({{ $folderId }})' title="Add subfolder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
            </button>
            <button type="button" class="wc-folder-action-button" @click.stop.prevent='openFolderEditor(@json($folder))' title="Edit folder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232a2.5 2.5 0 1 1 3.536 3.536L8 19.536l-4 1l1-4z"/></svg>
            </button>
        </div>
    </div>

    @if($children->isNotEmpty())
        <div class="wc-folder-children">
            @foreach($children as $child)
                @include('web_curator::media.partials.folder-tree-node', [
                    'folder' => $child,
                    'currentFolderId' => $currentFolderId,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
