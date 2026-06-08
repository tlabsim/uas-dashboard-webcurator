const STYLE_ID = 'wc-gallery-renderer-styles';

const decodeGalleryItems = (value) => {
    const normalized = String(value || '').trim();
    if (!normalized) {
        return null;
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

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const escapeAttribute = (value) => escapeHtml(value);

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

const getPreviewCss = () => `
.wc-rendered-gallery {
    margin: 1.75rem 0;
}
.wc-rendered-gallery__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
}
.wc-rendered-gallery__grid.is-masonry {
    columns: 3 220px;
    column-gap: .75rem;
    display: block;
}
.wc-rendered-gallery__grid.is-slider {
    display: none;
}
.wc-rendered-gallery__slider {
    position: relative;
    width: 100%;
    max-width: 100%;
    min-width: 0;
}
.wc-rendered-gallery__slider-stage {
    position: relative;
    overflow: hidden;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 0;
    border-radius: 1rem;
    // border: 1px solid rgba(148, 163, 184, 0.18);
    background: rgba(248, 250, 252, 0.92);
    min-height: 14rem;
}
.dark .wc-rendered-gallery__slider-stage {
    background: rgba(15, 23, 42, 0.7);
}
.wc-rendered-gallery__slide {
    display: none;
}
.wc-rendered-gallery__slide.is-active {
    display: block;
}
.wc-rendered-gallery__slide img {
    display: block;
    width: 100%;
    max-height: min(70dvh, 34rem);
    margin: 0 !important;
    object-fit: contain;
    background: transparent;
}
.wc-rendered-gallery__slider-nav {
    position: absolute;
    top: calc(min(70dvh, 34rem) / 2);
    z-index: 2;
    display: inline-flex !important;
    padding: 0 !important;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    margin-top: -1.25rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: rgba(255, 255, 255, 0.82);
    color: inherit;
    backdrop-filter: blur(8px);
}
.dark .wc-rendered-gallery__slider-nav {
    background: rgba(15, 23, 42, 0.82);
}
.wc-rendered-gallery__slider-nav.is-prev {
    left: .75rem;
}
.wc-rendered-gallery__slider-nav.is-next {
    right: .75rem;
}
.wc-rendered-gallery__slider-strip {
    display: none;
}
.wc-rendered-gallery__slider-strip-viewport {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    position: relative;
    overflow: hidden;
    margin-top: .75rem;
    display: block;
    box-sizing: border-box;
    height: 4.15rem;
}
.wc-rendered-gallery__slider-strip {
    display: flex;
    gap: .5rem;
    position: absolute;
    left: 0;
    top: 0;
    width: max-content;
    max-width: none;
    margin: 0;
    padding-bottom: .15rem;
    transition: transform .22s ease;
    will-change: transform;
}
.wc-rendered-gallery__slider-thumb {
    width: 4rem;
    height: 4rem;
    padding: 0 !important;
    flex: 0 0 auto;
    overflow: hidden;
    border-radius: .7rem;
    border-color: 1px solid rgba(148, 163, 184, 0.22);
    background: rgba(255, 255, 255, 0.72);
    opacity: .72;
}
.dark .wc-rendered-gallery__slider-thumb {
    background: rgba(15, 23, 42, 0.72);
}
.wc-rendered-gallery__slider-thumb.is-active {
    opacity: 1;
    border-color: rgba(16, 185, 129, 0.55) !important;
    box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.15);
}
.wc-rendered-gallery__slider-thumb img {
    display: block;
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    object-fit: cover;
}
.wc-rendered-gallery__item {
    display: block;
    overflow: hidden;
    border-radius: .85rem;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(248, 250, 252, 0.92);
    text-decoration: none;
    color: inherit;
    break-inside: avoid;
    margin-bottom: .75rem;
}
.dark .wc-rendered-gallery__item {
    background: rgba(15, 23, 42, 0.7);
}
.wc-rendered-gallery__item img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: cover;
}
@media (max-width: 768px) {
    .wc-rendered-gallery__grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    .wc-rendered-gallery__grid.is-masonry {
        columns: 2 160px;
    }
    .wc-rendered-gallery__slider-nav {
        width: 2.25rem;
        height: 2.25rem;
        margin-top: -1.125rem;
    }
    .wc-rendered-gallery__slider-thumb {
        width: 3.5rem;
        height: 3.5rem;
    }
}
`;

const sliderSetIndexScript = `(function(root,nextIndex){if(!root)return;const slides=[...root.querySelectorAll('.wc-rendered-gallery__slide')];const thumbs=[...root.querySelectorAll('.wc-rendered-gallery__slider-thumb')];if(!slides.length)return;let index=Number(nextIndex);if(!Number.isFinite(index))index=0;if(index<0)index=slides.length-1;if(index>=slides.length)index=0;root.dataset.sliderIndex=String(index);slides.forEach((slide,i)=>slide.classList.toggle('is-active',i===index));thumbs.forEach((thumb,i)=>thumb.classList.toggle('is-active',i===index));})(this.closest('.wc-rendered-gallery'),INDEX);`;

const buildGalleryMarkup = (element) => {
    const layout = ['grid', 'masonry', 'slider'].includes(element.getAttribute('data-layout')) ? element.getAttribute('data-layout') : 'grid';
    const items = safeParseItems(element.getAttribute('data-items'));

    if (!items.length) {
        return '';
    }

    const itemMarkup = items.map((item) => {
        const src = item.src || item.full_url || item.public_url || '';
        const thumb = item.thumb || item.thumbnail_full_url || src;
        if (!src || !thumb) {
            return '';
        }

        const title = String(item.title || item.filename || item.original_name || '').trim();
        const caption = String(item.caption || '').trim();
        const alt = String(item.alt || title || item.filename || 'Gallery image').trim();
        const tooltip = [caption, alt, title].find((value) => String(value || '').trim()) || '';

        return `
            <a class="wc-rendered-gallery__item" href="${escapeAttribute(src)}" target="_blank" rel="noopener"${tooltip ? ` title="${escapeAttribute(tooltip)}"` : ''}>
                <img src="${escapeAttribute(thumb)}" alt="${escapeAttribute(alt)}" loading="lazy">
            </a>
        `;
    }).join('');

    if (layout === 'slider') {
        const slideMarkup = items.map((item, index) => {
            const src = item.src || item.full_url || item.public_url || '';
            if (!src) {
                return '';
            }

            const title = String(item.title || item.filename || item.original_name || '').trim();
            const caption = String(item.caption || '').trim();
            const alt = String(item.alt || title || item.filename || 'Gallery image').trim();
            const tooltip = [caption, alt, title].find((value) => String(value || '').trim()) || '';

            return `
                <a class="wc-rendered-gallery__slide${index === 0 ? ' is-active' : ''}" href="${escapeAttribute(src)}" target="_blank" rel="noopener"${tooltip ? ` title="${escapeAttribute(tooltip)}"` : ''}>
                    <img src="${escapeAttribute(src)}" alt="${escapeAttribute(alt)}" loading="lazy">
                </a>
            `;
        }).join('');

        const thumbMarkup = items.map((item, index) => {
            const thumb = item.thumb || item.thumbnail_full_url || item.src || item.full_url || item.public_url || '';
            const title = String(item.title || item.filename || item.original_name || '').trim();
            const caption = String(item.caption || '').trim();
            const alt = String(item.alt || title || item.filename || 'Gallery image').trim();
            const tooltip = [caption, alt, title].find((value) => String(value || '').trim()) || '';

            return `
                <button type="button" class="wc-rendered-gallery__slider-thumb${index === 0 ? ' is-active' : ''}" data-gallery-slider-thumb aria-label="Show image ${index + 1}"${tooltip ? ` title="${escapeAttribute(tooltip)}"` : ''}>
                    <img src="${escapeAttribute(thumb)}" alt="${escapeAttribute(alt)}" loading="lazy">
                </button>
            `;
        }).join('');

        return `
            <figure class="wc-rendered-gallery" data-rendered-wc-gallery data-slider-index="0">
                <div class="wc-rendered-gallery__slider">
                    <button type="button" class="wc-rendered-gallery__slider-nav is-prev" data-gallery-slider-prev aria-label="Previous image">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m15 6l-6 6l6 6"/></svg>
                    </button>
                    <div class="wc-rendered-gallery__slider-stage">
                        ${slideMarkup}
                    </div>
                    <button type="button" class="wc-rendered-gallery__slider-nav is-next" data-gallery-slider-next aria-label="Next image">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m9 6l6 6l-6 6"/></svg>
                    </button>
                    <div class="wc-rendered-gallery__slider-strip-viewport">
                        <div class="wc-rendered-gallery__slider-strip">${thumbMarkup}</div>
                    </div>
                </div>
            </figure>
        `;
    }

    return `
        <figure class="wc-rendered-gallery" data-rendered-wc-gallery>
            <div class="wc-rendered-gallery__grid ${layout === 'masonry' ? 'is-masonry' : ''}${layout === 'slider' ? ' is-slider' : ''}">
                ${itemMarkup}
            </div>
        </figure>
    `;
};

const renderHtml = (html) => {
    const documentFragment = document.implementation.createHTMLDocument('gallery-renderer');
    documentFragment.body.innerHTML = String(html || '');
    documentFragment.body.querySelectorAll('figure[data-wc-gallery]').forEach((element) => {
        element.outerHTML = buildGalleryMarkup(element);
    });

    return documentFragment.body.innerHTML;
};

const setSliderIndex = (galleryElement, requestedIndex) => {
    if (!(galleryElement instanceof Element)) {
        return;
    }

    const slides = [...galleryElement.querySelectorAll('.wc-rendered-gallery__slide')];
    const thumbs = [...galleryElement.querySelectorAll('.wc-rendered-gallery__slider-thumb')];
    const viewport = galleryElement.querySelector('.wc-rendered-gallery__slider-strip-viewport');
    const track = galleryElement.querySelector('.wc-rendered-gallery__slider-strip');

    if (!slides.length) {
        return;
    }

    let index = Number(requestedIndex);
    if (!Number.isFinite(index)) {
        index = 0;
    }
    if (index < 0) {
        index = slides.length - 1;
    }
    if (index >= slides.length) {
        index = 0;
    }

    galleryElement.dataset.sliderIndex = String(index);
    slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === index));
    thumbs.forEach((thumb, thumbIndex) => thumb.classList.toggle('is-active', thumbIndex === index));

    if (viewport && track) {
        const activeThumb = thumbs[index] || thumbs[0];
        if (activeThumb) {
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
        }
    }
};

