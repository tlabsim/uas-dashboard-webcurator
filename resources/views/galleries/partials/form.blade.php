@php
    $initialSelectedPayload = old('selected_items_payload')
        ? (json_decode(old('selected_items_payload'), true) ?: [])
        : ($selectedItems ?? []);
    $initialCoverId = old('cover_media_item_id', $selectedCoverId ?? null);
@endphp

<form method="POST"
      action="{{ $action }}"
      class="space-y-6"
      x-data="galleryEditorPage({
        mediaItems: @js($mediaItems->values()),
        selectedItems: @js($initialSelectedPayload),
        selectedCoverId: @js($initialCoverId),
      })">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="selected_items_payload" :value="serializedSelectedItems">
    <input type="hidden" name="cover_media_item_id" :value="selectedCoverId || ''">

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),380px]">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Basic Information</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="label-base">Title</label>
                        <input type="text" name="title" value="{{ old('title', data_get($gallery, 'title')) }}" class="input-base w-full" required>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label-base">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', data_get($gallery, 'slug')) }}" class="input-base w-full" placeholder="Optional">
                        </div>
                        <div>
                            <label class="label-base">Author</label>
                            <input type="text" name="author" value="{{ old('author', data_get($gallery, 'author')) }}" class="input-base w-full" placeholder="Optional">
                        </div>
                    </div>
                    <div>
                        <label class="label-base">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="textarea-base w-full" placeholder="Short summary...">{{ old('excerpt', data_get($gallery, 'excerpt')) }}</textarea>
                    </div>
                    <div>
                        <label class="label-base">Description</label>
                        <textarea name="description" rows="5" class="textarea-base w-full" placeholder="Describe the gallery...">{{ old('description', data_get($gallery, 'description')) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Media Library</h3>
                        <p class="mt-1 text-xs text-gray-500">Browse, filter, and add media to the gallery.</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr),200px,200px]">
                        <div>
                            <label class="label-base">Search</label>
                            <input type="text" x-model="search" class="input-base w-full" placeholder="Title, file name, caption...">
                        </div>
                        <div>
                            <label class="label-base">Folder</label>
                            <select x-model="activeFolderId" class="select-base w-full">
                                <option value="">All folders</option>
                                @foreach($foldersFlat as $folder)
                                    <option value="{{ data_get($folder, 'id') }}">
                                        {{ str_repeat('— ', max(0, (int) data_get($folder, 'depth', 0))) }}{{ data_get($folder, 'folder_name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-base">Type</label>
                            <select x-model="activeType" class="select-base w-full">
                                <option value="">All types</option>
                                <option value="image">Images</option>
                                <option value="video">Videos</option>
                                <option value="document">Docs</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                :class="activeType === '' ? 'border-transparent text-[var(--accent-foreground)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-raised)] text-[var(--text)]'"
                                :style="activeType === '' ? 'background: var(--accent);' : ''"
                                @click="activeType = ''">All</button>
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                :class="activeType === 'image' ? 'border-transparent text-[var(--accent-foreground)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-raised)] text-[var(--text)]'"
                                :style="activeType === 'image' ? 'background: var(--accent);' : ''"
                                @click="activeType = 'image'">Images</button>
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                :class="activeType === 'video' ? 'border-transparent text-[var(--accent-foreground)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-raised)] text-[var(--text)]'"
                                :style="activeType === 'video' ? 'background: var(--accent);' : ''"
                                @click="activeType = 'video'">Videos</button>
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                :class="activeType === 'document' ? 'border-transparent text-[var(--accent-foreground)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-raised)] text-[var(--text)]'"
                                :style="activeType === 'document' ? 'background: var(--accent);' : ''"
                                @click="activeType = 'document'">Docs</button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="item in filteredMedia()" :key="item.id">
                            <div class="card overflow-hidden">
                                <div class="wc-media-card-visual">
                                    <template x-if="item.media_type === 'image' && (item.full_url || item.public_url)">
                                        <img :src="item.thumbnail_full_url || item.thumbnail_url || item.full_url || item.public_url" :alt="item.alt_text || item.title || item.original_name || 'Media item'" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="item.media_type !== 'image' || !(item.full_url || item.public_url)">
                                        <div class="wc-media-card-placeholder">
                                            <template x-if="item.media_type === 'video'">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="h-12 w-12"><path fill="currentColor" d="M32.12 10H3.88A1.88 1.88 0 0 0 2 11.88v18.24A1.88 1.88 0 0 0 3.88 32h28.24A1.88 1.88 0 0 0 34 30.12V11.88A1.88 1.88 0 0 0 32.12 10M32 30H4V12h28Z"/><path fill="currentColor" d="M30.14 3a1 1 0 0 0-1-1h-22a1 1 0 0 0-1 1v1h24Z"/><path fill="currentColor" d="M32.12 7a1 1 0 0 0-1-1h-26a1 1 0 0 0-1 1v1h28Z"/><path fill="currentColor" d="M12.82 26.79a1.74 1.74 0 0 0 .93.28a1.7 1.7 0 0 0 .69-.15l9.77-4.36a1.69 1.69 0 0 0 0-3.1l-9.77-4.36a1.7 1.7 0 0 0-2.39 1.55v8.72a1.7 1.7 0 0 0 .77 1.42m.63-10.14a.29.29 0 0 1 .14-.25a.3.3 0 0 1 .16 0a.3.3 0 0 1 .12 0l9.77 4.35a.29.29 0 0 1 .18.28a.28.28 0 0 1-.18.27l-9.77 4.36a.28.28 0 0 1-.28 0a.31.31 0 0 1-.14-.25Z"/></svg>
                                            </template>
                                            <template x-if="item.media_type !== 'video'">
                                                <svg viewBox="0 0 24 24" class="h-12 w-12"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M14.5 19h-2c-2.829 0-4.243 0-5.121-.879c-.88-.878-.88-2.293-.88-5.121V8c0-2.828 0-4.243.88-5.121C8.256 2 9.67 2 12.499 2h1.344c.818 0 1.226 0 1.594.152c.367.152.656.442 1.234 1.02l2.657 2.656c.578.578.867.868 1.02 1.235c.152.368.152.776.152 1.594V13c0 2.828 0 4.243-.879 5.121C18.743 19 17.328 19 14.5 19"/><path d="M15 2.5v1c0 1.886 0 2.828.586 3.414c.585.586 1.528.586 3.414.586h1M6.5 5a3 3 0 0 0-3 3v8c0 2.828 0 4.243.878 5.121C5.257 22 6.671 22 9.5 22h5a3 3 0 0 0 3-3M10 11h4m-4 4h7"/></g></svg>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <div class="space-y-3 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-[var(--text-strong)]" x-text="item.title || item.original_name"></h4>
                                            <p class="truncate text-xs text-gray-500" x-text="item.folder?.folder_name || 'Root library'"></p>
                                        </div>
                                        <span class="badge-tint text-[11px]"
                                              :class="item.media_type === 'image' ? 'badge-tint-blue' : (item.media_type === 'video' ? 'badge-tint-purple' : (item.media_type === 'document' ? 'badge-tint-amber' : 'badge-tint-gray'))"
                                              x-text="item.media_type.charAt(0).toUpperCase() + item.media_type.slice(1)"></span>
                                    </div>

                                    <button type="button"
                                            class="btn w-full"
                                            :class="isSelected(item.id) ? 'btn-outline text-red-600' : 'btn-primary'"
                                            @click="toggleMedia(item)">
                                        <span x-text="isSelected(item.id) ? 'Remove' : 'Add to Gallery'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="filteredMedia().length === 0" x-cloak class="rounded-2xl border border-dashed border-[var(--border)] px-4 py-8 text-center text-sm text-gray-500">
                        No media matches the current filters.
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-[calc(var(--header-height)+1rem)] xl:self-start">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Publishing</h3>
                        <p class="mt-1 text-xs text-gray-500">Control visibility and emphasis.</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="label-base">Status</label>
                        <select name="gallery_status" class="select-base w-full">
                            @foreach(['Draft', 'Published', 'Withdrawn'] as $status)
                                <option value="{{ $status }}" @selected(old('gallery_status', data_get($gallery, 'gallery_status', 'Draft')) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-base">Published At</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', data_get($gallery, 'published_at') ? \Carbon\Carbon::parse(data_get($gallery, 'published_at'))->format('Y-m-d\TH:i') : '') }}" class="input-base w-full">
                    </div>
                    <label class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-600/20 bg-transparent px-4 py-3">
                        <div>
                            <span class="label-base mb-0" style="color: var(--accent);">Featured</span>
                            <p class="mt-1 text-xs text-gray-500">Highlight this gallery in public views.</p>
                        </div>
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', data_get($gallery, 'is_featured'))) class="toggle-switch">
                    </label>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Selected Media</h3>
                        <p class="mt-1 text-xs text-gray-500"><span x-text="selectedItems.length"></span> item(s) in this gallery.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <template x-for="(selected, index) in selectedItems" :key="selected.media_item_id">
                        <div class="wc-selected-media-card">
                            <div class="flex gap-3">
                                <div class="wc-selected-media-thumb">
                                    <template x-if="mediaById[selected.media_item_id]?.media_type === 'image' && (mediaById[selected.media_item_id]?.full_url || mediaById[selected.media_item_id]?.public_url)">
                                        <img :src="mediaById[selected.media_item_id].full_url || mediaById[selected.media_item_id].public_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="mediaById[selected.media_item_id]?.media_type !== 'image' || !(mediaById[selected.media_item_id]?.full_url || mediaById[selected.media_item_id]?.public_url)">
                                        <div class="wc-media-card-placeholder !h-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="h-8 w-8" x-show="mediaById[selected.media_item_id]?.media_type === 'video'"><path fill="currentColor" d="M32.12 10H3.88A1.88 1.88 0 0 0 2 11.88v18.24A1.88 1.88 0 0 0 3.88 32h28.24A1.88 1.88 0 0 0 34 30.12V11.88A1.88 1.88 0 0 0 32.12 10M32 30H4V12h28Z"/><path fill="currentColor" d="M30.14 3a1 1 0 0 0-1-1h-22a1 1 0 0 0-1 1v1h24Z"/><path fill="currentColor" d="M32.12 7a1 1 0 0 0-1-1h-26a1 1 0 0 0-1 1v1h28Z"/><path fill="currentColor" d="M12.82 26.79a1.74 1.74 0 0 0 .93.28a1.7 1.7 0 0 0 .69-.15l9.77-4.36a1.69 1.69 0 0 0 0-3.1l-9.77-4.36a1.7 1.7 0 0 0-2.39 1.55v8.72a1.7 1.7 0 0 0 .77 1.42m.63-10.14a.29.29 0 0 1 .14-.25a.3.3 0 0 1 .16 0a.3.3 0 0 1 .12 0l9.77 4.35a.29.29 0 0 1 .18.28a.28.28 0 0 1-.18.27l-9.77 4.36a.28.28 0 0 1-.28 0a.31.31 0 0 1-.14-.25Z"/></svg>
                                            <svg viewBox="0 0 24 24" class="h-8 w-8" x-show="mediaById[selected.media_item_id]?.media_type !== 'video'"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M14.5 19h-2c-2.829 0-4.243 0-5.121-.879c-.88-.878-.88-2.293-.88-5.121V8c0-2.828 0-4.243.88-5.121C8.256 2 9.67 2 12.499 2h1.344c.818 0 1.226 0 1.594.152c.367.152.656.442 1.234 1.02l2.657 2.656c.578.578.867.868 1.02 1.235c.152.368.152.776.152 1.594V13c0 2.828 0 4.243-.879 5.121C18.743 19 17.328 19 14.5 19"/><path d="M15 2.5v1c0 1.886 0 2.828.586 3.414c.585.586 1.528.586 3.414.586h1M6.5 5a3 3 0 0 0-3 3v8c0 2.828 0 4.243.878 5.121C5.257 22 6.671 22 9.5 22h5a3 3 0 0 0 3-3M10 11h4m-4 4h7"/></g></svg>
                                        </div>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-[var(--text-strong)]" x-text="mediaById[selected.media_item_id]?.title || mediaById[selected.media_item_id]?.original_name || 'Media item'"></h4>
                                            <p class="truncate text-xs text-gray-500" x-text="mediaById[selected.media_item_id]?.original_name || ''"></p>
                                        </div>
                                        <button type="button" class="text-sm font-medium text-red-600" @click="removeMedia(selected.media_item_id)">Remove</button>
                                    </div>

                                    <div class="grid gap-3">
                                        <div>
                                            <label class="label-base !text-xs">Caption Override</label>
                                            <textarea rows="2" x-model="selected.caption_override" class="textarea-base w-full !text-sm"></textarea>
                                        </div>
                                        <div>
                                            <label class="label-base !text-xs">Alt Override</label>
                                            <input type="text" x-model="selected.alt_override" class="input-base w-full !text-sm">
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="btn btn-outline btn-sm" @click="moveSelected(index, -1)" :disabled="index === 0">Move Up</button>
                                        <button type="button" class="btn btn-outline btn-sm" @click="moveSelected(index, 1)" :disabled="index === selectedItems.length - 1">Move Down</button>
                                        <button type="button"
                                                class="btn btn-sm"
                                                :class="selectedCoverId === selected.media_item_id ? 'btn-primary' : 'btn-outline'"
                                                @click="selectedCoverId = selected.media_item_id">
                                            <span x-text="selectedCoverId === selected.media_item_id ? 'Cover Media' : 'Set Cover'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="selectedItems.length === 0" x-cloak class="rounded-2xl border border-dashed border-[var(--border)] px-4 py-8 text-center text-sm text-gray-500">
                        Select items from the media library to build this gallery.
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="flex flex-wrap gap-3 border-t border-[var(--border-soft)] pt-4">
        <a href="{{ route('dashboard.web_curator.galleries.index') }}" class="btn-base btn-secondary">Cancel</a>
        <button type="submit" class="btn-base btn-primary">{{ $submitLabel }}</button>
    </div>
</form>

@push('scripts')
<script>
    function galleryEditorPage(config) {
        const mediaById = Object.fromEntries((config.mediaItems || []).map((item) => [Number(item.id), item]));

        const normalizeSelected = (items) => {
            return (items || [])
                .map((item, index) => ({
                    media_item_id: Number(item.media_item_id || item.id || 0),
                    caption_override: item.caption_override || '',
                    alt_override: item.alt_override || '',
                    sort_order: Number(item.sort_order ?? index),
                }))
                .filter((item) => item.media_item_id && mediaById[item.media_item_id]);
        };

        return {
            mediaItems: config.mediaItems || [],
            mediaById,
            search: '',
            activeFolderId: '',
            activeType: '',
            selectedItems: normalizeSelected(config.selectedItems),
            selectedCoverId: config.selectedCoverId ? Number(config.selectedCoverId) : null,
            init() {
                this.syncSelected();
                if (!this.selectedCoverId && this.selectedItems.length > 0) {
                    this.selectedCoverId = this.selectedItems[0].media_item_id;
                }
            },
            filteredMedia() {
                return this.mediaItems.filter((item) => {
                    const matchesSearch = !this.search || [item.title, item.original_name, item.caption, item.description]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase()
                        .includes(this.search.toLowerCase());

                    const matchesFolder = !this.activeFolderId || String(item.folder_id || '') === String(this.activeFolderId);
                    const matchesType = !this.activeType || item.media_type === this.activeType;

                    return matchesSearch && matchesFolder && matchesType;
                });
            },
            isSelected(mediaId) {
                return this.selectedItems.some((item) => Number(item.media_item_id) === Number(mediaId));
            },
            toggleMedia(item) {
                if (this.isSelected(item.id)) {
                    this.removeMedia(item.id);
                    return;
                }

                this.selectedItems.push({
                    media_item_id: Number(item.id),
                    caption_override: '',
                    alt_override: '',
                    sort_order: this.selectedItems.length,
                });

                if (!this.selectedCoverId) {
                    this.selectedCoverId = Number(item.id);
                }

                this.syncSelected();
            },
            removeMedia(mediaId) {
                this.selectedItems = this.selectedItems.filter((item) => Number(item.media_item_id) !== Number(mediaId));

                if (Number(this.selectedCoverId) === Number(mediaId)) {
                    this.selectedCoverId = this.selectedItems[0]?.media_item_id || null;
                }

                this.syncSelected();
            },
            moveSelected(index, delta) {
                const target = index + delta;
                if (target < 0 || target >= this.selectedItems.length) return;

                const clone = [...this.selectedItems];
                [clone[index], clone[target]] = [clone[target], clone[index]];
                this.selectedItems = clone;
                this.syncSelected();
            },
            syncSelected() {
                this.selectedItems = this.selectedItems.map((item, index) => ({
                    ...item,
                    sort_order: index,
                }));
            },
            get serializedSelectedItems() {
                return JSON.stringify(this.selectedItems.map((item) => ({
                    media_item_id: Number(item.media_item_id),
                    caption_override: item.caption_override || '',
                    alt_override: item.alt_override || '',
                    sort_order: Number(item.sort_order || 0),
                })));
            },
        };
    }
</script>
@endpush
