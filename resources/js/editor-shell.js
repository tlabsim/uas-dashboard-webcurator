const SHELL_SELECTOR = '[data-editor-shell]';
const FORM_SELECTOR = '[data-web-curator-form]';
let tiptapModulesPromise = null;

const splitVisualContent = (content) => {
    const normalizedContent = String(content || '');
    const styleMatches = [...normalizedContent.matchAll(/<style\b[^>]*>([\s\S]*?)<\/style>/gi)];
    const css = styleMatches.map((match) => match[1] || '').join("\n").trim();
    const html = normalizedContent.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '').trim();

    return { html, css };
};

const formatCss = (css) => {
    const normalized = String(css || '').trim();
    if (!normalized) {
        return '';
    }

    return normalized
        .replace(/\s*{\s*/g, ' {\n')
        .replace(/;\s*/g, ';\n')
        .replace(/\s*}\s*/g, '\n}\n')
        .replace(/,\s*/g, ', ')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean)
        .reduce((lines, line) => {
            const previous = lines[lines.length - 1] || '';
            if (line === '}' && previous === '{') {
                lines[lines.length - 1] = '{}';
                return lines;
            }

            lines.push(line);
            return lines;
        }, [])
        .join('\n');
};

const formatHtmlNode = (node, depth = 0) => {
    const indent = '  '.repeat(depth);

    if (node.nodeType === Node.TEXT_NODE) {
        const text = node.textContent.replace(/\s+/g, ' ').trim();
        return text ? `${indent}${text}` : '';
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }

    const tagName = node.tagName.toLowerCase();
    const attributes = [...node.attributes]
        .map((attribute) => ` ${attribute.name}="${attribute.value}"`)
        .join('');

    if (!node.childNodes.length) {
        return `${indent}<${tagName}${attributes}></${tagName}>`;
    }

    const children = [...node.childNodes]
        .map((child) => formatHtmlNode(child, depth + 1))
        .filter(Boolean);

    if (!children.length) {
        return `${indent}<${tagName}${attributes}></${tagName}>`;
    }

    const textOnly = children.every((child) => !child.includes('\n'));
    if (textOnly && children.join('').length <= 100) {
        return `${indent}<${tagName}${attributes}>${children.map((child) => child.trim()).join('')}</${tagName}>`;
    }

    return `${indent}<${tagName}${attributes}>\n${children.join('\n')}\n${indent}</${tagName}>`;
};

const formatVisualContent = (content) => {
    const { html, css } = splitVisualContent(content);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html || '';

    const formattedHtml = [...wrapper.childNodes]
        .map((child) => formatHtmlNode(child, 0))
        .filter(Boolean)
        .join('\n\n')
        .trim();

    const formattedCss = formatCss(css);
    if (!formattedCss) {
        return formattedHtml;
    }

    return `${formattedHtml}\n\n<style>\n${formattedCss}\n</style>`.trim();
};

const hasMeaningfulVisualContent = (content) => {
    const normalized = String(content || '').trim();
    if (!normalized) {
        return false;
    }

    const withoutStyles = normalized.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '').trim();
    const simplified = withoutStyles
        .replace(/&nbsp;/gi, '')
        .replace(/<p>(?:\s|<br\s*\/?>|<\/?span[^>]*>)*<\/p>/gi, '')
        .replace(/<div>(?:\s|<br\s*\/?>|<\/?span[^>]*>)*<\/div>/gi, '')
        .replace(/<br\s*\/?>/gi, '')
        .replace(/\s+/g, '')
        .trim();

    return simplified.length > 0;
};

const waitForTinyMce = () => new Promise((resolve, reject) => {
    if (window.tinymce) {
        resolve(window.tinymce);
        return;
    }

    let attempts = 0;
    const interval = window.setInterval(() => {
        attempts += 1;
        if (window.tinymce) {
            window.clearInterval(interval);
            resolve(window.tinymce);
            return;
        }

        if (attempts >= 120) {
            window.clearInterval(interval);
            reject(new Error('TinyMCE did not load'));
        }
    }, 100);
});