const enhance = (root = document) => {
    const scope = root instanceof Document ? root : root.ownerDocument || document;
    const container = root instanceof Document ? root.body : root;

    container?.querySelectorAll?.('.wc-rendered-gallery[data-rendered-wc-gallery]').forEach((galleryElement) => {
        if (galleryElement.dataset.galleryEnhanced === 'true') {
            setSliderIndex(galleryElement, Number(galleryElement.dataset.sliderIndex || 0));
            return;
        }

        galleryElement.dataset.galleryEnhanced = 'true';

        const prevButton = galleryElement.querySelector('[data-gallery-slider-prev]');
        const nextButton = galleryElement.querySelector('[data-gallery-slider-next]');
        const thumbs = [...galleryElement.querySelectorAll('[data-gallery-slider-thumb]')];

        prevButton?.addEventListener('click', () => {
            setSliderIndex(galleryElement, Number(galleryElement.dataset.sliderIndex || 0) - 1);
        });

        nextButton?.addEventListener('click', () => {
            setSliderIndex(galleryElement, Number(galleryElement.dataset.sliderIndex || 0) + 1);
        });

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => {
                setSliderIndex(galleryElement, index);
            });
        });

        setSliderIndex(galleryElement, Number(galleryElement.dataset.sliderIndex || 0));
    });

    scope.defaultView?.addEventListener?.('resize', () => {
        container?.querySelectorAll?.('.wc-rendered-gallery[data-rendered-wc-gallery]').forEach((galleryElement) => {
            setSliderIndex(galleryElement, Number(galleryElement.dataset.sliderIndex || 0));
        });
    }, { passive: true });
};

const getPreviewScript = () => `
(function(){
    const setSliderIndex = ${setSliderIndex.toString()};
    const enhance = ${enhance.toString()};
    document.addEventListener('DOMContentLoaded', function(){ enhance(document); });
})();
`;

const ensureStyles = (targetDocument = document) => {
    if (!targetDocument?.head || targetDocument.getElementById(STYLE_ID)) {
        return;
    }

    const style = targetDocument.createElement('style');
    style.id = STYLE_ID;
    style.textContent = getPreviewCss();
    targetDocument.head.appendChild(style);
};

window.WebCuratorGalleryRenderer = {
    renderHtml,
    getPreviewCss,
    getPreviewScript,
    ensureStyles,
    enhance,
};

export { renderHtml, getPreviewCss, getPreviewScript, ensureStyles, enhance };
