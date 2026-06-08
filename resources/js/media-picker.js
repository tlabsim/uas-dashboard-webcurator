const PICKER_SELECTOR = '[data-web-curator-media-picker]';

class WebCuratorMediaPicker {
    constructor(root) {
        this.root = root;
        this.overlay = root.querySelector('[data-picker-overlay]');
        this.backdrop = root.querySelector('[data-picker-backdrop]');
        this.modal = root.querySelector('[data-picker-modal]');
        this.title = root.querySelector('[data-picker-title]');
        this.searchInput = root.querySelector('[data-picker-search]');
        this.folderSelect = root.querySelector('[data-picker-folder]');
        this.uploadTrigger = root.querySelector('[data-picker-upload-trigger]');
        this.fileInput = root.querySelector('[data-picker-file-input]');
        this.dropzone = root.querySelector('[data-picker-dropzone]');
        this.feedback = root.querySelector('[data-picker-feedback]');
        this.queueWrap = root.querySelector('[data-picker-queue]');
        this.queueCount = root.querySelector('[data-picker-queue-count]');
        this.queueList = root.querySelector('[data-picker-queue-list]');
        this.queueClear = root.querySelector('[data-picker-queue-clear]');
        this.queueCancel = root.querySelector('[data-picker-queue-cancel]');
        this.uploadCommit = root.querySelector('[data-picker-upload-commit]');
        this.grid = root.querySelector('[data-picker-grid]');
        this.empty = root.querySelector('[data-picker-empty]');
        this.loadMoreWrap = root.querySelector('[data-picker-loadmore-wrap]');
        this.loadMoreButton = root.querySelector('[data-picker-loadmore]');
        this.cancelButtons = [...root.querySelectorAll('[data-picker-cancel], [data-picker-close]')];
        this.chooseButton = root.querySelector('[data-picker-choose]');
        this.selectionMeta = root.querySelector('[data-picker-selection-meta]');
        this.libraryUrl = root.dataset.libraryUrl || '';
        this.uploadUrl = root.dataset.uploadUrl || '';
        this.defaultMediaType = root.dataset.defaultMediaType || 'image';
        this.defaultUploadContext = root.dataset.defaultUploadContext || 'gallery';
        this.items = [];
        this.folders = [];
        this.selectedItemId = null;
        this.selectedItems = new Map();
        this.pendingResolve = null;
        this.options = {};
        this.searchDebounce = null;
        this.selectedFolderId = '';
        this.currentPage = 1;
        this.lastPage = 1;
        this.isLoading = false;
        this.pendingFiles = [];
        this.pendingFileId = 0;
        this.boundKeydown = this.handleKeydown.bind(this);
        this.bind();
    }