const loadTiptapModules = async () => {
    if (!tiptapModulesPromise) {
        tiptapModulesPromise = Promise.all([
            import('@tiptap/core'),
            import('@tiptap/starter-kit'),
            import('@tiptap/extension-placeholder'),
            import('@tiptap/extension-text-align'),
            import('@tiptap/extension-text-style'),
            import('@tiptap/extension-color'),
            import('@tiptap/extension-highlight'),
            import('@tiptap/extension-youtube'),
            import('@tiptap/extension-link'),
            import('@tiptap/extension-subscript'),
            import('@tiptap/extension-superscript'),
            import('@tiptap/extension-mathematics'),
            import('@tiptap/extension-table'),
            import('@tiptap/extension-character-count'),
            import('katex'),
            import('katex/dist/katex.min.css'),
            import('./editors/font-size'),
            import('./editors/content-primitives'),
            import('./editors/media-gallery'),
            import('./editors/media-image'),
        ]).then(([
            tiptapCore,
            starterKit,
            placeholder,
            textAlign,
            textStyle,
            color,
            highlight,
            youtube,
            link,
            subscript,
            superscript,
            mathematics,
            table,
            characterCount,
            katex,
            _katexCss,
            fontSize,
            contentPrimitives,
            mediaGallery,
            mediaImage,
        ]) => ({
            Editor: tiptapCore.Editor,
            StarterKit: starterKit.default,
            Placeholder: placeholder.default,
            TextAlign: textAlign.default,
            TextStyle: textStyle.TextStyle,
            Color: color.default,
            Highlight: highlight.default,
            Youtube: youtube.default,
            Link: link.default,
            Subscript: subscript.default,
            Superscript: superscript.default,
            Mathematics: mathematics.default,
            TableKit: table.TableKit,
            CharacterCount: characterCount.default,
            katex: katex.default,
            FontSize: fontSize.FontSize,
            SectionBlock: contentPrimitives.SectionBlock,
            DivBlock: contentPrimitives.DivBlock,
            ContentPrimitiveAttributes: contentPrimitives.ContentPrimitiveAttributes,
            MediaGallery: mediaGallery.MediaGallery,
            MediaImage: mediaImage.MediaImage,
        }));
    }

    return tiptapModulesPromise;
};

class TinyMcePrimaryEditor {
    constructor(shell, onShortcut) {
        this.shell = shell;
        this.onShortcut = onShortcut;
        this.textarea = shell.root.querySelector('[data-primary-textarea]');
        this.instance = null;
        this.initialized = false;
    }

    async init(content) {
        if (this.initialized || !this.textarea) {
            return;
        }

        const tinymce = await waitForTinyMce();
        this.textarea.value = String(content || '');

        const instances = await tinymce.init({
            target: this.textarea,
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | align lineheight | numlist bullist | table image media link | removeformat code',
            height: Number.parseInt(this.shell.root.dataset.primaryHeight || '500', 10),
            license_key: 'gpl',
            setup: (editor) => {
                editor.on('keydown', (event) => {
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                        event.preventDefault();
                        this.onShortcut();
                    }
                });
            },
        });

        this.instance = instances?.[0] ?? null;
        this.initialized = true;

        if (this.instance) {
            this.instance.setContent(String(content || ''));
            this.instance.on('change input undo redo keyup', () => {
                this.shell.syncOutputFromActive();
            });
        }
    }

    async setContent(content) {
        await this.init(content);
        this.instance?.setContent(String(content || ''));
    }

    getContent() {
        if (this.instance) {
            return this.instance.getContent();
        }

        return this.textarea?.value || '';
    }

    focus() {
        this.instance?.focus();
    }
}

class TiptapPrimaryEditor {
    constructor(shell, onShortcut) {
        this.shell = shell;
        this.onShortcut = onShortcut;
        this.root = shell.root.querySelector('[data-tiptap-root]');
        this.surface = shell.root.querySelector('[data-rich-editor]');
        this.toolbar = shell.root.querySelector('[data-editor-toolbar]');
        this.headingSelect = shell.root.querySelector('[data-editor-heading]');
        this.toolbarButtons = [...shell.root.querySelectorAll('[data-editor-command]')];
        this.toolbarModeToggle = shell.root.querySelector('[data-toolbar-mode-toggle]');
        this.toolbarModeLabel = shell.root.querySelector('[data-toolbar-mode-label]');
        this.toolbarModeIcon = shell.root.querySelector('[data-toolbar-mode-icon]');
        this.toolbarGroups = [...shell.root.querySelectorAll('[data-toolbar-mode-group]')];
        this.textColorInput = shell.root.querySelector('[data-editor-text-color]');
        this.highlightColorInput = shell.root.querySelector('[data-editor-highlight-color]');
        this.fontSizeIndicator = shell.root.querySelector('[data-editor-font-size]');
        this.imageUploadInput = shell.root.querySelector('[data-editor-image-upload]');
        this.toolbarPresentation = this.root?.dataset.toolbarPresentation || 'toggle';
        this.toolbarMode = 'basic';
        this.editor = null;
    }

