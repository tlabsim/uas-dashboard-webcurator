function registerMediaWorkspace() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('mediaWorkspace', (config = {}) => ({
        routes: config.routes || {},
        context: {},
        folderTree: [],
        foldersFlat: [],
        foldersFlatNatural: [],
        mediaItems: [],
        libraryMediaItems: [],
        galleries: [],
        typeStats: {},
        galleryCount: 0,
        tab: 'folders',
        folderSort: 'name',
        gallerySort: 'name',
        mediaSort: 'modified',
        currentFolderId: null,
        activeGalleryId: null,
        searchText: '',
        mediaTypeFilter: '',
        thumb: 'md',
        loadingPane: false,
        checkedIds: [],
        selectedId: null,
        statusMessage: '',
        statusTone: 'muted',
        statusTimer: null,
        renamingMediaId: null,
        renamingFolderId: null,
        renamingGalleryId: null,
        renameDraftTitle: '',
        draggingFolderId: null,
        dragOverFolderId: null,
        dragOverRoot: false,
        draggingMediaId: null,
        mediaDragOverFolderId: null,
        mediaDragOverRoot: false,
        mediaDragOverSubfolderId: null,
        rootExpanded: true,
        expandedFolderIds: [],
        busy: false,
        creatingFolder: false,
        creatingFolderBusy: false,
        createFolderTitle: '',
        createFolderParentId: null,
        creatingGallery: false,
        creatingGalleryBusy: false,
        createGalleryTitle: '',
        contextMenu: { open: false, x: 0, y: 0, type: null, target: null, submenu: null, submenuSide: 'right', submenuTop: 0 },
        uploadModal: { open: false, busy: false, files: [], folder_id: '', dragActive: false },
        folderProperties: { open: false, busy: false, form: {} },
        galleryProperties: { open: false, busy: false, form: {} },
        mediaProperties: { open: false, busy: false, form: {} },
        lightbox: {
            open: false,
            itemId: null,
            zoom: 1,
            panX: 0,
            panY: 0,
            panning: false,
            startX: 0,
            startY: 0,
            originPanX: 0,
            originPanY: 0,
            fitWidth: null,
            fitHeight: null,
        },
        folderDeleteModal: { open: false, busy: false, folder: null, content_strategy: 'keep' },
        addToGalleryModal: { open: false, busy: false, search: '', gallery_id: '', mediaIds: [] },
        libraryPicker: { open: false, busy: false, search: '', media_type: '', checkedIds: [] },

        init() {
            this.applyPayload(config.payload || {});
            window.addEventListener('keydown', (event) => {
                if (this.lightbox.open) {
                    if (event.key === 'Escape') {
                        this.closeLightbox();
                        return;
                    }

                    if (event.key === 'ArrowRight') {
                        this.showNextLightboxItem();
                        return;
                    }

                    if (event.key === 'ArrowLeft') {
                        this.showPreviousLightboxItem();
                        return;
                    }
                }

                if (event.key === 'Escape') {
                    this.closeContextMenu();
                }
            });
            window.addEventListener('resize', () => {
                this.closeContextMenu();
                this.scheduleLightboxFit();
            });
        },

        applyPayload(payload) {
            const previousExpanded = [...this.expandedFolderIds];
            this.context = payload.context || {};
            this.folderTree = payload.folderTree || [];
            this.foldersFlat = payload.foldersFlat || [];
            this.foldersFlatNatural = payload.foldersFlatNatural || payload.foldersFlat || [];
            this.mediaItems = payload.mediaItems || [];
            this.libraryMediaItems = payload.libraryMediaItems || [];
            this.galleries = payload.galleries || [];
            this.typeStats = payload.typeStats || {};
            this.galleryCount = payload.galleryCount || 0;

            const filters = payload.filters || {};
            this.tab = filters.tab || this.tab || 'folders';
            this.folderSort = filters.folder_sort || this.folderSort || 'name';
            this.gallerySort = filters.gallery_sort || this.gallerySort || 'name';
            this.mediaSort = filters.media_sort || this.mediaSort || 'modified';
            this.currentFolderId = filters.folder_id ?? payload.currentFolder?.id ?? null;
            this.activeGalleryId = filters.gallery_id ?? payload.activeGallery?.id ?? null;
            this.thumb = filters.thumb || this.thumb || 'md';
            this.loadingPane = false;

            this.checkedIds = [];
            this.expandedFolderIds = previousExpanded.filter((id) => !!this.getFolderById(id));
            this.expandFolderPath(this.currentFolderId);

            if (!this.visibleItemIds().includes(Number(this.selectedId))) {
                this.selectedId = this.filteredItems()[0]?.id ?? null;
            }
        },

        get currentGallery() {
            return this.galleries.find((gallery) => Number(gallery.id) === Number(this.activeGalleryId)) || null;
        },

        get currentFolder() {
            return this.foldersFlat.find((folder) => Number(folder.id) === Number(this.currentFolderId)) || null;
        },

        get currentFolderName() {
            return this.currentFolder?.folder_name || 'Root Folder';
        },

        get canGoUpFolder() {
            return this.tab === 'folders' && !!this.currentFolderId;
        },

        get hasGalleries() {
            return this.galleries.length > 0;
        },

        get showNoGalleryState() {
            return this.tab === 'galleries' && !this.hasGalleries;
        },

        get hasActiveMediaQuery() {
            return !!this.searchText || !!this.mediaTypeFilter;
        },

        get showFilterEmptyState() {
            return !this.loadingPane
                && !this.showNoGalleryState
                && this.filteredItems().length === 0
                && this.hasActiveMediaQuery;
        },

        get statusSummary() {
            if (this.tab === 'galleries') {
                const total = this.mediaItems.length;
                return `${total} item${total === 1 ? '' : 's'} in gallery`;
            }

            const folderCount = this.currentSubfolders().length;
            const mediaCount = this.mediaItems.length;

            return `${folderCount} folder${folderCount === 1 ? '' : 's'} · ${mediaCount} media`;
        },

        get rootDirectItemCount() {
            return this.libraryMediaItems.filter((item) => !item.folder_id).length;
        },

        get rootDisplayCount() {
            return this.rootExpanded ? this.rootDirectItemCount : this.libraryMediaItems.length;
        },

        get selectedItem() {
            return this.mediaItems.find((item) => Number(item.id) === Number(this.selectedId)) || null;
        },

        get lightboxItem() {
            const lightboxId = Number(this.lightbox.itemId);
            return this.filteredItems().find((item) => Number(item.id) === lightboxId)
                || this.mediaItems.find((item) => Number(item.id) === lightboxId)
                || null;
        },

        get lightboxItems() {
            return this.filteredItems().filter((item) => this.isPreviewableMedia(item));
        },

        get currentLightboxIndex() {
            return this.lightboxItems.findIndex((item) => Number(item.id) === Number(this.lightbox.itemId));
        },

        get selectedItemSummary() {
            if (!this.selectedItem) {
                return this.checkedIds.length
                    ? `${this.checkedIds.length} item${this.checkedIds.length === 1 ? '' : 's'} checked`
                    : 'No item selected';
            }

            const item = this.selectedItem;
            const fallbackName = item.original_name || item.file_name || 'Media item';
            const baseDisplayName = item.title || fallbackName;
            const extension = String(item.file_name || item.original_name || '')
                .split('.')
                .pop()
                ?.trim()
                ?.toLowerCase();
            const normalizedBaseName = String(baseDisplayName).trim();
            const displayName = extension
                && normalizedBaseName
                && !normalizedBaseName.toLowerCase().endsWith(`.${extension}`)
                ? `${normalizedBaseName}.${extension}`
                : normalizedBaseName;

            const parts = [
                displayName || 'Media item',
                item.media_type ? String(item.media_type).charAt(0).toUpperCase() + String(item.media_type).slice(1) : '',
            ];

            if (item.width && item.height) {
                parts.push(`${item.width}×${item.height}`);
            }

            if (item.file_size) {
                parts.push(`${Math.max(1, Math.round(item.file_size / 1024))} KB`);
            }

            return parts.filter(Boolean).join(' · ');
        },

        get thumbPixels() {
            if (this.thumb === 'sm') return 96;
            if (this.thumb === 'lg') return 160;
            return 128;
        },

        get thumbStyle() {
            return `width:${this.thumbPixels}px;height:${this.thumbPixels}px;`;
        },

        get gridStyle() {
            return `grid-template-columns: repeat(auto-fill, minmax(${this.thumbPixels + 16}px, 1fr));`;
        },

        get itemCardStyle() {
            return `width:${this.thumbPixels + 16}px;`;
        },

        get titleBlockStyle() {
            return `width:${Math.max(this.thumbPixels - 2, 0)}px;`;
        },

        isGalleryEligibleMedia(item) {
            return ['image', 'video'].includes(String(item?.media_type || '').toLowerCase());
        },

        isPreviewableMedia(item) {
            if (!item) {
                return false;
            }

            const mediaType = String(item.media_type || '').toLowerCase();
            if (mediaType === 'image' || mediaType === 'video') {
                return !!(item.full_url || item.public_url);
            }

            if (mediaType === 'document') {
                const mimeType = String(item.mime_type || '').toLowerCase();
                const source = String(item.full_url || item.public_url || item.original_name || item.file_name || '').toLowerCase();
                return mimeType === 'application/pdf' || source.endsWith('.pdf');
            }

            return false;
        },

        lightboxDisplayTitle(item) {
            if (!item) {
                return 'Media item';
            }

            return item.title || item.original_name || item.file_name || 'Media item';
        },

        lightboxDescription(item) {
            return item.description || item.caption || item.gallery_caption_override || '';
        },

        openLightbox(item) {
            if (!this.isPreviewableMedia(item)) {
                return;
            }

            this.lightbox = {
                open: true,
                itemId: Number(item.id),
                zoom: 1,
                panX: 0,
                panY: 0,
                panning: false,
                startX: 0,
                startY: 0,
                originPanX: 0,
                originPanY: 0,
                fitWidth: null,
                fitHeight: null,
            };
            this.selectItem(item.id);
            this.scheduleLightboxFit();
        },

        closeLightbox() {
            this.lightbox.open = false;
            this.lightbox.itemId = null;
            this.lightbox.zoom = 1;
            this.lightbox.panX = 0;
            this.lightbox.panY = 0;
            this.lightbox.panning = false;
            this.lightbox.fitWidth = null;
            this.lightbox.fitHeight = null;
        },

        setLightboxItem(item) {
            if (!item) {
                return;
            }

            this.lightbox.itemId = Number(item.id);
            this.lightbox.zoom = 1;
            this.lightbox.panX = 0;
            this.lightbox.panY = 0;
            this.lightbox.panning = false;
            this.selectItem(item.id);
            this.scheduleLightboxFit();
        },

        scheduleLightboxFit(delay = 24) {
            if (!this.lightbox.open) {
                return;
            }

            window.clearTimeout(this._lightboxFitTimer);
            this._lightboxFitTimer = window.setTimeout(() => {
                requestAnimationFrame(() => this.updateLightboxImageFit());
            }, delay);
        },

        handleLightboxImageLoad() {
            this.scheduleLightboxFit(0);
        },

        updateLightboxImageFit() {
            if (!this.lightbox.open || String(this.lightboxItem?.media_type || '').toLowerCase() !== 'image') {
                return;
            }

            const canvas = this.$refs.lightboxCanvas;
            const image = this.$refs.lightboxImage;

            if (!canvas || !image || !image.naturalWidth || !image.naturalHeight) {
                return;
            }

            const canvasWidth = Math.max(0, canvas.clientWidth);
            const canvasHeight = Math.max(0, canvas.clientHeight);

            if (!canvasWidth || !canvasHeight) {
                return;
            }

            const widthRatio = canvasWidth / image.naturalWidth;
            const heightRatio = canvasHeight / image.naturalHeight;
            const fitScale = Math.min(widthRatio, heightRatio);

            this.lightbox.fitWidth = Math.max(1, Math.floor(image.naturalWidth * fitScale));
            this.lightbox.fitHeight = Math.max(1, Math.floor(image.naturalHeight * fitScale));
        },

        showNextLightboxItem() {
            if (!this.lightboxItems.length) {
                return;
            }

            const nextIndex = this.currentLightboxIndex < 0
                ? 0
                : (this.currentLightboxIndex + 1) % this.lightboxItems.length;

            this.setLightboxItem(this.lightboxItems[nextIndex]);
        },

        showPreviousLightboxItem() {
            if (!this.lightboxItems.length) {
                return;
            }

            const previousIndex = this.currentLightboxIndex < 0
                ? 0
                : (this.currentLightboxIndex - 1 + this.lightboxItems.length) % this.lightboxItems.length;

            this.setLightboxItem(this.lightboxItems[previousIndex]);
        },

        adjustLightboxZoom(delta) {
            if (String(this.lightboxItem?.media_type || '').toLowerCase() !== 'image') {
                return;
            }

            this.lightbox.zoom = Math.max(1, Math.min(4, Number((this.lightbox.zoom + delta).toFixed(2))));

            if (this.lightbox.zoom === 1) {
                this.lightbox.panX = 0;
                this.lightbox.panY = 0;
            }
        },

        resetLightboxZoom() {
            this.lightbox.zoom = 1;
            this.lightbox.panX = 0;
            this.lightbox.panY = 0;
        },

        handleLightboxWheel(event) {
            if (!this.lightbox.open || String(this.lightboxItem?.media_type || '').toLowerCase() !== 'image') {
                return;
            }

            event.preventDefault();
            this.adjustLightboxZoom(event.deltaY < 0 ? 0.2 : -0.2);
        },

        startLightboxPan(event) {
            if (!this.lightbox.open || String(this.lightboxItem?.media_type || '').toLowerCase() !== 'image' || this.lightbox.zoom <= 1) {
                return;
            }

            this.lightbox.panning = true;
            this.lightbox.startX = event.clientX;
            this.lightbox.startY = event.clientY;
            this.lightbox.originPanX = this.lightbox.panX || 0;
            this.lightbox.originPanY = this.lightbox.panY || 0;
        },

        moveLightboxPan(event) {
            if (!this.lightbox.panning) {
                return;
            }

            this.lightbox.panX = this.lightbox.originPanX + (event.clientX - this.lightbox.startX);
            this.lightbox.panY = this.lightbox.originPanY + (event.clientY - this.lightbox.startY);
        },

        endLightboxPan() {
            this.lightbox.panning = false;
        },

        folderLabel(folder) {
            return `${'— '.repeat(Math.max(0, Number(folder.depth || 0) + 1))}${folder.folder_name}`;
        },

        contextFolderLabel(folder) {
            const depth = Math.max(0, Number(folder.depth || 0));
            return `${'– '.repeat(depth)}${folder.folder_name}`;
        },

        getFolderById(folderId) {
            return this.foldersFlat.find((folder) => Number(folder.id) === Number(folderId)) || null;
        },

        getFolderDepth(folderId) {
            return Number(this.getFolderById(folderId)?.depth || 0);
        },

        getCreateFolderParentId() {
            if (!this.currentFolderId) {
                return '';
            }

            const currentFolder = this.getFolderById(this.currentFolderId);
            if (!currentFolder) {
                return '';
            }

            return Number(currentFolder.depth || 0) >= 4
                ? (currentFolder.parent_id || '')
                : Number(currentFolder.id);
        },

        hasFolderChildren(folderId) {
            return this.folderChildrenOf(folderId).length > 0;
        },

        isFolderExpanded(folderId) {
            return this.expandedFolderIds.includes(Number(folderId));
        },

        toggleFolderExpanded(folderId) {
            const numericId = Number(folderId);

            if (this.isFolderExpanded(numericId)) {
                this.expandedFolderIds = this.expandedFolderIds.filter((id) => id !== numericId);
                return;
            }

            this.expandedFolderIds.push(numericId);
        },

        ancestorFolderIds(folderId) {
            const ids = [];
            let current = this.getFolderById(folderId);

            while (current?.parent_id) {
                const parentId = Number(current.parent_id);
                ids.unshift(parentId);
                current = this.getFolderById(parentId);
            }

            return ids;
        },

        expandFolderPath(folderId) {
            if (!folderId) return;

            this.ancestorFolderIds(folderId).forEach((id) => {
                if (!this.expandedFolderIds.includes(Number(id))) {
                    this.expandedFolderIds.push(Number(id));
                }
            });
        },

        isFolderVisibleInTree(folder) {
            if (!this.rootExpanded) {
                return false;
            }

            return this.ancestorFolderIds(folder.id).every((id) => this.expandedFolderIds.includes(Number(id)));
        },

        folderChildrenOf(folderId) {
            return this.foldersFlat.filter((folder) => Number(folder.parent_id || 0) === Number(folderId));
        },

        descendantFolderIds(folderId) {
            const descendants = [];
            const stack = this.folderChildrenOf(folderId).map((folder) => Number(folder.id));

            while (stack.length) {
                const currentId = stack.pop();
                descendants.push(currentId);
                this.folderChildrenOf(currentId).forEach((folder) => {
                    stack.push(Number(folder.id));
                });
            }

            return descendants;
        },

        canMoveFolderToTarget(targetFolderId = null) {
            if (!this.draggingFolderId) {
                return false;
            }

            const draggingId = Number(this.draggingFolderId);
            const targetId = targetFolderId === null || targetFolderId === '' ? null : Number(targetFolderId);

            if (targetId !== null && draggingId === targetId) {
                return false;
            }

            if (targetId !== null && this.descendantFolderIds(draggingId).includes(targetId)) {
                return false;
            }

            return true;
        },

        folderMoveOutcome(targetFolderId = null) {
            if (!this.draggingFolderId) {
                return { allowed: false, reason: 'none' };
            }

            const draggingId = Number(this.draggingFolderId);
            const draggingFolder = this.getFolderById(draggingId);
            const targetId = targetFolderId === null || targetFolderId === '' ? null : Number(targetFolderId);

            if (targetId !== null && draggingId === targetId) {
                return { allowed: false, reason: 'self' };
            }

            const currentParentId = draggingFolder?.parent_id ? Number(draggingFolder.parent_id) : null;

            if (currentParentId === targetId) {
                return { allowed: false, reason: 'same-parent' };
            }

            if (targetId !== null && this.descendantFolderIds(draggingId).includes(targetId)) {
                return { allowed: false, reason: 'descendant' };
            }

            const targetFolder = targetId !== null ? this.getFolderById(targetId) : null;
            const nextDepth = targetFolder ? Number(targetFolder.depth || 0) + 1 : 0;

            if (nextDepth > 4) {
                return { allowed: false, reason: 'max-depth' };
            }

            return { allowed: true, reason: null };
        },

        visibleItemIds() {
            return this.mediaItems.map((item) => Number(item.id));
        },

        currentSubfolders() {
            if (this.tab !== 'folders') {
                return [];
            }

            const parentId = this.currentFolderId ? Number(this.currentFolderId) : null;

            return this.foldersFlatNatural.filter((folder) => {
                const folderParentId = folder.parent_id ? Number(folder.parent_id) : null;
                return folderParentId === parentId;
            });
        },

        sortFolderTreeLocal(tree, sortBy) {
            const nodes = Array.isArray(tree) ? tree : [];
            const sorted = nodes.map((node) => ({
                ...node,
                children_tree: this.sortFolderTreeLocal(node.children_tree || [], sortBy),
            }));

            const compareName = (a, b) => String(a.folder_name || '').toLowerCase().localeCompare(String(b.folder_name || '').toLowerCase());
            const compareCreated = (a, b) => (Date.parse(b.created_at || '') || 0) - (Date.parse(a.created_at || '') || 0);
            const compareUpdated = (a, b) => (Date.parse(b.updated_at || '') || 0) - (Date.parse(a.updated_at || '') || 0);

            if (sortBy === 'newest') {
                return sorted.sort(compareCreated);
            }

            if (sortBy === 'updated') {
                return sorted.sort(compareUpdated);
            }

            return sorted.sort(compareName);
        },

        flattenFolderTreeLocal(tree) {
            const flattened = [];

            const walk = (nodes = [], depth = 0) => {
                nodes.forEach((node) => {
                    flattened.push({
                        ...node,
                        depth,
                    });

                    if (Array.isArray(node.children_tree) && node.children_tree.length) {
                        walk(node.children_tree, depth + 1);
                    }
                });
            };

            walk(Array.isArray(tree) ? tree : [], 0);
            return flattened;
        },

        folderCardMeta(folder) {
            const childFolderCount = Number(folder.children_count || 0);
            const directMediaCount = Number(folder.media_items_count || 0);
            const parts = [];

            if (childFolderCount > 0) {
                parts.push(`${childFolderCount} folder${childFolderCount === 1 ? '' : 's'}`);
            }

            parts.push(`${directMediaCount} media`);

            return parts.join(' · ');
        },

        folderTreeCount(folder) {
            return this.isFolderExpanded(folder.id)
                ? Number(folder.media_items_count || 0)
                : Number(folder.total_media_items_count || 0);
        },

        filteredItems() {
            const filtered = this.mediaItems.filter((item) => {
                const matchesType = !this.mediaTypeFilter || item.media_type === this.mediaTypeFilter;
                const haystack = [
                    item.title,
                    item.original_name,
                    item.caption,
                    item.description,
                    item.gallery_caption_override,
                ].filter(Boolean).join(' ').toLowerCase();
                const matchesSearch = !this.searchText || haystack.includes(this.searchText.toLowerCase());
                return matchesType && matchesSearch;
            });

            if (this.mediaSort === 'name') {
                return filtered.sort((a, b) => String(a.title || a.original_name || '').localeCompare(String(b.title || b.original_name || '')));
            }

            return filtered.sort((a, b) => {
                const aTime = Date.parse(a.updated_at || a.created_at || 0) || 0;
                const bTime = Date.parse(b.updated_at || b.created_at || 0) || 0;
                return bTime - aTime;
            });
        },

        async fetchWorkspace(extra = {}) {
            this.closeContextMenu();
            const params = new URLSearchParams();
            const state = {
                tab: extra.tab ?? this.tab,
                folder_id: extra.folder_id ?? (extra.tab === 'galleries' ? null : this.currentFolderId),
                folder_sort: extra.folder_sort ?? this.folderSort,
                gallery_id: extra.gallery_id ?? (extra.tab === 'folders' ? null : this.activeGalleryId),
                gallery_sort: extra.gallery_sort ?? this.gallerySort,
                media_sort: extra.media_sort ?? this.mediaSort,
                thumb: extra.thumb ?? this.thumb,
            };

            Object.entries(state).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    params.set(key, value);
                }
            });

            const response = await fetch(`${this.routes.refresh}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Failed to load media workspace.');
            }

            this.applyPayload(data.data || {});
            this.syncUrl();
        },

        syncUrl() {
            const params = new URLSearchParams();
            params.set('tab', this.tab);
            if (this.tab === 'folders' && this.currentFolderId) params.set('folder_id', this.currentFolderId);
            if (this.folderSort) params.set('folder_sort', this.folderSort);
            if (this.tab === 'galleries' && this.activeGalleryId) params.set('gallery_id', this.activeGalleryId);
            if (this.tab === 'galleries' && this.gallerySort) params.set('gallery_sort', this.gallerySort);
            if (this.mediaSort) params.set('media_sort', this.mediaSort);
            if (this.thumb) params.set('thumb', this.thumb);
            const next = `${this.routes.refresh}?${params.toString()}`;
            window.history.replaceState({}, '', next);
        },

        async send(url, method = 'POST', payload = {}, options = {}) {
            const formData = options.formData || new FormData();

            if (!options.formData) {
                Object.entries(payload || {}).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        value.forEach((nested) => formData.append(`${key}[]`, nested));
                    } else if (value !== undefined && value !== null) {
                        formData.append(key, value);
                    }
                });
            }

            if (method !== 'POST') {
                formData.append('_method', method);
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Request failed.');
            }

            return data;
        },

        setStatusMessage(message, tone = 'muted', timeout = 2800) {
            this.statusMessage = message;
            this.statusTone = tone;

            if (this.statusTimer) {
                window.clearTimeout(this.statusTimer);
            }

            if (timeout > 0) {
                this.statusTimer = window.setTimeout(() => {
                    this.statusMessage = '';
                    this.statusTone = 'muted';
                }, timeout);
            }
        },

        notify(message, type = 'success', options = {}) {
            const toast = options.toast ?? (type === 'error');
            const statusTone = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success');
            const displayedMessage = type === 'error'
                ? (options.statusMessage || 'An error occurred')
                : message;
            const toastMessage = type === 'error'
                ? (options.toastMessage || displayedMessage)
                : message;

            this.setStatusMessage(displayedMessage, statusTone, options.timeout ?? 2800);

            if (!toast) {
                return;
            }

            if (window.toastNotifier?.show) {
                window.toastNotifier.show({ message: toastMessage, type });
                return;
            }

            if (type === 'error') {
                window.alert(toastMessage);
            }
        },

        replaceId(url, id) {
            return String(url).replace('__ID__', id);
        },

        focusAndScroll(getElement) {
            const applyFocus = () => {
                const element = getElement();
                if (!element) return false;

                const scrollTarget = element.closest('[data-scroll-target]') || element;
                scrollTarget.scrollIntoView({ block: 'nearest', inline: 'nearest' });

                if (typeof element.focus === 'function') {
                    element.focus({ preventScroll: true });
                }

                if (typeof element.setSelectionRange === 'function') {
                    const length = element.value?.length ?? 0;
                    element.setSelectionRange(length, length);
                }

                return true;
            };

            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    if (applyFocus()) return;
                    window.setTimeout(() => {
                        if (applyFocus()) return;
                        window.setTimeout(applyFocus, 80);
                    }, 24);
                });
            });
        },

        closeContextMenu() {
            this.contextMenu = { open: false, x: 0, y: 0, type: null, target: null, submenu: null, submenuSide: 'right', submenuTop: 0 };
        },

        openContextMenu(event, type, target) {
            const menuWidth = 212;
            const itemCount = type === 'media' ? (this.tab === 'galleries' ? 6 : 5) : 3;
            const menuHeight = 16 + (itemCount * 42);
            const padding = 12;
            const x = Math.min(event.clientX, window.innerWidth - menuWidth - padding);
            const y = Math.min(event.clientY, window.innerHeight - menuHeight - padding);

            this.contextMenu = {
                open: true,
                x: Math.max(padding, x),
                y: Math.max(padding, y),
                type,
                target,
                submenu: null,
                submenuSide: 'right',
                submenuTop: 0,
            };
        },

        get contextSubmenuStyle() {
            const gap = -2;
            const base = {
                top: `${this.contextMenu.submenuTop || 0}px`,
                left: this.contextMenu.submenuSide === 'left' ? 'auto' : `calc(100% + ${gap}px)`,
                right: this.contextMenu.submenuSide === 'left' ? `calc(100% + ${gap}px)` : 'auto',
                maxHeight: 'min(500px, calc(100vh - 24px))',
            };

            return Object.entries(base).map(([key, value]) => `${key}:${value}`).join(';');
        },

        switchTab(tab) {
            if (tab === this.tab) return;
            this.tab = tab;
            this.checkedIds = [];
            this.selectedId = null;
            this.loadingPane = true;
            if (tab === 'folders') {
                this.activeGalleryId = null;
                this.currentFolderId = null;
            } else {
                this.currentFolderId = null;
                this.activeGalleryId = this.galleries[0]?.id || null;
            }
            this.syncUrl();
            this.fetchWorkspace({
                tab,
                folder_id: tab === 'folders' ? null : undefined,
                folder_sort: this.folderSort,
                gallery_id: tab === 'galleries' ? (this.galleries[0]?.id || null) : null,
            }).catch((error) => {
                this.loadingPane = false;
                this.notify(error.message, 'error');
            });
        },

        selectFolder(folderId) {
            if (this.tab !== 'folders' || Number(this.currentFolderId) !== Number(folderId)) {
                this.tab = 'folders';
                this.currentFolderId = folderId ? Number(folderId) : null;
                this.expandFolderPath(this.currentFolderId);
                this.activeGalleryId = null;
                this.checkedIds = [];
                this.selectedId = null;
                this.loadingPane = true;
                this.syncUrl();
                this.fetchWorkspace({ tab: 'folders', folder_id: folderId, gallery_id: null })
                    .catch((error) => {
                        this.loadingPane = false;
                        this.notify(error.message, 'error');
                });
            }
        },

        goUpFolder() {
            if (!this.currentFolderId) {
                return;
            }

            const parentId = this.currentFolder?.parent_id ? Number(this.currentFolder.parent_id) : null;
            this.selectFolder(parentId);
        },

        selectGallery(galleryId) {
            if (this.tab !== 'galleries' || Number(this.activeGalleryId) !== Number(galleryId)) {
                this.tab = 'galleries';
                this.activeGalleryId = galleryId ? Number(galleryId) : null;
                this.currentFolderId = null;
                this.checkedIds = [];
                this.selectedId = null;
                this.loadingPane = true;
                this.syncUrl();
                this.fetchWorkspace({ tab: 'galleries', gallery_id: galleryId })
                    .catch((error) => {
                        this.loadingPane = false;
                        this.notify(error.message, 'error');
                    });
            }
        },

        setGallerySort(sort) {
            this.gallerySort = sort;
            this.loadingPane = true;
            this.syncUrl();
            this.fetchWorkspace({ tab: 'galleries', gallery_sort: sort, gallery_id: this.activeGalleryId || this.galleries[0]?.id || null })
                .catch((error) => {
                    this.loadingPane = false;
                    this.notify(error.message, 'error');
                });
        },

        setFolderSort(sort) {
            this.folderSort = sort;
            this.syncUrl();
            this.folderTree = this.sortFolderTreeLocal(this.folderTree, sort);
            this.foldersFlat = this.flattenFolderTreeLocal(this.folderTree);
            this.expandFolderPath(this.currentFolderId);
        },

        setMediaSort(sort) {
            this.mediaSort = sort;
            this.syncUrl();
        },

        setThumb(size) {
            this.thumb = size;
            this.syncUrl();
        },

        selectItem(itemId) {
            this.selectedId = Number(itemId);
        },

        toggleChecked(itemId) {
            const value = Number(itemId);
            if (this.checkedIds.includes(value)) {
                this.checkedIds = this.checkedIds.filter((id) => id !== value);
                return;
            }
            this.checkedIds.push(value);
        },

        selectAllVisible() {
            this.checkedIds = this.filteredItems().map((item) => Number(item.id));
        },

        clearChecked() {
            this.checkedIds = [];
        },

        startRenameMedia(item) {
            this.cancelRename();
            this.renamingMediaId = item.id;
            this.renameDraftTitle = item.title || item.original_name || '';
            this.focusMediaRenameInput(item.id);
        },

        focusMediaRenameInput(itemId) {
            this.focusAndScroll(() => this.$root?.querySelector(`[data-media-rename-id="${itemId}"]`));
        },

        async saveMediaRename(item) {
            if (this.renamingMediaId !== item.id) return;
            const nextTitle = this.renameDraftTitle.trim();
            this.renamingMediaId = null;
            if (!nextTitle || nextTitle === (item.title || item.original_name || '')) return;

            try {
                const data = await this.send(this.replaceId(this.routes.mediaUpdate, item.id), 'PUT', {
                    title: nextTitle,
                });
                this.notify(data.message || 'Media item updated successfully.', 'success', { toast: false });
                await this.fetchWorkspace();
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        startRenameFolder(folder) {
            this.cancelRename();
            this.renamingFolderId = folder.id;
            this.renameDraftTitle = folder.folder_name || '';
        },

        async saveFolderRename(folder) {
            const nextTitle = this.renameDraftTitle.trim();
            if (!nextTitle) return;

            try {
                const data = await this.send(this.replaceId(this.routes.folderUpdate, folder.id), 'PUT', {
                    folder_name: nextTitle,
                    parent_id: folder.parent_id || '',
                    sort_order: folder.sort_order || 0,
                    slug: folder.slug || '',
                    description: folder.description || '',
                });
                this.notify(data.message || 'Folder updated successfully.', 'success', { toast: false });
                this.cancelRename();
                await this.fetchWorkspace();
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        startRenameGallery(gallery) {
            this.cancelRename();
            this.renamingGalleryId = gallery.id;
            this.renameDraftTitle = gallery.title || '';
        },

        async saveGalleryRename(gallery) {
            const nextTitle = this.renameDraftTitle.trim();
            if (!nextTitle) return;

            try {
                const data = await this.send(this.replaceId(this.routes.galleryUpdate, gallery.id), 'PUT', {
                    title: nextTitle,
                    slug: gallery.slug || '',
                    description: gallery.description || '',
                    excerpt: gallery.excerpt || '',
                    gallery_status: gallery.gallery_status || 'Draft',
                    is_featured: gallery.is_featured ? 1 : 0,
                    author: gallery.author || '',
                });
                this.notify(data.message || 'Gallery updated successfully.', 'success', { toast: false });
                this.cancelRename();
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: gallery.id });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        async setGalleryPublished(gallery, publish = true) {
            if (!gallery?.id) return;

            try {
                const data = await this.send(this.replaceId(this.routes.galleryUpdate, gallery.id), 'PUT', {
                    title: gallery.title || '',
                    slug: gallery.slug || '',
                    description: gallery.description || '',
                    excerpt: gallery.excerpt || '',
                    gallery_status: publish ? 'Published' : 'Draft',
                    is_featured: gallery.is_featured ? 1 : 0,
                    author: gallery.author || '',
                    published_at: publish ? (gallery.published_at || new Date().toISOString()) : '',
                });
                this.notify(data.message || (publish ? 'Gallery published successfully.' : 'Gallery unpublished successfully.'), 'success', { toast: false });
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: gallery.id });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        cancelRename() {
            this.renamingMediaId = null;
            this.renamingFolderId = null;
            this.renamingGalleryId = null;
            this.renameDraftTitle = '';
        },

        startCreateFolder() {
            this.creatingGallery = false;
            this.creatingFolder = true;
            this.creatingFolderBusy = false;
            this.createFolderTitle = '';
            this.createFolderParentId = this.getCreateFolderParentId();
            this.focusAndScroll(() => this.$refs.folderCreateInput);
        },

        cancelCreateFolder() {
            this.creatingFolder = false;
            this.creatingFolderBusy = false;
            this.createFolderTitle = '';
        },

        async commitCreateFolder() {
            const title = this.createFolderTitle.trim();
            if (!title || this.creatingFolderBusy) return;
            this.creatingFolderBusy = true;

            try {
                const data = await this.send(this.routes.folderStore, 'POST', {
                    folder_name: title,
                    parent_id: this.createFolderParentId || '',
                });
                this.notify(data.message || 'Folder created successfully.', 'success', { toast: false });
                this.cancelCreateFolder();
                await this.fetchWorkspace({ tab: 'folders' });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.creatingFolderBusy = false;
            }
        },

        startCreateGallery() {
            this.creatingFolder = false;
            this.creatingGallery = true;
            this.creatingGalleryBusy = false;
            this.createGalleryTitle = '';
            this.focusAndScroll(() => this.$refs.galleryCreateInput);
        },

        cancelCreateGallery() {
            this.creatingGallery = false;
            this.creatingGalleryBusy = false;
            this.createGalleryTitle = '';
        },

        async commitCreateGallery() {
            const title = this.createGalleryTitle.trim();
            if (!title || this.creatingGalleryBusy) return;
            this.creatingGalleryBusy = true;

            try {
                const data = await this.send(this.routes.galleryStore, 'POST', {
                    title,
                    gallery_status: 'Draft',
                });
                this.notify(data.message || 'Gallery created successfully.', 'success', { toast: false });
                const galleryId = data.meta?.gallery_id || data.data?.id || null;
                this.cancelCreateGallery();
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: galleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.creatingGalleryBusy = false;
            }
        },

        openUploadModal() {
            this.uploadModal.open = true;
            this.uploadModal.folder_id = this.currentFolderId || '';
            this.uploadModal.files = [];
            this.uploadModal.dragActive = false;
            requestAnimationFrame(() => {
                if (this.$refs.uploadFileInput) {
                    this.$refs.uploadFileInput.value = '';
                }
            });
        },

        closeUploadModal() {
            this.uploadModal.open = false;
            this.uploadModal.dragActive = false;
            this.uploadModal.files = [];
            if (this.$refs.uploadFileInput) {
                this.$refs.uploadFileInput.value = '';
            }
        },

        openUploadFilePicker() {
            if (this.uploadModal.busy) return;
            this.$refs.uploadFileInput?.click();
        },

        handleUploadFiles(event) {
            this.mergeUploadFiles(Array.from(event.target.files || []));
            if (this.$refs.uploadFileInput) {
                this.$refs.uploadFileInput.value = '';
            }
        },

        handleUploadDragOver(event) {
            event.preventDefault();
            if (this.uploadModal.busy) return;
            this.uploadModal.dragActive = true;
        },

        handleUploadDragLeave(event) {
            if (event.currentTarget?.contains(event.relatedTarget)) {
                return;
            }
            this.uploadModal.dragActive = false;
        },

        handleUploadDrop(event) {
            event.preventDefault();
            this.uploadModal.dragActive = false;
            if (this.uploadModal.busy) return;
            this.mergeUploadFiles(Array.from(event.dataTransfer?.files || []));
        },

        mergeUploadFiles(files = []) {
            const existing = new Map(
                this.uploadModal.files.map((file) => [`${file.name}::${file.size}::${file.lastModified}`, file]),
            );

            files.forEach((file) => {
                if (!(file instanceof File)) return;
                existing.set(`${file.name}::${file.size}::${file.lastModified}`, file);
            });

            this.uploadModal.files = Array.from(existing.values());
        },

        removeUploadFile(index) {
            this.uploadModal.files.splice(index, 1);
        },

        clearUploadFiles() {
            this.uploadModal.files = [];
            if (this.$refs.uploadFileInput) {
                this.$refs.uploadFileInput.value = '';
            }
        },

        formatUploadFileSize(bytes) {
            const size = Number(bytes || 0);
            if (!size) return '0 B';
            if (size < 1024) return `${size} B`;
            if (size < 1024 * 1024) return `${Math.round(size / 102.4) / 10} KB`;
            return `${Math.round(size / (1024 * 102.4)) / 10} MB`;
        },

        async submitUpload() {
            if (!this.uploadModal.files.length) return;
            this.uploadModal.busy = true;

            const formData = new FormData();
            this.uploadModal.files.forEach((file) => formData.append('files[]', file));
            if (this.uploadModal.folder_id) formData.append('folder_id', this.uploadModal.folder_id);
            formData.append('storage_context', 'gallery');

            try {
                const data = await this.send(this.routes.upload, 'POST', {}, { formData });
                this.notify(data.message || 'Upload completed.');
                this.closeUploadModal();
                await this.fetchWorkspace();
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.uploadModal.busy = false;
            }
        },

        openFolderProperties(folder) {
            this.folderProperties = {
                open: true,
                busy: false,
                form: {
                    id: folder.id,
                    folder_name: folder.folder_name || '',
                    parent_id: folder.parent_id || '',
                    slug: folder.slug || '',
                    description: folder.description || '',
                },
            };
        },

        closeFolderProperties() {
            this.folderProperties.open = false;
        },

        availableParentFolders(currentId) {
            return this.foldersFlat.filter((folder) => Number(folder.id) !== Number(currentId));
        },

        async saveFolderProperties() {
            const form = this.folderProperties.form;
            this.folderProperties.busy = true;
            try {
                const data = await this.send(this.replaceId(this.routes.folderUpdate, form.id), 'PUT', form);
                this.notify(data.message || 'Folder updated successfully.', 'success', { toast: false });
                this.closeFolderProperties();
                await this.fetchWorkspace({ tab: 'folders', folder_id: this.currentFolderId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.folderProperties.busy = false;
            }
        },

        openGalleryProperties(gallery) {
            this.galleryProperties = {
                open: true,
                busy: false,
                form: {
                    id: gallery.id,
                    title: gallery.title || '',
                    slug: gallery.slug || '',
                    description: gallery.description || '',
                    excerpt: gallery.excerpt || '',
                    gallery_status: gallery.gallery_status || 'Draft',
                    is_featured: !!gallery.is_featured,
                    author: gallery.author || '',
                },
            };
        },

        closeGalleryProperties() {
            this.galleryProperties.open = false;
        },

        async saveGalleryProperties() {
            const form = this.galleryProperties.form;
            this.galleryProperties.busy = true;

            try {
                const data = await this.send(this.replaceId(this.routes.galleryUpdate, form.id), 'PUT', {
                    ...form,
                    is_featured: form.is_featured ? 1 : 0,
                });
                this.notify(data.message || 'Gallery updated successfully.', 'success', { toast: false });
                this.closeGalleryProperties();
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: form.id });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.galleryProperties.busy = false;
            }
        },

        openMediaProperties(item) {
            this.mediaProperties = {
                open: true,
                surface: 'pane',
                busy: false,
                form: {
                    id: item.id,
                    title: item.title || '',
                    folder_id: item.folder_id || '',
                    alt_text: item.alt_text || '',
                    caption: item.caption || '',
                    description: item.description || '',
                    preview_url: item.full_url || item.public_url || '',
                    media_type: item.media_type || 'image',
                    original_name: item.original_name || '',
                    mime_type: item.mime_type || '',
                },
            };
            this.selectItem(item.id);
        },

        closeMediaProperties() {
            this.mediaProperties.open = false;
            this.mediaProperties.surface = null;
        },

        async saveMediaProperties() {
            const form = this.mediaProperties.form;
            this.mediaProperties.busy = true;

            try {
                const data = await this.send(this.replaceId(this.routes.mediaUpdate, form.id), 'PUT', {
                    title: form.title,
                    folder_id: form.folder_id,
                    alt_text: form.alt_text,
                    caption: form.caption,
                    description: form.description,
                });
                this.notify(data.message || 'Media updated successfully.', 'success', { toast: false });
                this.closeMediaProperties();
                await this.fetchWorkspace({ tab: this.tab, folder_id: this.currentFolderId, gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.mediaProperties.busy = false;
            }
        },

        openFolderDeleteModal(folder) {
            this.folderDeleteModal = {
                open: true,
                busy: false,
                folder,
                content_strategy: 'keep',
            };
        },

        closeFolderDeleteModal() {
            this.folderDeleteModal.open = false;
        },

        folderHasManagedContents(folder) {
            if (!folder) {
                return false;
            }

            return Number(folder.children_count || 0) > 0 || Number(folder.media_items_count || 0) > 0;
        },

        async confirmDeleteFolder() {
            const folder = this.folderDeleteModal.folder;
            if (!folder) return;

            this.folderDeleteModal.busy = true;
            try {
                const data = await this.send(this.replaceId(this.routes.folderDelete, folder.id), 'DELETE', {
                    content_strategy: this.folderHasManagedContents(folder)
                        ? this.folderDeleteModal.content_strategy
                        : 'keep',
                });
                this.notify(data.message || 'Folder deleted successfully.');
                this.closeFolderDeleteModal();
                if (Number(this.currentFolderId) === Number(folder.id)) {
                    this.currentFolderId = null;
                }
                await this.fetchWorkspace({ tab: 'folders', folder_id: this.currentFolderId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.folderDeleteModal.busy = false;
            }
        },

        async deleteGallery(gallery) {
            const confirmed = window.modalNotifier?.confirm
                ? await window.modalNotifier.confirm('Delete this gallery?', { confirmLabel: 'Delete', confirmVariant: 'error' })
                : window.confirm('Delete this gallery?');

            if (!confirmed) return;

            try {
                const data = await this.send(this.replaceId(this.routes.galleryDelete, gallery.id), 'DELETE');
                this.notify(data.message || 'Gallery deleted successfully.');
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: null });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        async deleteMedia(item) {
            const confirmed = window.modalNotifier?.confirm
                ? await window.modalNotifier.confirm('Delete this media item?', { confirmLabel: 'Delete', confirmVariant: 'error' })
                : window.confirm('Delete this media item?');

            if (!confirmed) return;

            try {
                const data = await this.send(this.replaceId(this.routes.mediaDelete, item.id), 'DELETE');
                this.notify(data.message || 'Media item deleted successfully.');
                await this.fetchWorkspace({ tab: this.tab, folder_id: this.currentFolderId, gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        downloadMedia(item) {
            if (!item?.id || !this.routes?.mediaDownload) {
                this.notify('Download unavailable for this media item.', 'error');
                return;
            }

            window.location.href = this.replaceId(this.routes.mediaDownload, item.id);
        },

        async confirmDeleteChecked() {
            if (!this.checkedIds.length) return;

            const confirmed = window.modalNotifier?.confirm
                ? await window.modalNotifier.confirm(`Delete ${this.checkedIds.length} selected item(s)?`, { confirmLabel: 'Delete', confirmVariant: 'error' })
                : window.confirm(`Delete ${this.checkedIds.length} selected item(s)?`);

            if (!confirmed) return;

            try {
                await Promise.all(this.checkedIds.map((id) => this.send(this.replaceId(this.routes.mediaDelete, id), 'DELETE')));
                this.notify('Selected media deleted successfully.');
                this.checkedIds = [];
                await this.fetchWorkspace({ tab: this.tab, folder_id: this.currentFolderId, gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        openAddToGalleryModal(mediaIds) {
            const uniqueIds = [...new Set(mediaIds.map((id) => Number(id)))];
            const eligibleIds = uniqueIds
                .filter((id) => this.isGalleryEligibleMedia(
                    this.mediaItems.find((item) => Number(item.id) === id)
                    || this.libraryMediaItems.find((item) => Number(item.id) === id)
                ));

            if (!eligibleIds.length) {
                this.notify('Only images and videos can be added to galleries.', 'warning', { toast: false });
                return;
            }

            if (eligibleIds.length !== uniqueIds.length) {
                this.notify('Only images and videos can be added to galleries. Other selected items were skipped.', 'warning', { toast: false });
            }

            this.addToGalleryModal = {
                open: true,
                busy: false,
                search: '',
                gallery_id: this.activeGalleryId || '',
                mediaIds: eligibleIds,
            };
        },

        closeAddToGalleryModal() {
            this.addToGalleryModal.open = false;
        },

        filteredAddToGalleryTargets() {
            const term = this.addToGalleryModal.search.toLowerCase();
            return this.galleries.filter((gallery) => !term || String(gallery.title || '').toLowerCase().includes(term));
        },

        async confirmAddToGallery() {
            if (!this.addToGalleryModal.gallery_id || !this.addToGalleryModal.mediaIds.length) return;
            this.addToGalleryModal.busy = true;
            try {
                const data = await this.addMediaIdsToGallery(this.addToGalleryModal.mediaIds, this.addToGalleryModal.gallery_id);
                this.notify(data.message || 'Media added to gallery successfully.', 'success', { toast: false });
                this.closeAddToGalleryModal();
                if (Number(this.activeGalleryId) === Number(this.addToGalleryModal.gallery_id) && this.tab === 'galleries') {
                    await this.fetchWorkspace({ tab: 'galleries', gallery_id: this.activeGalleryId });
                }
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.addToGalleryModal.busy = false;
            }
        },

        openLibraryPicker() {
            if (!this.activeGalleryId) return;

            this.libraryPicker = {
                open: true,
                busy: false,
                search: '',
                media_type: '',
                checkedIds: [],
            };
        },

        closeLibraryPicker() {
            this.libraryPicker.open = false;
        },

        filteredLibraryPickerItems() {
            const term = this.libraryPicker.search.toLowerCase();
            return this.libraryMediaItems.filter((item) => {
                if (!this.isGalleryEligibleMedia(item)) {
                    return false;
                }
                const matchesType = !this.libraryPicker.media_type || item.media_type === this.libraryPicker.media_type;
                const haystack = [item.title, item.original_name, item.caption].filter(Boolean).join(' ').toLowerCase();
                const matchesSearch = !term || haystack.includes(term);
                return matchesType && matchesSearch;
            });
        },

        toggleLibraryPickerItem(itemId) {
            const id = Number(itemId);
            if (this.galleryContainsMedia(id)) return;
            if (this.libraryPicker.checkedIds.includes(id)) {
                this.libraryPicker.checkedIds = this.libraryPicker.checkedIds.filter((value) => value !== id);
                return;
            }
            this.libraryPicker.checkedIds.push(id);
        },

        galleryContainsMedia(mediaId) {
            return this.mediaItems.some((item) => Number(item.id) === Number(mediaId));
        },

        async confirmLibraryPicker() {
            if (!this.activeGalleryId || !this.libraryPicker.checkedIds.length) return;
            this.libraryPicker.busy = true;
            try {
                const data = await this.send(this.replaceId(this.routes.galleryAddItems, this.activeGalleryId), 'POST', {
                    media_item_ids: this.libraryPicker.checkedIds,
                });
                this.notify(data.message || 'Media added to gallery successfully.', 'success', { toast: false });
                this.closeLibraryPicker();
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.libraryPicker.busy = false;
            }
        },

        async removeMediaFromCurrentGallery(item) {
            if (!this.activeGalleryId || !item.gallery_item_id) return;

            try {
                const data = await this.send(this.replaceId(this.routes.galleryRemoveItems, this.activeGalleryId), 'POST', {
                    gallery_item_ids: [item.gallery_item_id],
                });
                this.notify(data.message || 'Media removed from gallery.', 'success', { toast: false });
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        async removeCheckedFromCurrentGallery() {
            if (!this.activeGalleryId) return;

            const galleryItemIds = this.mediaItems
                .filter((item) => this.checkedIds.includes(Number(item.id)) && item.gallery_item_id)
                .map((item) => Number(item.gallery_item_id));

            if (!galleryItemIds.length) return;

            try {
                const data = await this.send(this.replaceId(this.routes.galleryRemoveItems, this.activeGalleryId), 'POST', {
                    gallery_item_ids: galleryItemIds,
                });
                this.notify(data.message || 'Selected media removed from gallery.', 'success', { toast: false });
                this.checkedIds = [];
                await this.fetchWorkspace({ tab: 'galleries', gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            }
        },

        startFolderDrag(folder) {
            this.closeContextMenu();
            this.draggingFolderId = Number(folder.id);
            this.dragOverFolderId = null;
            this.dragOverRoot = false;
            this.endMediaDrag();
        },

        endFolderDrag() {
            this.draggingFolderId = null;
            this.clearFolderDropTarget();
        },

        startMediaDrag(item, event = null) {
            if (!item?.id || this.renamingMediaId === item.id) {
                return;
            }

            this.closeContextMenu();
            this.draggingMediaId = Number(item.id);
            this.clearMediaDropTarget();
            this.endFolderDrag();

            if (event?.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(item.id));

                const dragSource = event.currentTarget instanceof HTMLElement
                    ? event.currentTarget
                    : event.target instanceof HTMLElement
                        ? event.target.closest('.wc-media-item')
                        : null;

                if (dragSource && typeof event.dataTransfer.setDragImage === 'function') {
                    const rect = dragSource.getBoundingClientRect();
                    const offsetX = event.clientX ? Math.max(0, Math.min(rect.width, event.clientX - rect.left)) : Math.round(rect.width / 2);
                    const offsetY = event.clientY ? Math.max(0, Math.min(rect.height, event.clientY - rect.top)) : Math.round(rect.height / 2);
                    event.dataTransfer.setDragImage(dragSource, offsetX, offsetY);
                }
            }
        },

        endMediaDrag() {
            this.draggingMediaId = null;
            this.clearMediaDropTarget();
        },

        draggedMediaItem() {
            const mediaId = Number(this.draggingMediaId);
            return this.mediaItems.find((item) => Number(item.id) === mediaId)
                || this.libraryMediaItems.find((item) => Number(item.id) === mediaId)
                || null;
        },

        normalizeFolderTargetId(folderId = null) {
            if (folderId === '' || folderId === null || folderId === undefined) {
                return null;
            }

            return Number(folderId);
        },

        canMoveMediaToFolder(targetFolderId = null) {
            if (!this.draggingMediaId) {
                return false;
            }

            const item = this.draggedMediaItem();
            if (!item) {
                return false;
            }

            const currentFolderId = this.normalizeFolderTargetId(item.folder_id);
            const nextFolderId = this.normalizeFolderTargetId(targetFolderId);

            return currentFolderId !== nextFolderId;
        },

        setMediaFolderDropTarget(folderId) {
            if (!this.canMoveMediaToFolder(folderId)) {
                this.clearMediaDropTarget();
                return;
            }

            this.mediaDragOverFolderId = Number(folderId);
            this.mediaDragOverRoot = false;
            this.mediaDragOverSubfolderId = null;
        },

        setMediaRootDropTarget() {
            if (!this.canMoveMediaToFolder()) {
                this.clearMediaDropTarget();
                return;
            }

            this.mediaDragOverFolderId = null;
            this.mediaDragOverRoot = true;
            this.mediaDragOverSubfolderId = null;
        },

        setMediaSubfolderDropTarget(folderId) {
            if (!this.canMoveMediaToFolder(folderId)) {
                this.clearMediaDropTarget();
                return;
            }

            this.mediaDragOverFolderId = null;
            this.mediaDragOverRoot = false;
            this.mediaDragOverSubfolderId = Number(folderId);
        },

        clearMediaDropTarget() {
            this.mediaDragOverFolderId = null;
            this.mediaDragOverRoot = false;
            this.mediaDragOverSubfolderId = null;
        },

        handleFolderTargetDragOver(folderId = null) {
            if (this.draggingMediaId) {
                if (folderId === null) {
                    this.setMediaRootDropTarget();
                } else {
                    this.setMediaFolderDropTarget(folderId);
                }
                return;
            }

            if (folderId === null) {
                this.setRootDropTarget();
            } else {
                this.setFolderDropTarget(folderId);
            }
        },

        handleFolderTargetDrop(folder = null) {
            if (this.draggingMediaId) {
                if (folder) {
                    this.dropMediaOnFolder(folder);
                } else {
                    this.dropMediaOnRoot();
                }
                return;
            }

            if (folder) {
                this.dropFolderOnFolder(folder);
            } else {
                this.dropFolderOnRoot();
            }
        },

        setFolderDropTarget(folderId) {
            if (!this.folderMoveOutcome(folderId).allowed) {
                this.clearFolderDropTarget();
                return;
            }
            this.dragOverFolderId = Number(folderId);
            this.dragOverRoot = false;
        },

        setRootDropTarget() {
            if (!this.folderMoveOutcome().allowed) {
                this.clearFolderDropTarget();
                return;
            }
            this.dragOverFolderId = null;
            this.dragOverRoot = true;
        },

        clearFolderDropTarget(folderId = null) {
            if (folderId !== null && Number(this.dragOverFolderId) !== Number(folderId)) {
                return;
            }
            this.dragOverFolderId = null;
            this.dragOverRoot = false;
        },

        async dropFolderOnFolder(targetFolder) {
            const outcome = this.folderMoveOutcome(targetFolder.id);
            if (!outcome.allowed) {
                if (outcome.reason === 'descendant') {
                    this.notify('A folder cannot be moved under one of its descendants.', 'warning', { toast: false });
                } else if (outcome.reason === 'max-depth') {
                    this.notify('Folder nesting is limited to four levels from Root.', 'warning', { toast: false });
                }
                this.endFolderDrag();
                return;
            }

            const siblingSortOrders = this.foldersFlat
                .filter((folder) => Number(folder.parent_id || 0) === Number(targetFolder.id))
                .map((folder) => Number(folder.sort_order || 0));

            const nextSortOrder = siblingSortOrders.length ? Math.max(...siblingSortOrders) + 1 : 0;

            try {
                const data = await this.send(this.routes.folderReorder, 'POST', {
                    folders_payload: JSON.stringify([{
                        id: this.draggingFolderId,
                        parent_id: Number(targetFolder.id),
                        sort_order: nextSortOrder,
                    }]),
                });
                this.notify(data.message || 'Folder hierarchy updated successfully.', 'success', { toast: false });
                await this.fetchWorkspace({ tab: 'folders', folder_id: this.currentFolderId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.endFolderDrag();
            }
        },

        async dropFolderOnRoot() {
            const outcome = this.folderMoveOutcome();
            if (!outcome.allowed) {
                this.endFolderDrag();
                return;
            }

            const siblingSortOrders = this.foldersFlat
                .filter((folder) => !folder.parent_id)
                .map((folder) => Number(folder.sort_order || 0));

            const nextSortOrder = siblingSortOrders.length ? Math.max(...siblingSortOrders) + 1 : 0;

            try {
                const data = await this.send(this.routes.folderReorder, 'POST', {
                    folders_payload: JSON.stringify([{
                        id: this.draggingFolderId,
                        parent_id: '',
                        sort_order: nextSortOrder,
                    }]),
                });
                this.notify(data.message || 'Folder hierarchy updated successfully.', 'success', { toast: false });
                await this.fetchWorkspace({ tab: 'folders', folder_id: this.currentFolderId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.endFolderDrag();
            }
        },

        async dropMediaOnFolder(targetFolder) {
            await this.moveDraggedMediaToFolder(targetFolder?.id ?? null);
        },

        async dropMediaOnRoot() {
            await this.moveDraggedMediaToFolder(null);
        },

        async moveDraggedMediaToFolder(targetFolderId = null) {
            const item = this.draggedMediaItem();

            if (!item || !this.canMoveMediaToFolder(targetFolderId)) {
                this.endMediaDrag();
                return;
            }

            const normalizedTarget = this.normalizeFolderTargetId(targetFolderId);

            try {
                const data = await this.send(this.replaceId(this.routes.mediaMove, item.id), 'POST', {
                    folder_id: normalizedTarget ?? '',
                });
                this.notify(data.message || 'Media item moved successfully.', 'success', { toast: false });
                await this.fetchWorkspace({ tab: this.tab, folder_id: this.currentFolderId, gallery_id: this.activeGalleryId });
            } catch (error) {
                this.notify(error.message, 'error');
            } finally {
                this.endMediaDrag();
            }
        },

        moveMediaToFolder(item, folderId) {
            const targetFolderId = folderId === '' ? '' : Number(folderId);

            this.send(this.replaceId(this.routes.mediaMove, item.id), 'POST', {
                folder_id: targetFolderId,
            }).then(async (data) => {
                this.notify(data.message || 'Media item moved successfully.', 'success', { toast: false });
                this.closeContextMenu();
                await this.fetchWorkspace({ tab: this.tab, folder_id: this.currentFolderId, gallery_id: this.activeGalleryId });
            }).catch((error) => {
                this.notify(error.message, 'error');
            });
        },

        addMediaIdsToGallery(mediaIds, galleryId) {
            const eligibleIds = mediaIds
                .map((id) => Number(id))
                .filter((id) => this.isGalleryEligibleMedia(
                    this.mediaItems.find((item) => Number(item.id) === id)
                    || this.libraryMediaItems.find((item) => Number(item.id) === id)
                ));

            if (!eligibleIds.length) {
                return Promise.reject(new Error('Only images and videos can be added to galleries.'));
            }

            return this.send(this.replaceId(this.routes.galleryAddItems, galleryId), 'POST', {
                media_item_ids: eligibleIds,
            });
        },

        contextFolderTargets() {
            return [{ id: '', folder_name: 'Root Folder', depth: -1 }, ...this.foldersFlat];
        },

        contextGalleryTargets() {
            return this.galleries;
        },

        openContextSubmenu(type) {
            if (this.contextMenu.submenu === type) {
                this.contextMenu.submenu = null;
                return;
            }

            const menuWidth = 212;
            const submenuWidth = 212;
            const gap = -2;
            const padding = 12;
            const submenuItemCount = type === 'gallery'
                ? Math.max(1, this.contextGalleryTargets().length)
                : Math.max(1, this.contextFolderTargets().length);
            const submenuHeight = Math.min(window.innerHeight - (padding * 2), 16 + (submenuItemCount * 42));
            const openRight = this.contextMenu.x + menuWidth + gap + submenuWidth <= window.innerWidth - padding;
            const maxTop = Math.max(padding, window.innerHeight - submenuHeight - padding);
            const submenuTop = Math.max(padding - this.contextMenu.y, Math.min(0, maxTop - this.contextMenu.y));

            this.contextMenu.submenu = type;
            this.contextMenu.submenuSide = openRight ? 'right' : 'left';
            this.contextMenu.submenuTop = submenuTop;
        },

        closeAllMenus() {
            this.closeContextMenu();
            this.contextMenu.submenu = null;
        },
    }));

    queueMicrotask(() => {
        document.querySelectorAll('[x-data]').forEach((element) => {
            const expression = element.getAttribute('x-data') || '';
            if (!expression.includes('mediaWorkspace') || element._x_marker) {
                return;
            }

            window.Alpine.initTree(element);
        });
    });
}

registerMediaWorkspace();
document.addEventListener('alpine:init', registerMediaWorkspace);
