<div x-show="uploadModal.open" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeUploadModal()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-2xl">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Add Media</h3>
                </div>
                <button type="button" class="modal-close" @click="closeUploadModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <input x-ref="uploadFileInput" type="file" multiple class="sr-only" @change="handleUploadFiles($event)">
                <button
                    type="button"
                    class="wc-upload-dropzone"
                    :class="{ 'is-active': uploadModal.dragActive }"
                    @click="openUploadFilePicker()"
                    @dragover="handleUploadDragOver($event)"
                    @dragleave="handleUploadDragLeave($event)"
                    @drop="handleUploadDrop($event)"
                >
                    <div class="wc-upload-dropzone-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="currentColor" d="M12.554 2.494a.75.75 0 0 0-1.107 0l-4 4.375A.75.75 0 0 0 8.553 7.88l2.696-2.95V16a.75.75 0 0 0 1.5 0V4.932l2.697 2.95a.75.75 0 1 0 1.107-1.013z"/><path fill="currentColor" d="M3.75 15a.75.75 0 0 0-1.5 0v.055c0 1.367 0 2.47.117 3.337c.12.9.38 1.658.981 2.26c.602.602 1.36.86 2.26.982c.867.116 1.97.116 3.337.116h6.11c1.367 0 2.47 0 3.337-.116c.9-.122 1.658-.38 2.26-.982s.86-1.36.982-2.26c.116-.867.116-1.97.116-3.337V15a.75.75 0 0 0-1.5 0c0 1.435-.002 2.436-.103 3.192c-.099.734-.28 1.122-.556 1.399c-.277.277-.665.457-1.4.556c-.755.101-1.756.103-3.191.103H9c-1.435 0-2.437-.002-3.192-.103c-.734-.099-1.122-.28-1.399-.556c-.277-.277-.457-.665-.556-1.4c-.101-.755-.103-1.756-.103-3.191"/></svg>
                    </div>
                    <div class="wc-upload-dropzone-title">Drop files here</div>
                    <div class="wc-upload-dropzone-subtitle">or click to browse</div>
                </button>
                <div>
                    <label class="label-base">Folder</label>
                    <select class="select-base w-full" x-model="uploadModal.folder_id">
                        <option value="">Root</option>
                        <template x-for="folder in foldersFlat" :key="'upload-folder-' + folder.id">
                            <option :value="folder.id" x-text="folderLabel(folder)"></option>
                        </template>
                    </select>
                </div>
                <div class="wc-upload-filelist" x-show="uploadModal.files.length > 0" x-cloak>
                    <div class="wc-upload-filelist-header">
                        <span x-text="`${uploadModal.files.length} file${uploadModal.files.length === 1 ? '' : 's'} selected`"></span>
                        <button type="button" class="wc-upload-clear" @click="clearUploadFiles()" x-show="uploadModal.files.length > 1">Clear</button>
                    </div>
                    <div class="wc-upload-filelist-body custom-scrollbar">
                        <template x-for="(file, index) in uploadModal.files" :key="`${file.name}-${file.size}-${file.lastModified}`">
                            <div class="wc-upload-file-row">
                                <div class="min-w-0">
                                    <div class="wc-upload-file-name" x-text="file.name"></div>
                                    <div class="wc-upload-file-meta" x-text="formatUploadFileSize(file.size)"></div>
                                </div>
                                <button type="button" class="wc-upload-file-remove" @click="removeUploadFile(index)" :disabled="uploadModal.busy" aria-label="Remove file">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>              
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeUploadModal()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="uploadModal.busy || uploadModal.files.length === 0" @click="submitUpload()">
                    <span x-text="uploadModal.busy ? 'Uploading...' : 'Upload'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="folderProperties.open" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeFolderProperties()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-lg">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Folder Properties</h3>
                </div>
                <button type="button" class="modal-close" @click="closeFolderProperties()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body space-y-3.5">
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr),180px]">
                    <div>
                        <label class="label-base">Title</label>
                        <input type="text" class="input-base w-full" x-model="folderProperties.form.folder_name">
                    </div>
                    <div>
                        <label class="label-base">Slug</label>
                        <input type="text" class="input-base w-full" x-model="folderProperties.form.slug">
                    </div>
                </div>
                <div>
                    <label class="label-base">Parent</label>
                    <select class="select-base w-full" x-model="folderProperties.form.parent_id">
                        <option value="">Root</option>
                        <template x-for="folder in availableParentFolders(folderProperties.form.id)" :key="'parent-folder-' + folder.id">
                            <option :value="folder.id" x-text="folderLabel(folder)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label-base">Description</label>
                    <textarea rows="2" class="textarea-base w-full" x-model="folderProperties.form.description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeFolderProperties()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="folderProperties.busy" @click="saveFolderProperties()">
                    <span x-text="folderProperties.busy ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="galleryProperties.open" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeGalleryProperties()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-2xl">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Gallery Properties</h3>
                </div>
                <button type="button" class="modal-close" @click="closeGalleryProperties()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body space-y-3.5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label-base">Title</label>
                        <input type="text" class="input-base w-full" x-model="galleryProperties.form.title">
                    </div>
                    <div>
                        <label class="label-base">Status</label>
                        <select class="select-base w-full" x-model="galleryProperties.form.gallery_status">
                            <option value="Draft">Draft</option>
                            <option value="Published">Published</option>
                            <option value="Withdrawn">Withdrawn</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label-base">Slug</label>
                        <input type="text" class="input-base w-full" x-model="galleryProperties.form.slug">
                    </div>
                    <div>
                        <label class="label-base">Author</label>
                        <input type="text" class="input-base w-full" x-model="galleryProperties.form.author">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label-base">Excerpt</label>
                        <input type="text" class="input-base w-full" x-model="galleryProperties.form.excerpt">
                    </div>
                    <label class="">
                        <span class="label-base mb-0">Featured</span>
                        <input type="checkbox" class="mt-2 toggle-switch" x-model="galleryProperties.form.is_featured">
                    </label>
                </div>
                <div>
                    <label class="label-base">Description</label>
                    <textarea rows="3" class="textarea-base w-full" x-model="galleryProperties.form.description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeGalleryProperties()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="galleryProperties.busy" @click="saveGalleryProperties()">
                    <span x-text="galleryProperties.busy ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="mediaProperties.open && mediaProperties.surface === 'modal'" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeMediaProperties()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-3xl">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Media Properties</h3>
                </div>
                <button type="button" class="modal-close" @click="closeMediaProperties()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body grid gap-5 lg:grid-cols-[220px,minmax(0,1fr)]">
                <div class="space-y-4">
                    <div class="wc-media-modal-preview">
                        <template x-if="mediaProperties.form.preview_url && mediaProperties.form.media_type === 'image'">
                            <img :src="mediaProperties.form.preview_url" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!mediaProperties.form.preview_url || mediaProperties.form.media_type !== 'image'">
                            <div class="wc-media-thumb-placeholder">
                                <template x-if="mediaProperties.form.media_type === 'video'">
                                    <span class="h-12 w-12">{!! $videoIcon !!}</span>
                                </template>
                                <template x-if="mediaProperties.form.media_type === 'document'">
                                    <span class="h-12 w-12">{!! $documentIcon !!}</span>
                                </template>
                                <template x-if="mediaProperties.form.media_type !== 'video' && mediaProperties.form.media_type !== 'document'">
                                    <span class="h-12 w-12">{!! $imageIcon !!}</span>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div class="rounded-2xl border px-4 py-3 text-sm text-gray-600" style="border-color: var(--border); background: var(--surface-raised);">
                        <div class="font-medium break-all text-[var(--text-strong)]" x-text="mediaProperties.form.original_name || 'Media item'"></div>
                        <div class="mt-1" x-text="mediaProperties.form.mime_type || ''"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="label-base">Title</label>
                        <input type="text" class="input-base w-full" x-model="mediaProperties.form.title">
                    </div>
                    <div>
                        <label class="label-base">Folder</label>
                        <select class="select-base w-full" x-model="mediaProperties.form.folder_id">
                            <option value="">Root</option>
                            <template x-for="folder in foldersFlat" :key="'media-folder-' + folder.id">
                                <option :value="folder.id" x-text="folderLabel(folder)"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label-base">Alt Text</label>
                        <input type="text" class="input-base w-full" x-model="mediaProperties.form.alt_text">
                    </div>
                    <div>
                        <label class="label-base">Caption</label>
                        <textarea rows="3" class="textarea-base w-full" x-model="mediaProperties.form.caption"></textarea>
                    </div>
                    <div>
                        <label class="label-base">Description</label>
                        <textarea rows="4" class="textarea-base w-full" x-model="mediaProperties.form.description"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeMediaProperties()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="mediaProperties.busy" @click="saveMediaProperties()">
                    <span x-text="mediaProperties.busy ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="folderDeleteModal.open" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeFolderDeleteModal()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-lg">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Delete Folder?</h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="folderDeleteModal.folder?.folder_name || 'Folder'"></p>
                </div>
                <button type="button" class="modal-close" @click="closeFolderDeleteModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <div class="space-y-3" x-show="folderHasManagedContents(folderDeleteModal.folder)" x-cloak>
                    <label class="wc-delete-choice">
                        <input type="radio" value="keep" x-model="folderDeleteModal.content_strategy" class="mt-0.5">
                        <div>
                            <div class="text-sm font-medium text-[var(--text-strong)]">Keep contents</div>
                            <div class="mt-0.5 text-[13px] text-gray-500">Move child folders and media items to Root.</div>
                        </div>
                    </label>
                    <label class="wc-delete-choice">
                        <input type="radio" value="delete" x-model="folderDeleteModal.content_strategy" class="mt-0.5">
                        <div>
                            <div class="text-sm font-medium text-[var(--text-strong)]">Delete contents</div>
                            <div class="mt-0.5 text-[13px] text-gray-500">Delete child folders and media inside this folder.</div>
                        </div>
                    </label>
                </div>
                <p class="text-sm text-gray-500" x-show="!folderHasManagedContents(folderDeleteModal.folder)" x-cloak>
                    This folder is empty.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeFolderDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="folderDeleteModal.busy" @click="confirmDeleteFolder()">
                    <span x-text="folderDeleteModal.busy ? 'Deleting...' : 'Delete Folder'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="addToGalleryModal.open" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeAddToGalleryModal()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-xl">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Add to Gallery</h3>
                </div>
                <button type="button" class="modal-close" @click="closeAddToGalleryModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <input type="text" class="input-base w-full" x-model="addToGalleryModal.search" placeholder="Search gallery">
                <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                    <template x-for="gallery in filteredAddToGalleryTargets()" :key="'add-gallery-' + gallery.id">
                        <button type="button" class="wc-modal-list-button" :class="{ 'is-active': Number(addToGalleryModal.gallery_id) === Number(gallery.id) }" @click="addToGalleryModal.gallery_id = gallery.id">
                            <span class="truncate text-left" x-text="gallery.title"></span>
                            <span class="text-xs text-gray-500" x-text="`${gallery.items_count || 0} items`"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeAddToGalleryModal()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="addToGalleryModal.busy || !addToGalleryModal.gallery_id" @click="confirmAddToGallery()">
                    <span x-text="addToGalleryModal.busy ? 'Adding...' : 'Add'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="libraryPicker.open" x-cloak class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" @click="closeLibraryPicker()"></div>
    <div class="modal-container">
        <div class="modal-content wc-modal-compact max-w-4xl">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Add Media to Gallery</h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="currentGallery?.title || ''"></p>
                </div>
                <button type="button" class="modal-close" @click="closeLibraryPicker()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr),160px]">
                    <input type="text" class="input-base w-full" x-model="libraryPicker.search" placeholder="Search media">
                    <select class="select-base w-full" x-model="libraryPicker.media_type">
                        <option value="">All types</option>
                        <option value="image">Images</option>
                        <option value="video">Video</option>
                        <option value="document">Docs</option>
                    </select>
                </div>
                <div class="grid max-h-[28rem] grid-cols-2 gap-3 overflow-y-auto pr-1 md:grid-cols-3 xl:grid-cols-4">
                    <template x-for="item in filteredLibraryPickerItems()" :key="'picker-item-' + item.id">
                        <label class="wc-library-picker-item" :class="{ 'is-checked': libraryPicker.checkedIds.includes(Number(item.id)), 'is-disabled': galleryContainsMedia(item.id) }">
                            <input type="checkbox" class="sr-only" :checked="libraryPicker.checkedIds.includes(Number(item.id))" :disabled="galleryContainsMedia(item.id)" @change="toggleLibraryPickerItem(item.id)">
                            <div class="wc-library-picker-thumb">
                                <template x-if="item.media_type === 'image' && (item.full_url || item.public_url)">
                                    <img :src="item.thumbnail_full_url || item.thumbnail_url || item.full_url || item.public_url" alt="" class="h-full w-full object-cover">
                                </template>
                                <template x-if="item.media_type !== 'image' || !(item.full_url || item.public_url)">
                                    <div class="wc-media-thumb-placeholder">
                                        <template x-if="item.media_type === 'video'">
                                            <span class="h-10 w-10">{!! $videoIcon !!}</span>
                                        </template>
                                        <template x-if="item.media_type === 'document'">
                                            <span class="h-10 w-10">{!! $documentIcon !!}</span>
                                        </template>
                                        <template x-if="item.media_type !== 'video' && item.media_type !== 'document'">
                                            <span class="h-10 w-10">{!! $imageIcon !!}</span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <div class="wc-library-picker-title" x-text="item.title || item.original_name"></div>
                        </label>
                    </template>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="closeLibraryPicker()">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="libraryPicker.busy || libraryPicker.checkedIds.length === 0" @click="confirmLibraryPicker()">
                    <span x-text="libraryPicker.busy ? 'Adding...' : 'Add Selected'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