    async init(content) {
        if (this.editor || !this.surface) {
            return;
        }

        const {
            Editor,
            StarterKit,
            Placeholder,
            TextAlign,
            TextStyle,
            FontSize,
            Color,
            Highlight,
            Youtube,
            Link,
            Subscript,
            Superscript,
            TableKit,
            Mathematics,
            CharacterCount,
            katex,
            SectionBlock,
            DivBlock,
            ContentPrimitiveAttributes,
            MediaGallery,
            MediaImage,
        } = await loadTiptapModules();

        this.editor = new Editor({
            element: this.surface,
            extensions: [
                StarterKit.configure({
                    link: false,
                }),
                Placeholder.configure({
                    placeholder: this.shell.root.dataset.placeholder || 'Start writing...',
                }),
                Link.configure({
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                }),
                TextStyle,
                FontSize,
                Color,
                Highlight.configure({ multicolor: true }),
                TextAlign.configure({
                    types: ['heading', 'paragraph'],
                }),
                MediaImage,
                Youtube.configure({
                    nocookie: true,
                }),
                Subscript,
                Superscript,
                TableKit.configure({
                    resizable: false,
                }),
                Mathematics.configure({
                    katex,
                    katexOptions: {
                        throwOnError: false,
                    },
                }),
                CharacterCount,
                SectionBlock,
                DivBlock,
                ContentPrimitiveAttributes,
                MediaGallery,
            ],
            content: String(content || ''),
            onCreate: () => {
                this.syncToolbar();
                this.shell.syncOutputFromActive();
            },
            onSelectionUpdate: () => {
                this.syncToolbar();
            },
            onUpdate: () => {
                this.syncToolbar();
                this.shell.syncOutputFromActive();
            },
        });

        this.bindEvents();
        this.setToolbarMode(this.root?.dataset.toolbarModeDefault || 'basic');
    }

