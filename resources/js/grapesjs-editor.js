import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import '../css/grapesjs-custom-theme.css';
import grapesjsPresetWebpage from 'grapesjs-preset-webpage';

const getThemePalette = () => {
    const rootStyles = window.getComputedStyle(document.documentElement);
    const read = (name, fallback = '') => rootStyles.getPropertyValue(name).trim() || fallback;

    return {
        appBg: read('--surface-page', '#f3f6fb'),
        surface: read('--surface', '#ffffff'),
        surfaceMuted: read('--surface', '#f8fafc'),
        surfaceSoft: read('--surface-muted', '#e2e8f0'),
        surfaceElevated: read('--surface-raised', '#ffffff'),
        textStrong: read('--text-strong', '#0f172a'),
        textBody: read('--text', '#334155'),
        textSoft: read('--text-soft', '#64748b'),
        borderSoft: read('--border-soft', 'rgba(226, 232, 240, 0.9)'),
        primary: read('--accent', '#3b82f6'),
        primaryFg: read('--accent-foreground', '#f8fafc'),
        secondary: read('--secondary', '#64748b'),
    };
};

const buildImagePlaceholderSrc = (palette = getThemePalette()) => {
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675" viewBox="0 0 1200 675" fill="none">
            <rect width="1200" height="675" rx="24" fill="${palette.surfaceSoft}"/>
            <rect x="200" y="132" width="800" height="411" rx="20" fill="${palette.surfaceMuted}" stroke="${palette.borderSoft}" stroke-width="2"/>
            <circle cx="430" cy="278" r="44" fill="${palette.primary}" opacity="0.24"/>
            <path d="M330 456l118-122 96 96 142-154 184 180H330Z" fill="${palette.primary}" opacity="0.22"/>
            <text x="600" y="600" text-anchor="middle" fill="${palette.textSoft}" font-family="Inter, system-ui, sans-serif" font-size="34" font-weight="600">Add an image</text>
        </svg>
    `.trim();

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
};

const buildResponsiveCanvasCss = (palette = getThemePalette()) => `
  html, body {
    margin: 0;
    padding: 0;
    background: ${palette.surfaceMuted};
    color: ${palette.textBody};
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }

  body {
    --wc-surface: ${palette.surface};
    --wc-surface-muted: ${palette.surfaceMuted};
    --wc-surface-soft: ${palette.surfaceSoft};
    --wc-text-strong: ${palette.textStrong};
    --wc-text: ${palette.textBody};
    --wc-text-soft: ${palette.textSoft};
    --wc-border-soft: ${palette.borderSoft};
    --wc-primary: ${palette.primary};
    --wc-primary-fg: ${palette.primaryFg};
    --wc-secondary: ${palette.secondary};
    padding: 1rem;
    line-height: 1.65;
  }

  img, video, iframe, svg, canvas {
    max-width: 100%;
    height: auto;
  }

  img,
  video,
  iframe {
    border-radius: 0.875rem;
  }

  body > * + *,
  [data-gjs-type="wrapper"] > * + *,
  .gjs-content-section > * + *,
  .gjs-card > * + *,
  .gjs-callout > * + * {
    margin-top: 0.5rem;
  }

  iframe {
    width: 100%;
  }

  h1 {
    font-size: 1.875rem;
    line-height: 2.25rem;
    font-weight: 700;
    margin: 0.75rem 0 1rem;
    color: ${palette.textStrong};
  }

  h2 {
    font-size: 1.5rem;
    line-height: 2rem;
    font-weight: 600;
    margin: 0.75rem 0 0.75rem;
    color: ${palette.textStrong};
  }

  h3 {
    font-size: 1.25rem;
    line-height: 1.75rem;
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
    color: ${palette.textStrong};
  }

  p {
    margin: 0 0 0.75rem;
  }

  p:last-child {
    margin-bottom: 0;
  }

  ul {
    list-style: disc;
    padding-left: 1.5rem;
    margin: 0.75rem 0;
  }

  ol {
    list-style: decimal;
    padding-left: 1.5rem;
    margin: 0.75rem 0;
  }

  pre {
    background: ${palette.textStrong};
    color: ${palette.primaryFg};
    border-radius: 1rem;
    padding: 1rem;
    overflow-x: auto;
    margin: 1rem 0;
  }

  code {
    background: ${palette.surfaceSoft};
    border-radius: 0.375rem;
    padding: 0.125rem 0.375rem;
    font-size: 0.875em;
  }

  pre code {
    background: transparent;
    padding: 0;
    color: inherit;
  }

  table {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
  }

  th, td {
    padding: 0.75rem;
    text-align: left;
    border: 1px solid ${palette.borderSoft};
  }

  a:not(.gjs-button):not(.gjs-link-button) {
    color: ${palette.primary};
    text-decoration: underline;
    text-underline-offset: 2px;
  }

  hr {
    border: 0;
    border-top: 1px solid ${palette.borderSoft};
    margin: 1rem 0;
  }

  .gjs-two-column {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .gjs-three-column {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
  }

  @media (max-width: 768px) {
    .gjs-two-column,
    .gjs-three-column {
      grid-template-columns: 1fr;
    }
  }

  .gjs-one-column,
  .gjs-two-column,
  .gjs-three-column {
    margin: 0 0 1.5rem;
  }

  .gjs-content-section,
  .gjs-card,
  .gjs-callout,
  .gjs-quote,
  .gjs-spacer,
  .gjs-media-image,
  .gjs-media-video {
    box-sizing: border-box;
  }

  .gjs-content-section {
    padding: 0;
    border-radius: 0;
    background: transparent;
  }

  .gjs-content-section > :first-child,
  .gjs-card > :first-child,
  .gjs-callout > :first-child,
  .gjs-quote > :first-child {
    margin-top: 0;
  }

  .gjs-content-section > :last-child,
  .gjs-card > :last-child,
  .gjs-callout > :last-child,
  .gjs-quote > :last-child {
    margin-bottom: 0;
  }

  .gjs-card {
    padding: 1.125rem 1.25rem;
    border-radius: 1rem;
    background: var(--wc-surface);
  }

  .gjs-callout {
    padding: 1rem 1.125rem;
    border-radius: 1rem;
    background: var(--wc-surface);
  }

  .gjs-quote {
    position: relative;
    margin: 0;
    padding: 1.5rem 1.5rem 1.5rem 2.5rem;
    border-left: 4px solid var(--wc-secondary);
    background: var(--wc-surface);
    color: var(--wc-text);
  }

  .gjs-quote::before {
    content: "“";
    position: absolute;
    top: 0.65rem;
    left: 0.95rem;
    font-size: 3.75rem;
    line-height: 1;
    color: var(--wc-text-soft);
    font-family: Georgia, serif;
    opacity: 0.28;
  }

  .gjs-quote p {
    margin: 0;
    font-size: 1.3rem;
    line-height: 1.7;
    font-style: italic;
    font-family: Georgia, serif;
  }

  .gjs-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 999px;
    background: var(--wc-primary);
    color: var(--wc-primary-fg);
    text-decoration: none;
    font-weight: 600;
  }

  .gjs-link-button {
    color: var(--wc-primary);
    text-decoration: none;
    font-weight: 600;
  }

  .gjs-spacer {
    display: block;
    width: 100%;
    height: 1rem;
    background: transparent;
  }

  .gjs-media-image,
  .gjs-media-video {
    display: block;
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    border-radius: 0.875rem;
  }

  .gjs-plh-image {
    background: ${palette.surfaceSoft};
    outline-color: ${palette.primary};
  }
`;

const CANVAS_THEME_STYLE_ID = 'wc-grapes-canvas-theme';

const splitVisualContent = (content) => {
    const normalizedContent = String(content || '');
    const styleMatches = [...normalizedContent.matchAll(/<style\b[^>]*>([\s\S]*?)<\/style>/gi)];
    const css = styleMatches.map((match) => match[1] || '').join('\n').trim();
    const html = normalizedContent.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '').trim();

    return { html, css };
};

const serializeVisualContent = (editor) => {
    const html = editor.getHtml();
    const css = editor.getCss();
    return css ? `${html}<style>${css}</style>` : html;
};

const ensureComponentStyle = (component, expectedStyle = {}) => {
    if (!component || typeof component.addStyle !== 'function') {
        return;
    }

    const currentStyle = component.getStyle() || {};
    const mergedStyle = { ...expectedStyle, ...currentStyle };
    component.addStyle(mergedStyle);
};

const hasClassName = (component, className) => {
    if (!component || typeof component.getClasses !== 'function') {
        return false;
    }

    return component.getClasses().some((cssClass) => {
        if (typeof cssClass === 'string') {
            return cssClass === className;
        }

        if (cssClass && typeof cssClass === 'object' && typeof cssClass.get === 'function') {
            return cssClass.get('name') === className;
        }

        return false;
    });
};

const applyCuratedComponentStyles = (component) => {
    if (!component) {
        return;
    }

    if (hasClassName(component, 'gjs-one-column')) {
        ensureComponentStyle(component, {
            display: 'grid',
            'grid-template-columns': 'minmax(0, 1fr)',
            gap: '1rem',
        });
    }

    if (hasClassName(component, 'gjs-two-column')) {
        ensureComponentStyle(component, {
            display: 'grid',
            'grid-template-columns': 'repeat(2, minmax(0, 1fr))',
            gap: '1rem',
        });
    }

    if (hasClassName(component, 'gjs-three-column')) {
        ensureComponentStyle(component, {
            display: 'grid',
            'grid-template-columns': 'repeat(3, minmax(0, 1fr))',
            gap: '1rem',
        });
    }

    if (hasClassName(component, 'gjs-content-section')) {
        ensureComponentStyle(component, {
            padding: '0',
            'border-radius': '0',
            background: 'transparent',
        });
    }

    if (hasClassName(component, 'gjs-card')) {
        ensureComponentStyle(component, {
            padding: '1.125rem 1.25rem',
            'border-radius': '1rem',
            background: 'var(--wc-surface)',
        });
    }

    if (hasClassName(component, 'gjs-callout')) {
        ensureComponentStyle(component, {
            padding: '1rem 1.125rem',
            'border-radius': '1rem',
            background: 'var(--wc-surface)',
        });
    }

    if (hasClassName(component, 'gjs-quote')) {
        ensureComponentStyle(component, {
            position: 'relative',
            margin: '0 0 1.5rem',
            padding: '1.5rem 1.5rem 1.5rem 2.5rem',
            'border-left': '4px solid var(--wc-secondary)',
            background: 'var(--wc-surface)',
            color: 'var(--wc-text)',
        });
    }

    if (hasClassName(component, 'gjs-button')) {
        ensureComponentStyle(component, {
            display: 'inline-flex',
            'align-items': 'center',
            'justify-content': 'center',
            gap: '0.5rem',
            padding: '0.75rem 1rem',
            'border-radius': '999px',
            background: 'var(--wc-primary)',
            color: 'var(--wc-primary-fg)',
            'text-decoration': 'none',
            'font-weight': '600',
        });
    }

    if (hasClassName(component, 'gjs-link-button')) {
        ensureComponentStyle(component, {
            color: 'var(--wc-primary)',
            'text-decoration': 'none',
            'font-weight': '600',
        });
    }

    if (hasClassName(component, 'gjs-spacer')) {
        ensureComponentStyle(component, {
            display: 'block',
            width: '100%',
            height: '1rem',
            background: 'transparent',
        });
    }

    if (hasClassName(component, 'gjs-media-image') || hasClassName(component, 'gjs-media-video')) {
        ensureComponentStyle(component, {
            display: 'block',
            width: '100%',
            'max-width': '100%',
            margin: '0 auto',
            'border-radius': '0.875rem',
        });
    }

    if (hasClassName(component, 'gjs-media-image')) {
        const attributes = component.getAttributes?.() || {};
        if (attributes['data-wc-placeholder'] === 'image' && typeof component.addAttributes === 'function') {
            component.addAttributes({
                src: buildImagePlaceholderSrc(),
                alt: attributes.alt || 'Add an image',
            });
        }
    }

    const childComponents = component.components?.();
    if (childComponents?.forEach) {
        childComponents.forEach((child) => applyCuratedComponentStyles(child));
    }
};

const normalizeCuratedBlocks = (editor) => {
    const wrapper = editor?.DomComponents?.getWrapper?.();
    if (!wrapper) {
        return;
    }

    applyCuratedComponentStyles(wrapper);
};

const applyCanvasTheme = (editor) => {
    const documentNode = editor?.Canvas?.getDocument?.();
    if (!documentNode) {
        return;
    }

    const palette = getThemePalette();

    let styleNode = documentNode.getElementById(CANVAS_THEME_STYLE_ID);
    if (!styleNode) {
        styleNode = documentNode.createElement('style');
        styleNode.id = CANVAS_THEME_STYLE_ID;
        documentNode.head.appendChild(styleNode);
    }

    styleNode.textContent = buildResponsiveCanvasCss(palette);

    const htmlNode = documentNode.documentElement;
    const bodyNode = documentNode.body;
    if (htmlNode) {
        htmlNode.style.background = palette.surfaceMuted;
        htmlNode.style.color = palette.textBody;
    }
    if (bodyNode) {
        bodyNode.style.background = palette.surfaceMuted;
        bodyNode.style.color = palette.textBody;
    }

    const container = editor.getContainer?.();
    const outerCanvas = container?.querySelector?.('.gjs-cv-canvas');
    const frameWrapper = container?.querySelector?.('.gjs-frame-wrapper');
    const frameContainer = container?.querySelector?.('.gjs-cv-canvas__frames');

    if (outerCanvas) {
        outerCanvas.style.backgroundColor = palette.surfaceMuted;
    }
    if (frameWrapper) {
        frameWrapper.style.backgroundColor = palette.surfaceMuted;
    }
    if (frameContainer) {
        frameContainer.style.backgroundColor = palette.surfaceMuted;
    }
};

const applyResponsiveMediaStyles = (component) => {
    if (!component || component.get('type') !== 'image') {
        return;
    }

    const style = component.getStyle() || {};
    component.addStyle({
        display: style.display || 'block',
        'max-width': style['max-width'] || '100%',
        height: style.height || 'auto',
    });
};

const contentSectionComponent = (heading = 'Add a header', body = 'Add your content here.') => ({
    tagName: 'div',
    classes: ['gjs-content-section'],
    components: [
        {
            tagName: 'h2',
            content: heading,
        },
        {
            tagName: 'p',
            content: body,
        },
    ],
});

const getLayoutOneColumnContent = () => ({
    tagName: 'section',
    classes: ['gjs-one-column'],
    style: {
        display: 'grid',
        'grid-template-columns': 'minmax(0, 1fr)',
        gap: '1rem',
    },
    components: [
        contentSectionComponent('Add a header', 'Add your content here.'),
    ],
});

const getLayoutTwoColumnContent = () => ({
    tagName: 'section',
    classes: ['gjs-two-column'],
    style: {
        display: 'grid',
        'grid-template-columns': 'repeat(2, minmax(0, 1fr))',
        gap: '1rem',
    },
    components: [
        contentSectionComponent('Add a header', 'Add supporting content, dates, or a short explanation.'),
        contentSectionComponent('Add a header', 'Use for additional details, links, or a related image.'),
    ],
});

const getLayoutThreeColumnContent = () => ({
    tagName: 'section',
    classes: ['gjs-three-column'],
    style: {
        display: 'grid',
        'grid-template-columns': 'repeat(3, minmax(0, 1fr))',
        gap: '1rem',
    },
    components: [
        contentSectionComponent('Add a header', 'Add content.'),
        contentSectionComponent('Add a header', 'Add content.'),
        contentSectionComponent('Add a header', 'Add content.'),
    ],
});

const getCuratedBlockContent = (blockId) => {
    switch (blockId) {
        case 'layout-one-column':
            return getLayoutOneColumnContent();
        case 'layout-two-column':
            return getLayoutTwoColumnContent();
        case 'layout-three-column':
            return getLayoutThreeColumnContent();
        default:
            return null;
    }
};

const addCuratedBlocks = (editor) => {
    const blockManager = editor.BlockManager;

    blockManager.add('layout-one-column', {
        label: 'Single',
        category: 'Layout',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="5" width="16" height="14" rx="2"/></svg>',
        content: getLayoutOneColumnContent(),
    });

    blockManager.add('layout-two-column', {
        label: 'Two Col',
        category: 'Layout',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 5h7v14H4zM13 5h7v14h-7z"/></svg>',
        content: getLayoutTwoColumnContent(),
    });

    blockManager.add('layout-three-column', {
        label: 'Three Col',
        category: 'Layout',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h5v14H3zm6.5 0h5v14h-5zM16 5h5v14h-5z"/></svg>',
        content: getLayoutThreeColumnContent(),
    });

    blockManager.add('text-heading', {
        label: 'Heading',
        category: 'Text',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 5h3v6h8V5h3v14h-3v-6H8v6H5z"/></svg>',
        content: '<h2>Section heading</h2>',
    });

    blockManager.add('text-paragraph', {
        label: 'Paragraph',
        category: 'Text',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 6h14v2H5zm0 5h14v2H5zm0 5h9v2H5z"/></svg>',
        content: '<p>Write a paragraph with supporting details, context, or narrative copy.</p>',
    });

    blockManager.add('text-quote', {
        label: 'Quote',
        category: 'Text',
        media: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M6.5 10c-.223 0-.437.034-.65.065c.069-.232.14-.468.254-.68c.114-.308.292-.575.469-.844c.148-.291.409-.488.601-.737c.201-.242.475-.403.692-.604c.213-.21.492-.315.714-.463c.232-.133.434-.28.65-.35l.539-.222l.474-.197l-.485-1.938l-.597.144c-.191.048-.424.104-.689.171c-.271.05-.56.187-.882.312c-.318.142-.686.238-1.028.466c-.344.218-.741.4-1.091.692c-.339.301-.748.562-1.05.945c-.33.358-.656.734-.909 1.162c-.293.408-.492.856-.702 1.299c-.19.443-.343.896-.468 1.336c-.237.882-.343 1.72-.384 2.437c-.034.718-.014 1.315.028 1.747c.015.204.043.402.063.539l.025.168l.026-.006A4.5 4.5 0 1 0 6.5 10m11 0c-.223 0-.437.034-.65.065c.069-.232.14-.468.254-.68c.114-.308.292-.575.469-.844c.148-.291.409-.488.601-.737c.201-.242.475-.403.692-.604c.213-.21.492-.315.714-.463c.232-.133.434-.28.65-.35l.539-.222l.474-.197l-.485-1.938l-.597.144c-.191.048-.424.104-.689.171c-.271.05-.56.187-.882.312c-.317.143-.686.238-1.028.467c-.344.218-.741.4-1.091.692c-.339.301-.748.562-1.05.944c-.33.358-.656.734-.909 1.162c-.293.408-.492.856-.702 1.299c-.19.443-.343.896-.468 1.336c-.237.882-.343 1.72-.384 2.437c-.034.718-.014 1.315.028 1.747c.015.204.043.402.063.539l.025.168l.026-.006A4.5 4.5 0 1 0 17.5 10"/></svg>',
        content: `
            <blockquote class="gjs-quote">
                <p>Use a quotation or emphasized statement.</p>
            </blockquote>
        `,
    });

    blockManager.add('section-box', {
        label: 'Section',
        category: 'Section/Card',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M7 9h10v2H7zm0 4h7v2H7z" fill="#fff"/></svg>',
        content: `
            <section class="gjs-content-section">
                <h2>Section title</h2>
                <p>Use this section for body content, notices, and descriptive text.</p>
            </section>
        `,
    });

    blockManager.add('section-card', {
        label: 'Card',
        category: 'Section/Card',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M8 9h8v2H8zm0 4h5v2H8z" fill="#fff"/></svg>',
        content: `
            <div class="gjs-card">
                <h3>Card title</h3>
                <p>Use this for compact content summaries, quick details, or highlights.</p>
            </div>
        `,
    });

    blockManager.add('section-callout', {
        label: 'Callout',
        category: 'Section/Card',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v16H4z"/><path d="M8 8h8v2H8zm0 4h6v2H8z" fill="#fff"/></svg>',
        content: `
            <div class="gjs-callout">
                <strong>Important update</strong>
                <p>Add a highlighted note, announcement, or context here.</p>
            </div>
        `,
    });

    blockManager.add('media-image', {
        label: 'Image',
        category: 'Media',
        media: '<svg viewBox="0 0 16 16"><path fill="currentColor" d="M.002 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2zm1 9v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71l-2.66-1.772a.5.5 0 0 0-.63.062zm5-6.5a1.5 1.5 0 1 0-3 0a1.5 1.5 0 0 0 3 0"/></svg>',
        content: {
            type: 'image',
            classes: ['gjs-media-image'],
            attributes: {
                src: buildImagePlaceholderSrc(),
                alt: 'Add an image',
                'data-wc-placeholder': 'image',
            },
        },
    });

    blockManager.add('media-video', {
        label: 'Video',
        category: 'Media',
        media: '<svg viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M4 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm4.625 5.63a1.235 1.235 0 0 1 1.715-.992c.504.216 1.568.702 2.916 1.48a28 28 0 0 1 2.74 1.786a1.234 1.234 0 0 1 0 1.98a28 28 0 0 1-2.74 1.784a28 28 0 0 1-2.916 1.482a1.234 1.234 0 0 1-1.715-.992a29 29 0 0 1-.176-3.264c0-1.551.112-2.719.176-3.264"/></g></svg>',
        content: `
            <div class="gjs-content-section">
                <iframe class="gjs-media-video" src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Embedded video" allowfullscreen></iframe>
            </div>
        `,
    });

    blockManager.add('action-button', {
        label: 'Button',
        category: 'Action',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="8" width="16" height="8" rx="4"/></svg>',
        content: `
            <a class="gjs-button" href="https://www.nstu.edu.bd" target="_blank" rel="noopener noreferrer">
                Learn more
            </a>
        `,
    });

    blockManager.add('action-link', {
        label: 'Link',
        category: 'Action',
        media: '<svg viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13a5 5 0 0 0 8 1l4-4a1 1 0 0 0-7-7l-2 2m3 6a5 5 0 0 0-8-1l-4 4a1 1 0 0 0 7 7l2-2"/></svg>',
        content: `
            <a class="gjs-link-button" href="https://www.nstu.edu.bd" target="_blank" rel="noopener noreferrer">
                Read more
            </a>
        `,
    });

    blockManager.add('layout-spacer', {
        label: 'Spacer',
        category: 'Layout',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 7h12v2H6zm0 8h12v2H6z"/><path d="M12 4l2 2h-4zm0 16l-2-2h4z"/></svg>',
        content: '<div class="gjs-spacer"></div>',
    });

    blockManager.add('action-divider', {
        label: 'Divider',
        category: 'Action',
        media: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="11" width="16" height="2" rx="1"/></svg>',
        content: '<hr>',
    });
};

const registerBetterDefaults = (editor) => {
    editor.on('component:create', applyResponsiveMediaStyles);
    editor.on('component:add', applyResponsiveMediaStyles);
    editor.on('component:add', () => normalizeCuratedBlocks(editor));
    editor.on('load', () => {
        editor.DomComponents.getWrapper().findType('image').forEach(applyResponsiveMediaStyles);
        normalizeCuratedBlocks(editor);
    });
};

export function initGrapesEditor(config = {}) {
    const defaultConfig = {
        container: '#gjs-editor',
        fromElement: true,
        height: '600px',
        width: 'auto',
        storageManager: false,
        nativeDnD: false,
        colorPrimary: '#3b82f6',
        colorSecondary: '#64748b',
        colorDark: '#0f172a',
        colorLight: '#f8fafc',
        colorWarning: '#f59e0b',
        colorDanger: '#ef4444',
        colorSuccess: '#10b981',
        plugins: [grapesjsPresetWebpage],
        pluginsOpts: {
            [grapesjsPresetWebpage]: {
                blocks: [],
                modalImportTitle: 'Import HTML/CSS',
                modalImportLabel: '<div style="margin-bottom:10px;font-size:13px;">Paste HTML and optional CSS inside a single &lt;style&gt; tag.</div>',
                modalImportContent(editor) {
                    return serializeVisualContent(editor);
                },
            },
        },
        canvas: {
            styles: ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'],
        },
        canvasCss: buildResponsiveCanvasCss(),
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '820px', widthMedia: '992px' },
                { name: 'Mobile', width: '375px', widthMedia: '480px' },
            ],
        },
        styleManager: {
            appendTo: '#gjs-styles',
            sectors: [
                {
                    name: 'Layout',
                    open: true,
                    buildProps: ['display', 'position', 'top', 'right', 'bottom', 'left', 'flex-direction', 'justify-content', 'align-items', 'gap'],
                },
                {
                    name: 'Spacing',
                    open: false,
                    buildProps: ['margin', 'padding'],
                },
                {
                    name: 'Size',
                    open: false,
                    buildProps: ['width', 'max-width', 'min-height', 'height'],
                },
                {
                    name: 'Typography',
                    open: false,
                    buildProps: ['font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'color', 'text-align'],
                },
                {
                    name: 'Decorations',
                    open: false,
                    buildProps: ['background-color', 'border-radius', 'border', 'box-shadow', 'opacity'],
                },
            ],
        },
        blockManager: {
            appendTo: '#gjs-blocks',
        },
        layerManager: {
            appendTo: '#gjs-layers',
        },
        traitManager: {
            appendTo: '#gjs-traits',
        },
        selectorManager: {
            appendTo: false,
        },
        storageManager: false,
    };

    const editor = grapesjs.init({
        ...defaultConfig,
        ...config,
        canvas: {
            ...defaultConfig.canvas,
            ...(config.canvas || {}),
        },
        pluginsOpts: {
            ...defaultConfig.pluginsOpts,
            ...(config.pluginsOpts || {}),
        },
        styleManager: {
            ...defaultConfig.styleManager,
            ...(config.styleManager || {}),
        },
        blockManager: {
            ...defaultConfig.blockManager,
            ...(config.blockManager || {}),
        },
        layerManager: {
            ...defaultConfig.layerManager,
            ...(config.layerManager || {}),
        },
        traitManager: {
            ...defaultConfig.traitManager,
            ...(config.traitManager || {}),
        },
        selectorManager: {
            ...defaultConfig.selectorManager,
            ...(config.selectorManager || {}),
        },
        deviceManager: {
            ...defaultConfig.deviceManager,
            ...(config.deviceManager || {}),
        },
    });

    editor.Commands.add('set-device-desktop', {
        run: (instance) => instance.setDevice('Desktop'),
    });
    editor.Commands.add('set-device-tablet', {
        run: (instance) => instance.setDevice('Tablet'),
    });
    editor.Commands.add('set-device-mobile', {
        run: (instance) => instance.setDevice('Mobile'),
    });

    addCuratedBlocks(editor);
    registerBetterDefaults(editor);
    editor.on('load', () => applyCanvasTheme(editor));
    editor.refreshDashboardTheme = () => {
        applyCanvasTheme(editor);
        normalizeCuratedBlocks(editor);
    };

    return editor;
}

window.initGrapesEditor = initGrapesEditor;
window.serializeGrapesContent = serializeVisualContent;
window.splitGrapesContent = splitVisualContent;
window.getGrapesBlockContent = getCuratedBlockContent;
