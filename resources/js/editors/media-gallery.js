import { Node } from '@tiptap/core';
const GALLERY_LAYOUTS = ['grid', 'masonry', 'slider'];

const encodeGalleryItems = (items = []) => {
    try {
        const json = JSON.stringify(Array.isArray(items) ? items : []);
        return `b64:${window.btoa(unescape(encodeURIComponent(json)))}`;
    } catch (error) {
        return 'b64:W10=';
    }
};

const decodeGalleryItems = (value) => {
    const normalized = String(value || '').trim();
    if (!normalized) {
        return [];
    }

    if (normalized.startsWith('b64:')) {
        try {
            const decoded = decodeURIComponent(escape(window.atob(normalized.slice(4))));
            const parsed = JSON.parse(decoded);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    return null;
};

const safeParseItems = (value) => {
    if (!value) {
        return [];
    }

    const decoded = decodeGalleryItems(value);
    if (decoded !== null) {
        return decoded;
    }

    try {
        const parsed = typeof value === 'string' ? JSON.parse(value) : value;
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
};

const normalizeLayout = (value) => (GALLERY_LAYOUTS.includes(value) ? value : 'grid');
const itemKey = (item) => {
    const numericId = Number(item?.id);
    if (Number.isFinite(numericId) && numericId > 0) {
        return `id:${numericId}`;
    }

    return `src:${item?.src || item?.full_url || item?.public_url || ''}`;
};

const buildGalleryItemsPayload = (items = []) => items
    .map((item) => {
        const src = item?.full_url || item?.public_url || '';
        const thumb = item?.thumbnail_full_url || src;
        if (!src) {
            return null;
        }

        return {
            id: Number(item.id),
            src,
            thumb,
            title: item.title || item.original_name || item.file_name || '',
            filename: item.original_name || item.file_name || '',
            alt: item.alt_text || item.title || item.original_name || '',
            caption: item.caption || '',
            mime: item.mime_type || '',
        };
    })
    .filter(Boolean);

class MediaGalleryView {
    constructor(node, editor, getPos) {
        this.node = node;
        this.editor = editor;
        this.getPos = getPos;
        this.isPreview = false;
        this.sliderIndex = 0;

        this.dom = document.createElement('figure');
        this.dom.className = 'wc-editor-gallery-node';
        this.dom.contentEditable = 'false';

        this.header = document.createElement('div');
        this.header.className = 'wc-editor-gallery-node__header';

        this.title = document.createElement('div');
        this.title.className = 'wc-editor-gallery-node__title';
        this.title.textContent = 'Gallery';

        this.controls = document.createElement('div');
        this.controls.className = 'wc-editor-gallery-node__controls';

        this.layoutToggle = document.createElement('div');
        this.layoutToggle.className = 'wc-editor-gallery-node__layout-toggle';
        this.layoutToggle.innerHTML = `
            <span class="wc-editor-gallery-node__layout-thumb"></span>
            <button type="button" class="wc-editor-gallery-node__layout-option" data-layout-option="grid">Grid</button>
            <button type="button" class="wc-editor-gallery-node__layout-option" data-layout-option="masonry">Masonry</button>
            <button type="button" class="wc-editor-gallery-node__layout-option" data-layout-option="slider">Slider</button>
        `;
        this.layoutButtons = [...this.layoutToggle.querySelectorAll('[data-layout-option]')];
        this.layoutButtons.forEach((button) => {
            button.addEventListener('click', () => {
                this.setAttrs({ layout: normalizeLayout(button.dataset.layoutOption || 'grid') });
            });
        });

        this.previewButton = document.createElement('button');
        this.previewButton.type = 'button';
        this.previewButton.className = 'wc-editor-gallery-node__preview-toggle';
        this.previewButton.textContent = 'Preview';
        this.previewButton.addEventListener('click', () => {
            this.isPreview = !this.isPreview;
            this.render();
        });

        this.addButton = document.createElement('button');
        this.addButton.type = 'button';
        this.addButton.className = 'wc-editor-gallery-node__add';
        this.addButton.textContent = '+ Add images';
        this.addButton.addEventListener('click', () => this.openPicker());

        this.previewAddButtonGroup = document.createElement('div');
        this.previewAddButtonGroup.className = 'max-md:ml-auto flex list-items-center gap-2';
        this.previewAddButtonGroup.append(this.previewButton, this.addButton);

        this.controls.append(this.layoutToggle, this.previewAddButtonGroup);
        this.header.append(this.title, this.controls);

        this.body = document.createElement('div');
        this.body.className = 'wc-editor-gallery-node__body';

        this.dom.append(this.header, this.body);
        this.render();
    }

    updateSliderThumbTrack(viewport, track, activeIndex) {
        if (!viewport || !track) {
            return;
        }

        window.requestAnimationFrame(() => {
            const thumbs = [...track.querySelectorAll('.wc-editor-gallery-node__slider-thumb')];
            const activeThumb = thumbs[activeIndex] || thumbs[0];
            if (!activeThumb) {
                track.style.transform = 'translateX(0px)';
                return;
            }

            const viewportWidth = viewport.clientWidth;
            const trackWidth = track.scrollWidth;
            let translate = 0;

            if (trackWidth <= viewportWidth) {
                translate = (viewportWidth - trackWidth) / 2;
            } else {
                const activeCenter = activeThumb.offsetLeft + (activeThumb.offsetWidth / 2);
                const desired = (viewportWidth / 2) - activeCenter;
                const minTranslate = viewportWidth - trackWidth;
                const maxTranslate = 0;
                translate = Math.max(minTranslate, Math.min(maxTranslate, desired));
            }

            track.style.transform = `translateX(${translate}px)`;
        });
    }

    updateSliderPreviewState() {
        const preview = this.body.querySelector('.wc-editor-gallery-node__slider-preview');
        if (!preview) {
            return;
        }

        const slides = [...preview.querySelectorAll('.wc-editor-gallery-node__slider-slide')];
        const thumbs = [...preview.querySelectorAll('.wc-editor-gallery-node__slider-thumb')];
        const viewport = preview.querySelector('.wc-editor-gallery-node__slider-strip-viewport');
        const track = preview.querySelector('.wc-editor-gallery-node__slider-strip');

        if (!slides.length) {
            return;
        }

        this.sliderIndex = this.sliderIndex < 0
            ? slides.length - 1
            : this.sliderIndex >= slides.length
                ? 0
                : this.sliderIndex;

        slides.forEach((slide, index) => slide.classList.toggle('is-active', index === this.sliderIndex));
        thumbs.forEach((thumb, index) => thumb.classList.toggle('is-active', index === this.sliderIndex));
        this.updateSliderThumbTrack(viewport, track, this.sliderIndex);
    }

    setSliderIndex(nextIndex) {
        this.sliderIndex = nextIndex;
        this.updateSliderPreviewState();
    }

    isInteractiveTarget(target) {
        return Boolean(target?.closest?.('.wc-editor-gallery-node__controls, .wc-editor-gallery-node__remove'));
    }

    getItems() {
        return safeParseItems(this.node.attrs.itemsJson);
    }

    async openPicker() {
        const picker = window.WebCuratorMediaPicker;
        if (!picker?.open) {
            return;
        }

        const shell = this.dom.closest('[data-editor-shell]');
        const uploadContext = shell?.dataset?.mediaUploadContext || 'gallery';

        const selectedItems = await picker.open({
            title: 'Choose gallery images',
            mediaType: 'image',
            uploadContext,
            multiple: true,
        });

        if (!Array.isArray(selectedItems) || !selectedItems.length) {
            return;
        }

        const existingItems = this.getItems();
        const existingKeys = new Set(existingItems.map((item) => itemKey(item)).filter(Boolean));
        const appendedItems = buildGalleryItemsPayload(selectedItems).filter((item) => !existingKeys.has(itemKey(item)));
        if (!appendedItems.length) {
            return;
        }

        this.setAttrs({
            itemsJson: encodeGalleryItems([...existingItems, ...appendedItems]),
        });
    }

    setAttrs(partialAttrs = {}) {
        const pos = typeof this.getPos === 'function' ? this.getPos() : null;
        if (typeof pos !== 'number') {
            return;
        }

        const nextAttrs = {
            ...this.node.attrs,
            ...partialAttrs,
        };

        const transaction = this.editor.state.tr.setNodeMarkup(pos, undefined, nextAttrs);
        this.editor.view.dispatch(transaction);
        this.syncEditorOutput();
    }

    syncEditorOutput() {
        const shell = this.dom.closest('[data-editor-shell]');
        if (!shell) {
            return;
        }

        window.queueMicrotask(() => {
            const output = shell.querySelector?.('[data-editor-output]');
            if (output) {
                output.value = this.editor.getHTML();
            }

            if (typeof shell.__wcEditorShellInstance?.syncOutputFromActive === 'function') {
                shell.__wcEditorShellInstance.syncOutputFromActive(true);
            }
        });
    }

    removeItem(targetItem) {
        const targetKey = itemKey(targetItem);
        const nextItems = this.getItems().filter((item) => itemKey(item) !== targetKey);
        this.setAttrs({
            itemsJson: encodeGalleryItems(nextItems),
        });
    }

    render() {
        const layout = normalizeLayout(this.node.attrs.layout);
        const items = this.getItems();
        this.sliderIndex = Math.max(0, Math.min(this.sliderIndex, Math.max(items.length - 1, 0)));
        this.dom.dataset.layout = layout;
        this.dom.dataset.itemCount = String(items.length);
        this.dom.dataset.mode = this.isPreview ? 'preview' : 'edit';
        this.previewButton.textContent = this.isPreview ? 'Exit Preview' : 'Preview';
        this.previewButton.classList.toggle('is-active', this.isPreview);
        this.layoutButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.layoutOption === layout);
        });

        this.body.innerHTML = '';

        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'wc-editor-gallery-node__empty';
            empty.textContent = 'No images added yet';
            this.body.appendChild(empty);
            return;
        }

        if (this.isPreview) {
            if (layout === 'slider') {
                const preview = document.createElement('div');
                preview.className = 'wc-editor-gallery-node__slider-preview';

                const stage = document.createElement('div');
                stage.className = 'wc-editor-gallery-node__slider-stage';

                items.forEach((item, index) => {
                    const slide = document.createElement('div');
                    slide.className = `wc-editor-gallery-node__slider-slide${index === this.sliderIndex ? ' is-active' : ''}`;

                    const image = document.createElement('img');
                    image.src = item.src || item.thumb;
                    image.alt = item.alt || item.title || item.filename || 'Gallery image';
                    image.loading = 'lazy';
                    image.draggable = false;

                    slide.appendChild(image);
                    stage.appendChild(slide);
                });

                const prev = document.createElement('button');
                prev.type = 'button';
                prev.className = 'wc-editor-gallery-node__slider-nav is-prev';
                prev.setAttribute('aria-label', 'Previous image');
                prev.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m15 6l-6 6l6 6"/></svg>';
                prev.addEventListener('click', () => {
                    this.setSliderIndex(this.sliderIndex <= 0 ? items.length - 1 : this.sliderIndex - 1);
                });

                const next = document.createElement('button');
                next.type = 'button';
                next.className = 'wc-editor-gallery-node__slider-nav is-next';
                next.setAttribute('aria-label', 'Next image');
                next.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m9 6l6 6l-6 6"/></svg>';
                next.addEventListener('click', () => {
                    this.setSliderIndex(this.sliderIndex >= items.length - 1 ? 0 : this.sliderIndex + 1);
                });

                const stripViewport = document.createElement('div');
                stripViewport.className = 'wc-editor-gallery-node__slider-strip-viewport';

                const strip = document.createElement('div');
                strip.className = 'wc-editor-gallery-node__slider-strip';

                items.forEach((item, index) => {
                    const thumbButton = document.createElement('button');
                    thumbButton.type = 'button';
                    thumbButton.className = `wc-editor-gallery-node__slider-thumb${index === this.sliderIndex ? ' is-active' : ''}`;
                    thumbButton.setAttribute('aria-label', `Show image ${index + 1}`);

                    const thumbImage = document.createElement('img');
                    thumbImage.src = item.thumb || item.src;
                    thumbImage.alt = item.alt || item.title || item.filename || 'Gallery image';
                    thumbImage.loading = 'lazy';
                    thumbImage.draggable = false;

                    thumbButton.appendChild(thumbImage);
                    thumbButton.addEventListener('click', () => {
                        this.setSliderIndex(index);
                    });
                    strip.appendChild(thumbButton);
                });

                stripViewport.appendChild(strip);
                preview.append(prev, stage, next, stripViewport);
                this.body.appendChild(preview);
                this.updateSliderPreviewState();
                return;
            }

            const preview = document.createElement('div');
            preview.className = `wc-editor-gallery-node__preview${layout === 'masonry' ? ' is-masonry' : ''}${layout === 'slider' ? ' is-slider' : ''}`;

            items.forEach((item) => {
                const card = document.createElement('div');
                card.className = 'wc-editor-gallery-node__preview-card';

                const image = document.createElement('img');
                image.src = item.thumb || item.src;
                image.alt = item.alt || item.title || item.filename || 'Gallery image';
                image.loading = 'lazy';
                image.draggable = false;

                card.appendChild(image);

                preview.appendChild(card);
            });

            this.body.appendChild(preview);
            return;
        }

        const list = document.createElement('div');
        list.className = 'wc-editor-gallery-node__list';

        items.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'wc-editor-gallery-node__item';

            const thumbWrap = document.createElement('div');
            thumbWrap.className = 'wc-editor-gallery-node__thumb';

            const image = document.createElement('img');
            image.src = item.thumb || item.src;
            image.alt = item.alt || item.title || item.filename || 'Gallery image';
            image.loading = 'lazy';
            image.draggable = false;
            thumbWrap.appendChild(image);

            const meta = document.createElement('div');
            meta.className = 'wc-editor-gallery-node__item-meta';

            const name = document.createElement('div');
            name.className = 'wc-editor-gallery-node__item-title';
            name.textContent = item.title || item.filename || `Image #${item.id}`;

            const sub = document.createElement('div');
            sub.className = 'wc-editor-gallery-node__item-subtitle';
            sub.textContent = item.caption || item.filename || '';

            meta.append(name, sub);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'wc-editor-gallery-node__remove';
            remove.setAttribute('aria-label', 'Remove image');
            remove.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71a.996.996 0 0 0-1.41 0L12 10.59L7.11 5.7A.996.996 0 1 0 5.7 7.11L10.59 12L5.7 16.89a.996.996 0 1 0 1.41 1.41L12 13.41l4.89 4.89a.996.996 0 0 0 1.41-1.41L13.41 12l4.89-4.89c.38-.38.38-1.02 0-1.4"/></svg>';
            remove.addEventListener('click', () => this.removeItem(item));

            row.append(thumbWrap, meta, remove);
            list.appendChild(row);
        });

        this.body.appendChild(list);
    }

    update(node) {
        if (node.type.name !== 'mediaGallery') {
            return false;
        }

        this.node = node;
        this.render();
        return true;
    }

    stopEvent(event) {
        return this.isInteractiveTarget(event.target);
    }

    ignoreMutation() {
        return true;
    }

    destroy() {}
}

export const MediaGallery = Node.create({
    name: 'mediaGallery',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            layout: {
                default: 'grid',
                parseHTML: (element) => element.getAttribute('data-layout') || 'grid',
                renderHTML: () => ({}),
            },
            itemsJson: {
                default: 'b64:W10=',
                parseHTML: (element) => element.getAttribute('data-items') || 'b64:W10=',
                renderHTML: () => ({}),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'figure[data-wc-gallery]' }];
    },

    renderHTML({ node }) {
        const items = safeParseItems(node?.attrs?.itemsJson);
        const layout = normalizeLayout(node?.attrs?.layout);

        return ['figure', {
            'data-wc-gallery': 'true',
            'data-layout': layout,
            'data-items': encodeGalleryItems(items),
            'data-item-count': String(items.length),
        }];
    },

    addNodeView() {
        return ({ node, editor, getPos }) => new MediaGalleryView(node, editor, getPos);
    },
});