    bindEvents() {
        this.toolbarButtons.forEach((button) => {
            button.addEventListener('click', () => this.runCommand(button.dataset.editorCommand || ''));
        });

        this.headingSelect?.addEventListener('change', () => {
            if (!this.editor) {
                return;
            }

            const level = this.headingSelect.value;
            const chain = this.editor.chain().focus();

            if (level === 'paragraph') {
                chain.setParagraph().run();
            } else {
                chain.setHeading({ level: Number(level) }).run();
            }

            this.syncToolbar();
        });

        this.textColorInput?.addEventListener('input', () => {
            this.editor?.chain().focus().setColor(this.textColorInput.value).run();
            this.syncToolbar();
        });

        this.highlightColorInput?.addEventListener('input', () => {
            this.editor?.chain().focus().setHighlight({ color: this.highlightColorInput.value }).run();
            this.syncToolbar();
        });

        this.toolbarModeToggle?.addEventListener('click', () => {
            this.setToolbarMode(this.toolbarMode === 'advanced' ? 'basic' : 'advanced');
        });

        this.surface?.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                this.onShortcut();
            }
        });

        this.surface?.addEventListener('click', (event) => {
            if (!(event.target instanceof HTMLElement)
                || event.target.closest('.editor-toolbar-button')
                || event.target.closest('.wc-editor-media-image')
                || event.target.closest('.wc-editor-gallery-node')) {
                return;
            }

            this.editor?.chain().focus().run();
        });
    }

    async openMediaLibraryPicker(options = {}) {
        const picker = window.WebCuratorMediaPicker;

        if (!picker?.open) {
            return null;
        }

        return picker.open({
            title: 'Insert image',
            mediaType: 'image',
            uploadContext: this.shell.root.dataset.mediaUploadContext || 'gallery',
            preferUpload: false,
            multiple: false,
            ...options,
        });
    }

    setToolbarMode(mode) {
        this.toolbarMode = mode === 'all' ? 'all' : (mode === 'advanced' ? 'advanced' : 'basic');

        this.root?.setAttribute('data-toolbar-mode', this.toolbarMode);
        this.toolbar?.setAttribute('data-toolbar-mode', this.toolbarMode);

        this.toolbarGroups.forEach((group) => {
            const groupMode = group.dataset.toolbarModeGroup || 'basic';
            const shouldHide = groupMode === 'advanced' && !['advanced', 'all'].includes(this.toolbarMode);
            group.hidden = shouldHide;
            group.classList.toggle('hidden', shouldHide);
            group.style.display = shouldHide ? 'none' : 'inline-flex';
        });

        if (this.toolbarModeToggle) {
            this.toolbarModeToggle.setAttribute('aria-expanded', this.toolbarMode === 'advanced' ? 'true' : 'false');
            this.toolbarModeToggle.title = this.toolbarMode === 'advanced' ? 'Collapse advanced toolbar' : 'Expand advanced toolbar';
        }

        if (this.toolbarModeLabel) {
            this.toolbarModeLabel.textContent = this.toolbarMode === 'advanced' ? 'Basic' : 'Advanced';
        }

        this.toolbarModeIcon?.classList.toggle('rotate-180', this.toolbarMode === 'advanced');
    }

    syncToolbar() {
        if (!this.editor) {
            return;
        }

        const buttonStates = {
            bold: this.editor.isActive('bold'),
            italic: this.editor.isActive('italic'),
            underline: this.editor.isActive('underline'),
            strike: this.editor.isActive('strike'),
            bulletList: this.editor.isActive('bulletList'),
            orderedList: this.editor.isActive('orderedList'),
            blockquote: this.editor.isActive('blockquote'),
            codeBlock: this.editor.isActive('codeBlock'),
            link: this.editor.isActive('link'),
            alignLeft: this.editor.isActive({ textAlign: 'left' }),
            alignCenter: this.editor.isActive({ textAlign: 'center' }),
            alignRight: this.editor.isActive({ textAlign: 'right' }),
            subscript: this.editor.isActive('subscript'),
            superscript: this.editor.isActive('superscript'),
        };

        this.toolbarButtons.forEach((button) => {
            button.classList.toggle('is-active', Boolean(buttonStates[button.dataset.editorCommand || '']));
        });

        if (this.headingSelect) {
            if (this.editor.isActive('heading', { level: 1 })) {
                this.headingSelect.value = '1';
            } else if (this.editor.isActive('heading', { level: 2 })) {
                this.headingSelect.value = '2';
            } else if (this.editor.isActive('heading', { level: 3 })) {
                this.headingSelect.value = '3';
            } else {
                this.headingSelect.value = 'paragraph';
            }
        }

        if (this.textColorInput) {
            const color = this.editor.getAttributes('textStyle')?.color ?? '#0f172a';
            this.textColorInput.value = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(color) ? color : '#0f172a';
        }

        if (this.highlightColorInput) {
            const highlight = this.editor.getAttributes('highlight')?.color ?? '#fff3b0';
            this.highlightColorInput.value = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(highlight) ? highlight : '#fff3b0';
        }

        if (this.fontSizeIndicator) {
            this.fontSizeIndicator.textContent = this.editor.getAttributes('textStyle')?.fontSize ?? '16px';
        }
    }

    adjustFontSize(delta) {
        if (!this.editor) {
            return;
        }

        const currentFontSize = this.editor.getAttributes('textStyle')?.fontSize ?? '16px';
        const parsedFontSize = Number.parseInt(String(currentFontSize).replace('px', ''), 10);
        const nextFontSize = Math.min(32, Math.max(12, (Number.isNaN(parsedFontSize) ? 16 : parsedFontSize) + delta));

        this.editor.chain().focus().setFontSize(`${nextFontSize}px`).run();
        this.syncToolbar();
    }

    runCommand(command) {
        if (!this.editor) {
            return;
        }

        const chain = this.editor.chain().focus();

        switch (command) {
            case 'undo':
                chain.undo().run();
                break;
            case 'redo':
                chain.redo().run();
                break;
            case 'bold':
                chain.toggleBold().run();
                break;
            case 'italic':
                chain.toggleItalic().run();
                break;
            case 'underline':
                chain.toggleUnderline().run();
                break;
            case 'strike':
                chain.toggleStrike().run();
                break;
            case 'bulletList':
                chain.toggleBulletList().run();
                break;
            case 'orderedList':
                chain.toggleOrderedList().run();
                break;
            case 'blockquote':
                chain.toggleBlockquote().run();
                break;
            case 'codeBlock':
                chain.toggleCodeBlock().run();
                break;
            case 'fontSizeDecrease':
                this.adjustFontSize(-2);
                return;
            case 'fontSizeIncrease':
                this.adjustFontSize(2);
                return;
            case 'alignLeft':
                chain.setTextAlign('left').run();
                break;
            case 'alignCenter':
                chain.setTextAlign('center').run();
                break;
            case 'alignRight':
                chain.setTextAlign('right').run();
                break;
            case 'subscript':
                chain.toggleSubscript().run();
                break;
            case 'superscript':
                chain.toggleSuperscript().run();
                break;
            case 'horizontalRule':
                chain.setHorizontalRule().run();
                break;
            case 'link': {
                const existingUrl = this.editor.getAttributes('link')?.href ?? '';
                const url = window.prompt('Enter link URL', existingUrl);
                if (url === null) {
                    return;
                }
                if (url.trim() === '') {
                    chain.extendMarkRange('link').unsetLink().run();
                } else {
                    chain.extendMarkRange('link').setLink({ href: url.trim() }).run();
                }
                break;
            }
            case 'image': {
                const url = window.prompt('Enter image URL');
                if (!url?.trim()) {
                    return;
                }
                chain.setMediaImage({
                    src: url.trim(),
                    layout: 'full',
                }).run();
                break;
            }
            case 'imageUpload':
                this.openMediaLibraryPicker().then((item) => {
                    if (!item) {
                        return;
                    }

                    const src = item.full_url || item.public_url || '';
                    if (!src) {
                        return;
                    }

                    this.editor?.chain().focus().setMediaImage({
                        src,
                        layout: 'full',
                        alt: item.alt_text || item.title || item.original_name || '',
                        title: item.title || item.original_name || '',
                        caption: item.caption || '',
                    }).run();
                });
                return;
            case 'mediaGallery':
                this.editor?.chain().focus().insertContent({
                    type: 'mediaGallery',
                    attrs: {
                        layout: 'grid',
                        itemsJson: 'b64:W10=',
                    },
                }).run();
                return;
            case 'youtube': {
                const url = window.prompt('Enter YouTube URL');
                if (!url?.trim()) {
                    return;
                }
                chain.setYoutubeVideo({ src: url.trim() }).run();
                break;
            }
            case 'insertTable':
                chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
                break;
            case 'addRowAfter':
                chain.addRowAfter().run();
                break;
            case 'addColumnAfter':
                chain.addColumnAfter().run();
                break;
            case 'deleteTable':
                chain.deleteTable().run();
                break;
            case 'inlineMath': {
                const latex = window.prompt('Enter inline math expression');
                if (!latex?.trim()) {
                    return;
                }
                chain.insertInlineMath({ latex: latex.trim() }).run();
                break;
            }
            case 'blockMath': {
                const latex = window.prompt('Enter block math expression');
                if (!latex?.trim()) {
                    return;
                }
                chain.insertBlockMath({ latex: latex.trim() }).run();
                break;
            }
            case 'clearFormatting':
                chain.clearNodes().unsetAllMarks().run();
                break;
            default:
                return;
        }

        this.syncToolbar();
    }

    async setContent(content) {
        this.init(content);
        this.editor?.commands.setContent(String(content || ''), false);
        this.syncToolbar();
    }

    getContent() {
        return this.editor?.getHTML() || '';
    }

    focus() {
        this.editor?.chain().focus().run();
    }
}

