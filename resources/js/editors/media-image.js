import { Node, mergeAttributes } from '@tiptap/core';

class MediaImageNodeView {
    constructor({ node, getPos, editor }) {
        this.node = node;
        this.getPos = getPos;
        this.editor = editor;
        this.dom = document.createElement('figure');
        this.dom.className = 'wc-editor-media-image';
        this.dom.contentEditable = 'false';
        this.render();
    }

    render() {
        const attrs = this.node.attrs || {};
        const layout = attrs.layout || 'full';
        const src = attrs.src || '';
        const alt = attrs.alt || '';
        const title = attrs.title || '';
        const caption = attrs.caption || '';

        this.dom.className = `wc-editor-media-image wc-editor-media-image--${layout}`;
        this.dom.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'wc-editor-media-image__header';

        const label = document.createElement('span');
        label.className = 'wc-editor-media-image__label';
        label.textContent = 'Image';
        header.appendChild(label);

        const layoutToggle = document.createElement('div');
        layoutToggle.className = 'wc-editor-media-image__layout-toggle';

        [
            ['full', 'Full'],
            ['wrap-left', 'Left'],
            ['wrap-right', 'Right'],
        ].forEach(([value, text]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `wc-editor-media-image__layout-btn${layout === value ? ' is-active' : ''}`;
            button.textContent = text;
            button.addEventListener('click', () => this.updateAttrs({ layout: value }));
            layoutToggle.appendChild(button);
        });

        header.appendChild(layoutToggle);
        this.dom.appendChild(header);

        const preview = document.createElement('div');
        preview.className = 'wc-editor-media-image__preview';
        const image = document.createElement('img');
        image.src = src;
        image.alt = alt;
        image.title = title;
        image.draggable = false;
        preview.appendChild(image);
        this.dom.appendChild(preview);

        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.value = caption;
        captionInput.placeholder = 'Add caption';
        captionInput.className = 'wc-editor-media-image__caption-input';
        const persistCaption = (event) => {
            this.updateAttrs({ caption: event.currentTarget.value });
        };
        captionInput.addEventListener('change', persistCaption);
        captionInput.addEventListener('blur', persistCaption);
        captionInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                event.currentTarget.blur();
            }
        });
        this.dom.appendChild(captionInput);
    }

    update(node) {
        if (node.type.name !== this.node.type.name) {
            return false;
        }

        this.node = node;
        this.render();
        return true;
    }

    updateAttrs(attrs) {
        const pos = typeof this.getPos === 'function' ? this.getPos() : null;
        if (pos === null || pos === undefined) {
            return;
        }

        const transaction = this.editor.state.tr.setNodeMarkup(pos, undefined, {
            ...this.node.attrs,
            ...attrs,
        });
        this.editor.view.dispatch(transaction);
    }

    ignoreMutation() {
        return true;
    }

    stopEvent(event) {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return false;
        }

        return Boolean(target.closest('button, input, select, textarea, label'));
    }
}

export const MediaImage = Node.create({
    name: 'mediaImage',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            src: { default: '' },
            alt: { default: '' },
            title: { default: '' },
            caption: { default: '' },
            layout: { default: 'full' },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-wc-media-image]',
                getAttrs: (element) => {
                    const image = element.querySelector('img');
                    const caption = element.querySelector('figcaption');
                    return {
                        src: image?.getAttribute('src') || '',
                        alt: image?.getAttribute('alt') || '',
                        title: image?.getAttribute('title') || '',
                        caption: caption?.textContent || '',
                        layout: element.getAttribute('data-layout') || 'full',
                    };
                },
            },
            {
                tag: 'img[src]',
                getAttrs: (element) => ({
                    src: element.getAttribute('src') || '',
                    alt: element.getAttribute('alt') || '',
                    title: element.getAttribute('title') || '',
                    caption: '',
                    layout: 'full',
                }),
            },
        ];
    },

    renderHTML({ node, HTMLAttributes }) {
        const attrs = node.attrs || {};
        const caption = String(attrs.caption || '').trim();
        const imageAttrs = {
            src: attrs.src || '',
            alt: attrs.alt || '',
            title: attrs.title || '',
        };

        return ['figure', mergeAttributes(HTMLAttributes, {
            'data-wc-media-image': 'true',
            'data-layout': attrs.layout || 'full',
            class: `wc-content-image wc-content-image--${attrs.layout || 'full'}`,
        }),
            ['img', imageAttrs],
            ...(caption ? [['figcaption', {}, caption]] : []),
        ];
    },

    addCommands() {
        return {
            setMediaImage: (attrs) => ({ chain }) => chain().insertContent({
                type: this.name,
                attrs: {
                    src: attrs?.src || '',
                    alt: attrs?.alt || '',
                    title: attrs?.title || '',
                    caption: attrs?.caption || '',
                    layout: attrs?.layout || 'full',
                },
            }).run(),
        };
    },

    addNodeView() {
        return (props) => new MediaImageNodeView(props);
    },
});
