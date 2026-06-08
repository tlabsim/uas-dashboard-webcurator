import { renderHtml, enhance } from './gallery-renderer';

const mount = (root) => {
    if (!root) {
        return root;
    }

    root.innerHTML = renderHtml(root.innerHTML || '');
    enhance(root);
    return root;
};

const enhanceAll = (root = document) => {
    root.querySelectorAll('.wc-rendered-content').forEach((element) => {
        mount(element);
    });
};

window.WebCuratorRenderedContent = {
    renderHtml,
    enhance,
    mount,
    enhanceAll,
};

export { renderHtml, enhance, mount, enhanceAll };