class GrapesVisualEditor {
    constructor(shell) {
        this.shell = shell;
        this.canvas = shell.root.querySelector('[data-grapes-canvas]');
        this.visualDefaultTemplate = shell.root.querySelector('[data-visual-default-template]');
        this.blocksTarget = shell.root.querySelector('[data-grapes-blocks]');
        this.stylesTarget = shell.root.querySelector('[data-grapes-styles]');
        this.layersTarget = shell.root.querySelector('[data-grapes-layers]');
        this.traitsTarget = shell.root.querySelector('[data-grapes-traits]');
        this.rightPanel = shell.root.querySelector('[data-grapes-right-panel]');
        this.rightToggleButtons = [...shell.root.querySelectorAll('[data-grapes-right-toggle]')];
        this.rightToggleLabels = [...shell.root.querySelectorAll('[data-grapes-right-toggle-label]')];
        this.panelTabs = [...shell.root.querySelectorAll('[data-grapes-panel-tab]')];
        this.panels = [...shell.root.querySelectorAll('[data-grapes-panel]')];
        this.codeTextarea = shell.root.querySelector('[data-grapes-code-editor]');
        this.codeLoadButtons = [...shell.root.querySelectorAll('[data-grapes-code-load]')];
        this.codeApplyButtons = [...shell.root.querySelectorAll('[data-grapes-code-apply]')];
        this.editor = null;
        this.isRightPanelCollapsed = true;
        this.activePanel = 'styles';
        this.codeDirty = true;
        this.refreshCodeTextareaDeferred = this.debounce(() => this.refreshCodeTextareaNow(), 120);
        this.handleThemeChange = () => {
            this.editor?.refreshDashboardTheme?.();
            this.editor?.refresh?.();
        };
    }

    async init(content) {
        if (this.editor || !this.canvas) {
            return;
        }

        const { initGrapesEditor } = await import('../grapesjs-editor');
        this.editor = initGrapesEditor({
            container: `#${this.canvas.id}`,
            blockManager: this.blocksTarget?.id ? { appendTo: `#${this.blocksTarget.id}` } : undefined,
            styleManager: this.stylesTarget?.id ? { appendTo: `#${this.stylesTarget.id}` } : undefined,
            layerManager: this.layersTarget?.id ? { appendTo: `#${this.layersTarget.id}` } : undefined,
            traitManager: this.traitsTarget?.id ? { appendTo: `#${this.traitsTarget.id}` } : undefined,
            height: this.shell.root.dataset.visualHeight || '600px',
            fromElement: true,
            storageManager: false,
        });

        this.setContent(content);
        this.bindUI();
        this.editor.on('update', () => {
            this.codeDirty = true;
            if (this.activePanel === 'code') {
                this.refreshCodeTextareaDeferred();
            }
        });
        this.editor.on('load', () => {
            this.codeDirty = true;
            if (this.activePanel === 'code') {
                this.refreshCodeTextareaDeferred();
            }
        });
        this.setRightPanelCollapsed(true);
        document.addEventListener('dashboard-theme-changed', this.handleThemeChange);
        window.addEventListener('dashboard-theme-changed', this.handleThemeChange);
    }

