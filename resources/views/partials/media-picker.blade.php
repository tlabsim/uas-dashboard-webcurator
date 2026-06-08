@props([
    'mediaType' => 'image',
    'libraryUrl',
    'uploadUrl',
    'uploadContext' => 'gallery',
])

<div
    data-web-curator-media-picker
    data-library-url="{{ $libraryUrl }}"
    data-upload-url="{{ $uploadUrl }}"
    data-default-media-type="{{ $mediaType }}"
    data-default-upload-context="{{ $uploadContext }}"
    class="hidden"
>
    <div class="modal-overlay hidden" data-picker-overlay>
        <div class="modal-backdrop" data-picker-backdrop></div>
        <div class="modal-container">
            <div class="modal-content wc-modal-surface wc-media-picker-modal" data-picker-modal tabindex="-1" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" data-picker-title>Choose Image</h3>
                </div>
                <button type="button" class="modal-close" data-picker-close aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M18.3 5.71a.996.996 0 0 0-1.41 0L12 10.59L7.11 5.7A.996.996 0 1 0 5.7 7.11L10.59 12L5.7 16.89a.996.996 0 1 0 1.41 1.41L12 13.41l4.89 4.89a.996.996 0 1 0 1.41-1.41L13.41 12l4.89-4.89c.38-.38.38-1.02 0-1.4"/></svg>
                </button>
            </div>

            <div class="modal-body custom-scrollbar wc-modal-compact">
                <div class="wc-media-picker-dropzone" data-picker-dropzone>
                    <input type="file" class="hidden" accept="image/*" multiple data-picker-file-input>
                    <div class="wc-media-picker-dropzone-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-500" viewBox="0 0 24 24"><path fill="currentColor" d="M12.554 2.494a.75.75 0 0 0-1.107 0l-4 4.375A.75.75 0 0 0 8.553 7.88l2.696-2.95V16a.75.75 0 0 0 1.5 0V4.932l2.697 2.95a.75.75 0 1 0 1.107-1.013z"/><path fill="currentColor" d="M3.75 15a.75.75 0 0 0-1.5 0v.055c0 1.367 0 2.47.117 3.337c.12.9.38 1.658.981 2.26c.602.602 1.36.86 2.26.982c.867.116 1.97.116 3.337.116h6.11c1.367 0 2.47 0 3.337-.116c.9-.122 1.658-.38 2.26-.982s.86-1.36.982-2.26c.116-.867.116-1.97.116-3.337V15a.75.75 0 0 0-1.5 0c0 1.435-.002 2.436-.103 3.192c-.099.734-.28 1.122-.556 1.399c-.277.277-.665.457-1.4.556c-.755.101-1.756.103-3.191.103H9c-1.435 0-2.437-.002-3.192-.103c-.734-.099-1.122-.28-1.399-.556c-.277-.277-.457-.665-.556-1.4c-.101-.755-.103-1.756-.103-3.191"/></svg>
                        <div>
                            <p class="wc-media-picker-dropzone-title">Drop images here</p>
                            <p class="wc-media-picker-dropzone-note">
                                or
                                <button type="button" class="wc-media-picker-browse-link" data-picker-upload-trigger>Click to browse</button>
                                from your device
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 wc-media-picker-queue hidden" data-picker-queue>
                    <div class="wc-media-picker-queue-header">
                        <span data-picker-queue-count>0 files selected</span>
                        <button type="button" class="wc-media-picker-queue-clear" data-picker-queue-clear>Clear</button>
                    </div>
                    <div class="wc-media-picker-queue-list" data-picker-queue-list></div>
                    <div class="wc-media-picker-queue-actions">
                        <button type="button" class="btn-base btn-secondary" data-picker-queue-cancel>Cancel upload</button>
                        <button type="button" class="btn-base btn-primary" data-picker-upload-commit>Upload selected</button>
                    </div>
                </div>

                <div class="mt-8 wc-media-picker-section wc-media-picker-section-library">
                    <div class="wc-media-picker-section-header">
                        <h4 class="wc-media-picker-section-title">Choose from Media Library</h4>
                        <div class="wc-media-picker-toolbar">
                            <select class="select-base wc-media-picker-folder-select" data-picker-folder>
                                <option value="">All folders</option>
                            </select>
                            <input
                                type="search"
                                class="input-base wc-media-picker-search"
                                placeholder="Search images..."
                                data-picker-search
                            >
                        </div>
                    </div>

                    <div class="wc-media-picker-feedback hidden" data-picker-feedback></div>

                    <div class="wc-media-picker-grid custom-scrollbar" data-picker-grid></div>

                    <div class="wc-media-picker-empty hidden" data-picker-empty>
                        <p class="wc-media-picker-empty-title">No images found</p>
                        <p class="wc-media-picker-empty-text">Try another search or upload a new image.</p>
                    </div>

                    <div class="wc-media-picker-loadmore hidden" data-picker-loadmore-wrap>
                        <button type="button" class="btn-base btn-secondary" data-picker-loadmore>Load more</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="wc-media-picker-footer-meta" data-picker-selection-meta>No image selected</div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-base btn-secondary" data-picker-cancel>Cancel</button>
                    <button type="button" class="btn-base btn-primary" data-picker-choose disabled>Use image</button>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