    bind() {
        this.backdrop?.addEventListener('click', () => this.close(null));
        this.cancelButtons.forEach((button) => button.addEventListener('click', () => this.close(null)));
        this.chooseButton?.addEventListener('click', () => this.confirmSelection());
        this.searchInput?.addEventListener('input', () => {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = window.setTimeout(() => {
                this.fetchItems({ reset: true });
            }, 140);
        });
        this.folderSelect?.addEventListener('change', () => {
            this.selectedFolderId = this.folderSelect.value || '';
            this.fetchItems({ reset: true });
        });
        this.uploadTrigger?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.fileInput?.click();
        });
        this.fileInput?.addEventListener('change', (event) => this.handleFiles(event.target.files));
        this.queueClear?.addEventListener('click', () => this.clearPendingFiles());
        this.queueCancel?.addEventListener('click', () => this.clearPendingFiles());
        this.uploadCommit?.addEventListener('click', () => this.commitUpload());
        this.loadMoreButton?.addEventListener('click', () => this.fetchItems({ reset: false }));

        this.dropzone?.addEventListener('click', () => this.fileInput?.click());
        this.dropzone?.addEventListener('dragover', (event) => {
            event.preventDefault();
            this.dropzone.classList.add('is-dragover');
        });
        this.dropzone?.addEventListener('dragleave', () => {
            this.dropzone.classList.remove('is-dragover');
        });
        this.dropzone?.addEventListener('drop', (event) => {
            event.preventDefault();
            this.dropzone.classList.remove('is-dragover');
            this.handleFiles(event.dataTransfer?.files || null);
        });
    }

    async open(options = {}) {
        this.options = {
            title: options.title || 'Choose media',
            mediaType: options.mediaType || this.defaultMediaType,
            uploadContext: options.uploadContext || this.defaultUploadContext,
            folderId: options.folderId ?? null,
            preferUpload: Boolean(options.preferUpload),
            multiple: Boolean(options.multiple),
        };

        this.title.textContent = this.options.title;
        this.root.classList.remove('hidden');
        this.overlay?.classList.remove('hidden');
        this.modal?.classList.remove('hidden');
        this.modal?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        document.addEventListener('keydown', this.boundKeydown);

        this.setFeedback('', '');
        this.searchInput.value = '';
        this.selectedItemId = null;
        this.selectedItems.clear();
        this.selectedFolderId = this.options.folderId === null || this.options.folderId === undefined
            ? ''
            : String(this.options.folderId);
        this.currentPage = 1;
        this.lastPage = 1;
        this.pendingFiles = [];
        if (this.chooseButton) {
            this.chooseButton.textContent = this.options.multiple ? 'Insert gallery' : 'Choose image';
        }
        this.updateSelectionMeta();
        this.renderFolders();
        this.renderPendingFiles();
        this.renderGrid();
        this.focusSearch();

        const promise = new Promise((resolve) => {
            this.pendingResolve = resolve;
        });

        await this.fetchItems({ reset: true, includeFolders: true });

        if (this.options.preferUpload) {
            window.setTimeout(() => this.fileInput?.click(), 80);
        }

        return promise;
    }

    close(result = null) {
        this.clearPendingFiles();
        if (this.pendingResolve) {
            this.pendingResolve(result);
            this.pendingResolve = null;
        }

        this.root.classList.add('hidden');
        this.overlay?.classList.add('hidden');
        this.modal?.classList.add('hidden');
        this.modal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        document.removeEventListener('keydown', this.boundKeydown);
    }

    handleKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            this.close(null);
        }
    }

    async fetchItems({ reset = false, includeFolders = false } = {}) {
        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        this.setFeedback(reset ? 'Loading images...' : 'Loading more images...', 'info');

        try {
            const url = new URL(this.libraryUrl, window.location.origin);
            url.searchParams.set('media_type', this.options.mediaType || 'image');
            url.searchParams.set('per_page', '24');
            url.searchParams.set('page', String(reset ? 1 : this.currentPage + 1));
            if (includeFolders) {
                url.searchParams.set('include_folders', '1');
            }
            if (this.selectedFolderId === '__root__') {
                url.searchParams.set('root_only', '1');
            } else if (this.selectedFolderId !== '') {
                url.searchParams.set('folder_id', this.selectedFolderId);
            }

            const searchTerm = String(this.searchInput?.value || '').trim();
            if (searchTerm !== '') {
                url.searchParams.set('search', searchTerm);
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json();

            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Failed to load media items.');
            }

            const data = payload.data || {};
            const pageItems = Array.isArray(data.items) ? data.items : [];
            const pagination = data.pagination || {};

            this.items = reset ? pageItems : [...this.items, ...pageItems];
            this.currentPage = Number(pagination.current_page || 1);
            this.lastPage = Number(pagination.last_page || 1);

            if (includeFolders && Array.isArray(data.folders)) {
                this.folders = data.folders;
                this.renderFolders();
            }

            this.setFeedback('', '');
            this.renderGrid();
            this.updateSelectionMeta();
        } catch (error) {
            if (reset) {
                this.items = [];
            }
            this.renderGrid();
            this.setFeedback(error.message || 'Failed to load media items.', 'error');
        } finally {
            this.isLoading = false;
        }
    }

    renderGrid() {
        if (!this.grid) {
            return;
        }

        this.grid.innerHTML = '';

        if (!this.options.multiple && this.selectedItemId && !this.items.some((item) => Number(item.id) === Number(this.selectedItemId))) {
            this.selectedItemId = null;
        }

        if (!this.items.length) {
            this.empty?.classList.remove('hidden');
            this.loadMoreWrap?.classList.add('hidden');
            return;
        }

        this.empty?.classList.add('hidden');
        const hasMore = this.currentPage < this.lastPage;
        this.loadMoreWrap?.classList.toggle('hidden', !hasMore);
        this.loadMoreButton.disabled = this.isLoading;

        this.items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'wc-media-picker-item';
            button.dataset.itemId = String(item.id);

            const isSelected = this.options.multiple
                ? this.selectedItems.has(Number(item.id))
                : Number(item.id) === Number(this.selectedItemId);

            if (isSelected) {
                button.classList.add('is-selected');
            }

            const img = document.createElement('img');
            img.className = 'wc-media-picker-item-thumb';
            img.src = item.thumbnail_full_url || item.full_url || item.public_url || '';
            img.alt = item.alt_text || item.title || item.original_name || 'Media item';
            img.loading = 'lazy';
            img.draggable = false;

            const name = document.createElement('div');
            name.className = 'wc-media-picker-item-title';
            name.textContent = this.displayItemTitle(item);

            button.append(img, name);
            button.addEventListener('click', () => {
                if (this.options.multiple) {
                    const itemId = Number(item.id);
                    if (this.selectedItems.has(itemId)) {
                        this.selectedItems.delete(itemId);
                    } else {
                        this.selectedItems.set(itemId, item);
                    }
                } else {
                    this.selectedItemId = Number(item.id);
                }
                this.renderGrid();
                this.updateSelectionMeta();
            });
            button.addEventListener('dblclick', () => {
                if (this.options.multiple) {
                    return;
                }

                this.selectedItemId = Number(item.id);
                this.confirmSelection();
            });

            this.grid.appendChild(button);
        });
    }

    renderFolders() {
        if (!this.folderSelect) {
            return;
        }

        const options = [
            { value: '', label: 'All folders' },
            { value: '__root__', label: 'Root only' },
            ...this.folders.map((folder) => ({
                value: String(folder.id),
                label: `${'– '.repeat(Math.max(0, Number(folder.depth || 0)))}${folder.folder_name || `Folder #${folder.id}`}`,
            })),
        ];

        this.folderSelect.innerHTML = options
            .map((option) => `<option value="${option.value}">${this.escapeHtml(option.label)}</option>`)
            .join('');

        this.folderSelect.value = this.selectedFolderId;
    }

    updateSelectionMeta() {
        if (this.options.multiple) {
            const selectedItems = [...this.selectedItems.values()];
            this.chooseButton.disabled = selectedItems.length === 0;

            if (!selectedItems.length) {
                this.selectionMeta.textContent = 'No images selected';
                return;
            }

            if (selectedItems.length === 1) {
                this.selectionMeta.textContent = this.displayItemTitle(selectedItems[0]);
                return;
            }

            this.selectionMeta.textContent = `${selectedItems.length} images selected`;
            return;
        }

        const selected = this.items.find((item) => Number(item.id) === Number(this.selectedItemId)) || null;
        this.chooseButton.disabled = !selected;

        if (!selected) {
            this.selectionMeta.textContent = 'No image selected';
            return;
        }

        this.selectionMeta.textContent = this.displayItemTitle(selected);
    }

    confirmSelection() {
        if (this.options.multiple) {
            const selectedItems = [...this.selectedItems.values()];
            if (!selectedItems.length) {
                return;
            }

            this.close(selectedItems);
            return;
        }

        const selected = this.items.find((item) => Number(item.id) === Number(this.selectedItemId)) || null;
        if (!selected) {
            return;
        }

        this.close(selected);
    }

    async handleFiles(fileList) {
        const files = Array.from(fileList || []).filter((file) => file.type.startsWith('image/'));
        this.fileInput.value = '';

        if (!files.length) {
            this.setFeedback('Please choose valid image files.', 'error');
            return;
        }

        const queuedEntries = files.map((file) => ({
            id: ++this.pendingFileId,
            file,
            previewUrl: URL.createObjectURL(file),
        }));

        this.pendingFiles = [...this.pendingFiles, ...queuedEntries];
        this.renderPendingFiles();
        this.setFeedback(`${files.length} image${files.length > 1 ? 's' : ''} added to upload queue.`, 'info');
    }

    renderPendingFiles() {
        if (!this.queueWrap || !this.queueList) {
            return;
        }

        this.queueWrap.classList.toggle('hidden', this.pendingFiles.length === 0);
        this.queueCount.textContent = `${this.pendingFiles.length} file${this.pendingFiles.length === 1 ? '' : 's'} selected`;
        this.queueList.innerHTML = '';

        this.pendingFiles.forEach((entry) => {
            const row = document.createElement('div');
            row.className = 'wc-media-picker-queue-row';

            const preview = document.createElement('img');
            preview.className = 'wc-media-picker-queue-thumb';
            preview.src = entry.previewUrl;
            preview.alt = entry.file.name;
            preview.draggable = false;

            const meta = document.createElement('div');
            meta.className = 'wc-media-picker-queue-meta';
            meta.innerHTML = `
                <div class="wc-media-picker-queue-name">${this.escapeHtml(entry.file.name)}</div>
                <div class="wc-media-picker-queue-size">${this.formatFileSize(entry.file.size)}</div>
            `;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'wc-media-picker-queue-remove';
            remove.setAttribute('aria-label', 'Remove file');
            remove.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.42-1.42L12 9.17L7.12 4.29A1 1 0 0 0 5.7 5.71L10.59 10.6L5.7 15.48a1 1 0 1 0 1.42 1.42L12 12l4.88 4.9a1 1 0 0 0 1.42-1.42l-4.89-4.88z"/></svg>';
            remove.addEventListener('click', () => {
                this.revokePendingEntry(entry);
                this.pendingFiles = this.pendingFiles.filter((item) => item.id !== entry.id);
                this.renderPendingFiles();
            });

            row.append(preview, meta, remove);
            this.queueList.appendChild(row);
        });
    }

    clearPendingFiles() {
        this.pendingFiles.forEach((entry) => this.revokePendingEntry(entry));
        this.pendingFiles = [];
        this.renderPendingFiles();
        this.setFeedback('', '');
    }

    async commitUpload() {
        if (!this.pendingFiles.length) {
            return;
        }

        const formData = new FormData();
        this.pendingFiles.forEach((entry) => formData.append('files[]', entry.file));
        formData.append('storage_context', this.options.uploadContext || this.defaultUploadContext);

        if (this.selectedFolderId && this.selectedFolderId !== '__root__') {
            formData.append('folder_id', String(this.selectedFolderId));
        }

        this.setFeedback(`Uploading ${this.pendingFiles.length} image${this.pendingFiles.length > 1 ? 's' : ''}...`, 'info');
        this.setUploading(true);

        try {
            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await response.json();

            if (!response.ok || !['success', 'warning'].includes(payload.status)) {
                throw new Error(payload.message || 'Upload failed.');
            }

            const uploadedItems = Array.isArray(payload.data) ? payload.data : [];

            if (uploadedItems.length) {
                if (this.options.multiple) {
                    uploadedItems.forEach((item) => this.selectedItems.set(Number(item.id), item));
                }
                this.selectedItemId = Number(uploadedItems[0].id);
            }

            this.clearPendingFiles();
            await this.fetchItems({ reset: true });
            this.setFeedback(payload.message || 'Upload completed.', payload.status === 'warning' ? 'warning' : 'success');
        } catch (error) {
            this.setFeedback(error.message || 'Upload failed.', 'error');
        } finally {
            this.setUploading(false);
        }
    }

    setUploading(isUploading) {
        this.uploadTrigger.disabled = isUploading;
        if (this.folderSelect) {
            this.folderSelect.disabled = isUploading;
        }
        if (this.searchInput) {
            this.searchInput.disabled = isUploading;
        }
        const hasSelection = this.options.multiple
            ? this.selectedItems.size > 0
            : Boolean(this.selectedItemId);
        this.chooseButton.disabled = isUploading || !hasSelection;
        if (this.uploadCommit) {
            this.uploadCommit.disabled = isUploading || this.pendingFiles.length === 0;
            this.uploadCommit.textContent = isUploading ? 'Uploading...' : 'Upload selected';
        }
        this.dropzone.classList.toggle('is-uploading', isUploading);
    }

    setFeedback(message, tone = 'info') {
        if (!this.feedback) {
            return;
        }

        this.feedback.textContent = message || '';
        this.feedback.className = `wc-media-picker-feedback${message ? '' : ' hidden'}${tone ? ` is-${tone}` : ''}`;
    }

    focusSearch() {
        window.setTimeout(() => {
            this.searchInput?.focus({ preventScroll: true });
        }, 80);
    }

    formatFileSize(size) {
        const bytes = Number(size || 0);
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    revokePendingEntry(entry) {
        if (entry?.previewUrl) {
            URL.revokeObjectURL(entry.previewUrl);
        }
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    displayItemTitle(item) {
        const title = String(item?.title || '').trim();
        const originalName = String(item?.original_name || item?.file_name || '').trim();

        if (!title) {
            return originalName || `Media #${item?.id ?? ''}`;
        }

        const extension = this.extractExtension(originalName);
        if (!extension || title.toLowerCase().endsWith(`.${extension.toLowerCase()}`)) {
            return title;
        }

        return `${title}.${extension}`;
    }

    extractExtension(filename) {
        const clean = String(filename || '').trim();
        const segments = clean.split('.');
        if (segments.length < 2) {
            return '';
        }

        return segments.pop() || '';
    }

    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
}

let pickerInstance = null;

const getPicker = () => {
    if (pickerInstance) {
        return pickerInstance;
    }

    const root = document.querySelector(PICKER_SELECTOR);
    if (!root) {
        return null;
    }

    pickerInstance = new WebCuratorMediaPicker(root);
    return pickerInstance;
};

window.WebCuratorMediaPicker = {
    open(options = {}) {
        const picker = getPicker();

        if (!picker) {
            return Promise.resolve(null);
        }

        return picker.open(options);
    },
};