    setContent(content) {
        let visualDefaultContent = this.visualDefaultTemplate?.innerHTML?.trim() || '';
        let visualDefaultComponents = null;
        const visualDefaultBlock = this.shell.root.dataset.visualDefaultBlock || '';
        const shouldUseDefaultBlock = !hasMeaningfulVisualContent(content) && visualDefaultBlock && this.editor;

        if (shouldUseDefaultBlock && typeof window.getGrapesBlockContent === 'function') {
            visualDefaultComponents = window.getGrapesBlockContent(visualDefaultBlock);
        }

        if (!visualDefaultComponents && !visualDefaultContent && visualDefaultBlock && this.editor) {
            const block = this.editor.BlockManager.get(visualDefaultBlock);
            const blockContent = block?.get('content');

            if (typeof blockContent === 'string') {
                visualDefaultContent = blockContent;
            } else if (blockContent && typeof blockContent === 'object') {
                visualDefaultComponents = blockContent;
            }
        }

        const resolvedContent = hasMeaningfulVisualContent(content)
            ? String(content || '')
            : visualDefaultContent;

        if (!this.editor) {
            if (this.canvas) {
                this.canvas.innerHTML = resolvedContent;
            }
            return;
        }

        const { splitGrapesContent } = window;
        const { html, css } = typeof splitGrapesContent === 'function'
            ? splitGrapesContent(resolvedContent)
            : splitVisualContent(resolvedContent);

        this.editor.setStyle(css || '');

        if (shouldUseDefaultBlock && visualDefaultComponents) {
            this.editor.setComponents('');
            this.editor.addComponents(visualDefaultComponents);
        } else if (shouldUseDefaultBlock && html) {
            this.editor.setComponents('');
            this.editor.addComponents(html);
        } else {
            this.editor.setComponents(html || '<p></p>');
        }
        this.refreshCodeTextarea();
    }

    getContent() {
        if (!this.editor) {
            return this.canvas?.innerHTML || '';
        }

        const serialize = window.serializeGrapesContent;
        return typeof serialize === 'function'
            ? serialize(this.editor)
            : `${this.editor.getHtml()}<style>${this.editor.getCss()}</style>`;
    }

    focus() {}

