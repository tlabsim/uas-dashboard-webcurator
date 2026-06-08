import { Extension, Node } from '@tiptap/core';

const attributeMap = {
    class: {
        default: null,
        parseHTML: (element) => element.getAttribute('class'),
        renderHTML: (attributes) => (attributes.class ? { class: attributes.class } : {}),
    },
    style: {
        default: null,
        parseHTML: (element) => element.getAttribute('style'),
        renderHTML: (attributes) => (attributes.style ? { style: attributes.style } : {}),
    },
};

export const ContentPrimitiveAttributes = Extension.create({
    name: 'contentPrimitiveAttributes',

    addGlobalAttributes() {
        return [
            {
                types: ['blockquote', 'link', 'sectionBlock', 'divBlock'],
                attributes: attributeMap,
            },
        ];
    },
});

export const SectionBlock = Node.create({
    name: 'sectionBlock',
    group: 'block',
    content: 'block+',
    defining: true,

    parseHTML() {
        return [{ tag: 'section' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['section', HTMLAttributes, 0];
    },
});

export const DivBlock = Node.create({
    name: 'divBlock',
    group: 'block',
    content: 'block*',
    defining: true,

    parseHTML() {
        return [{ tag: 'div' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', HTMLAttributes, 0];
    },
});