    bindUI() {
        if (this.uiBound) {
            return;
        }

        this.uiBound = true;

        this.panelTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                this.setActivePanel(tab.dataset.grapesPanelTab || 'styles');
            });
        });

        this.codeLoadButtons.forEach((button) => {
            button.addEventListener('click', () => this.refreshCodeTextarea());
        });

        this.codeApplyButtons.forEach((button) => {
            button.addEventListener('click', () => this.applyCodeTextarea());
        });

        this.rightToggleButtons.forEach((button) => {
            button.addEventListener('click', () => this.setRightPanelCollapsed(!this.isRightPanelCollapsed));
        });
    }

    debounce(fn, wait = 120) {
        let timeoutId = null;
        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => fn(...args), wait);
        };
    }

    setActivePanel(panelName) {
        this.activePanel = panelName;
        this.panelTabs.forEach((tab) => {
            tab.classList.toggle('is-active', tab.dataset.grapesPanelTab === panelName);
        });

        this.panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.grapesPanel === panelName);
        });

        if (panelName === 'code') {
            this.refreshCodeTextareaNow();
        }
    }

    setRightPanelCollapsed(collapsed) {
        this.isRightPanelCollapsed = collapsed;
        this.shell.root.classList.toggle('is-grapes-right-collapsed', collapsed);
        this.rightToggleButtons.forEach((button) => {
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
        this.rightToggleLabels.forEach((label) => {
            label.textContent = collapsed ? 'Show Panel' : 'Hide Panel';
        });
    }

    refreshCodeTextareaNow() {
        if (!this.codeTextarea || !this.editor) {
            return;
        }

        this.codeTextarea.value = formatVisualContent(this.getContent());
        this.codeDirty = false;
    }

    refreshCodeTextarea() {
        if (this.activePanel !== 'code') {
            this.codeDirty = true;
            return;
        }

        if (this.codeDirty) {
            this.refreshCodeTextareaNow();
        }
    }

    applyCodeTextarea() {
        if (!this.codeTextarea || !this.editor) {
            return;
        }

        this.setContent(this.codeTextarea.value || '');
        this.refreshCodeTextarea();
        this.shell.syncOutputFromActive();
    }
}

class WebCuratorEditorShell {
    constructor(root) {
        this.root = root;
        this.form = root.closest('form');
        this.output = root.querySelector('[data-editor-output]');
        this.modeInput = root.querySelector('[data-editor-mode-input]');
        this.primaryToggle = root.querySelector('[data-switch-primary]');
        this.visualToggle = root.querySelector('[data-switch-visual]');
        this.panes = {
            primary: root.querySelector('[data-editor-pane="primary"]'),
            visual: root.querySelector('[data-editor-pane="visual"]'),
        };
        this.primaryEditorType = root.dataset.primaryEditor || 'tiptap';
        this.visualEditorType = root.dataset.visualEditor || 'none';
        this.activeMode = root.dataset.activeMode === 'visual' && this.visualEditorType !== 'none' ? 'visual' : 'primary';
        this.allowFullscreen = root.dataset.allowFullscreen === 'true';
        this.isFullscreen = false;
        this.fullscreenToggle = root.querySelector('[data-editor-fullscreen-toggle]');
        this.fullscreenEnterIcon = root.querySelector('[data-editor-fullscreen-enter]');
        this.fullscreenExitIcon = root.querySelector('[data-editor-fullscreen-exit]');
        this.fullscreenLabel = root.querySelector('[data-editor-fullscreen-label]');
        this.mobileViewport = window.matchMedia('(max-width: 639.98px)');
        this.handleEscapeKey = (event) => {
            if (event.key === 'Escape' && this.isFullscreen) {
                this.setFullscreen(false);
            }
        };
        this.handleViewportChange = () => {
            this.applyResponsiveCapabilities();
        };
        this.debouncedVisualSync = this.debounce(() => this.syncOutputFromActive(true), 180);
        this.primaryEditor = this.primaryEditorType === 'tinymce'
            ? new TinyMcePrimaryEditor(this, () => this.submitShortcut())
            : new TiptapPrimaryEditor(this, () => this.submitShortcut());
        this.visualEditor = this.visualEditorType === 'grapesjs' ? new GrapesVisualEditor(this) : null;
    }

    async init() {
        this.applyResponsiveCapabilities();
        this.applyModeState();
        await this.primaryEditor.init(this.output?.value || '');

        if (this.activeMode === 'visual' && this.visualEditor) {
            await this.waitForNextFrame();
            await this.visualEditor.init(this.output?.value || '');
            this.visualEditor.editor?.refresh?.();
        }

        this.bindModeSwitches();
        this.bindFullscreenToggle();
        this.mobileViewport.addEventListener?.('change', this.handleViewportChange);
    }

    bindModeSwitches() {
        this.primaryToggle?.addEventListener('click', () => this.setMode('primary'));
        this.visualToggle?.addEventListener('click', () => this.setMode('visual'));
    }

    bindFullscreenToggle() {
        if (!this.allowFullscreen || !this.fullscreenToggle) {
            return;
        }

        this.fullscreenToggle.addEventListener('click', () => {
            this.setFullscreen(!this.isFullscreen);
        });
    }

    debounce(fn, wait = 180) {
        let timeoutId = null;
        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => fn(...args), wait);
        };
    }

    waitForNextFrame() {
        return new Promise((resolve) => window.requestAnimationFrame(() => resolve()));
    }

    isMobileViewport() {
        return this.mobileViewport?.matches;
    }

    applyResponsiveCapabilities() {
        if (this.isMobileViewport()) {
            if (this.isFullscreen) {
                this.setFullscreen(false);
            }

            if (this.activeMode === 'visual') {
                this.activeMode = 'primary';
            }
        }
    }

    applyModeState() {
        this.panes.primary?.classList.toggle('hidden', this.activeMode !== 'primary');
        this.panes.visual?.classList.toggle('hidden', this.activeMode !== 'visual');

        if (this.primaryToggle) {
            this.primaryToggle.classList.toggle('is-active', this.activeMode === 'primary');
        }

        if (this.visualToggle) {
            this.visualToggle.classList.toggle('is-active', this.activeMode === 'visual');
        }

        if (this.modeInput) {
            this.modeInput.value = this.activeMode;
        }
    }

    setFullscreen(shouldFullscreen) {
        if (!this.allowFullscreen || (shouldFullscreen && this.isMobileViewport())) {
            return;
        }

        this.isFullscreen = Boolean(shouldFullscreen);
        this.root.classList.toggle('is-fullscreen', this.isFullscreen);
        document.body.classList.toggle('dashboard-editor-fullscreen-open', this.isFullscreen);

        if (this.fullscreenToggle) {
            this.fullscreenToggle.classList.toggle('is-active', this.isFullscreen);
            this.fullscreenToggle.setAttribute('aria-pressed', this.isFullscreen ? 'true' : 'false');
            this.fullscreenToggle.title = this.isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen';
        }

        this.fullscreenEnterIcon?.classList.toggle('hidden', this.isFullscreen);
        this.fullscreenExitIcon?.classList.toggle('hidden', !this.isFullscreen);

        if (this.fullscreenLabel) {
            this.fullscreenLabel.textContent = this.isFullscreen ? 'Exit Fullscreen' : 'Fullscreen';
        }

        if (this.isFullscreen) {
            document.addEventListener('keydown', this.handleEscapeKey);
        } else {
            document.removeEventListener('keydown', this.handleEscapeKey);
        }
    }

    async setMode(mode) {
        if (mode !== 'visual' && mode !== 'primary') {
            return;
        }

        if (mode === 'visual' && this.isMobileViewport()) {
            return;
        }

        if (mode === 'visual' && !this.visualEditor) {
            return;
        }

        const currentContent = this.getActiveEditor()?.getContent() ?? this.output?.value ?? '';
        if (this.output) {
            this.output.value = currentContent;
        }

        if (mode === 'visual' && this.visualEditor) {
            this.activeMode = mode;
            this.applyModeState();
            await this.waitForNextFrame();
            await this.visualEditor.init(currentContent);
            this.visualEditor.editor?.refresh?.();
            this.visualEditor.setContent(currentContent);
        } else {
            await this.primaryEditor.setContent(currentContent);
            this.activeMode = mode;
            this.applyModeState();
        }
        this.getActiveEditor()?.focus();
    }

    getActiveEditor() {
        return this.activeMode === 'visual' ? this.visualEditor : this.primaryEditor;
    }

    syncOutputFromActive(forceImmediate = false) {
        if (!this.output) {
            return;
        }

        if (!forceImmediate && this.activeMode === 'visual') {
            this.debouncedVisualSync();
            return;
        }

        this.output.value = this.getActiveEditor()?.getContent() ?? '';
    }

    async prepareForSubmit() {
        if (this.activeMode === 'visual' && this.visualEditor) {
            await this.visualEditor.init(this.output?.value || '');
        } else {
            await this.primaryEditor.init(this.output?.value || '');
        }

        this.syncOutputFromActive(true);
    }

    submitShortcut() {
        if (!this.form || !this.form.matches('[data-enable-quick-save="true"]')) {
            this.form?.requestSubmit?.();
            return;
        }

        window.WebCuratorEditors?.prepareFormSubmission(this.form, { quickSave: true }).then(() => {
            this.form?.requestSubmit?.();
        });
    }
}

const registry = new WeakMap();
let quickSaveBindingInitialized = false;

const getShellsInForm = (form) => [...form.querySelectorAll(SHELL_SELECTOR)]
    .map((element) => registry.get(element))
    .filter(Boolean);

const initAllShells = () => {
    document.querySelectorAll(SHELL_SELECTOR).forEach((element) => {
        if (registry.has(element)) {
            return;
        }

        const shell = new WebCuratorEditorShell(element);
        registry.set(element, shell);
        element.__wcEditorShellInstance = shell;
        shell.init();
    });
};

const bindFormSubmission = () => {
    document.querySelectorAll(FORM_SELECTOR).forEach((form) => {
        if (form.dataset.editorBound !== 'true') {
            form.dataset.editorBound = 'true';
        }
    });

    if (quickSaveBindingInitialized) {
        return;
    }

    quickSaveBindingInitialized = true;

    document.addEventListener('keydown', async (event) => {
        if (event.defaultPrevented || !(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') {
            return;
        }

        const activeElement = document.activeElement;
        if (!(activeElement instanceof HTMLElement)) {
            return;
        }

        const form = activeElement.closest(FORM_SELECTOR);
        if (!(form instanceof HTMLFormElement) || form.dataset.enableQuickSave !== 'true') {
            return;
        }

        event.preventDefault();
        await window.WebCuratorEditors.prepareFormSubmission(form, { quickSave: true });
        form.requestSubmit();
    });
};

window.WebCuratorEditors = {
    initAll: () => {
        initAllShells();
        bindFormSubmission();
    },
    prepareFormSubmission: async (form, { quickSave = false } = {}) => {
        const shells = getShellsInForm(form);
        for (const shell of shells) {
            await shell.prepareForSubmit();
        }

        let quickSaveInput = form.querySelector('input[name="quick_save"]');
        if (quickSave) {
            if (!quickSaveInput) {
                quickSaveInput = document.createElement('input');
                quickSaveInput.type = 'hidden';
                quickSaveInput.name = 'quick_save';
                form.appendChild(quickSaveInput);
            }

            quickSaveInput.value = '1';
        } else if (quickSaveInput) {
            quickSaveInput.remove();
        }
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.WebCuratorEditors.initAll());
} else {
    window.WebCuratorEditors.initAll();
}
