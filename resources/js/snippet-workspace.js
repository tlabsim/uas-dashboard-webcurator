const escapeHtml = (value = '') => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

const escapeScript = (value = '') => String(value).replace(/<\/script/gi, '<\\/script');

function highlightHtml(source = '') {
    let output = escapeHtml(source);
    output = output.replace(/(&lt;!--[\s\S]*?--&gt;)/g, '<span class="token-comment">$1</span>');
    output = output.replace(/(&lt;\/?)([a-zA-Z][\w:-]*)([^&]*?)(\/?&gt;)/g, (_, open, tag, attrs, close) => {
        const highlightedAttrs = attrs.replace(/([\w:-]+)(=)(&quot;[^&]*?&quot;|&#039;[^&]*?&#039;)/g, '<span class="token-attr">$1</span>$2<span class="token-string">$3</span>');
        return `<span class="token-punctuation">${open}</span><span class="token-tag">${tag}</span>${highlightedAttrs}<span class="token-punctuation">${close}</span>`;
    });
    return output;
}

function highlightCss(source = '') {
    let output = escapeHtml(source);
    output = output.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="token-comment">$1</span>');
    output = output.replace(/([.#]?[\w-]+)(\s*\{)/g, '<span class="token-selector">$1</span><span class="token-punctuation">$2</span>');
    output = output.replace(/([\w-]+)(\s*:)/g, '<span class="token-property">$1</span><span class="token-punctuation">$2</span>');
    output = output.replace(/(:\s*)(#[0-9a-fA-F]{3,8}|-?[\d.]+(?:px|rem|em|%|vh|vw)?|[a-zA-Z-]+)(;?)/g, '$1<span class="token-value">$2</span>$3');
    return output;
}

function highlightJs(source = '') {
    let output = escapeHtml(source);
    output = output.replace(/(\/\/.*$|\/\*[\s\S]*?\*\/)/gm, '<span class="token-comment">$1</span>');
    output = output.replace(/(['"`])((?:\\.|(?!\1)[\s\S])*)\1/g, '<span class="token-string">$&</span>');
    output = output.replace(/\b(const|let|var|function|return|if|else|for|while|try|catch|new|class|await|async|import|from|export|default|true|false|null|undefined)\b/g, '<span class="token-keyword">$1</span>');
    output = output.replace(/\b(\d+(?:\.\d+)?)\b/g, '<span class="token-number">$1</span>');
    return output;
}

function getPreviewDoc({ html = '', css = '', js = '', channel = '' }) {
    return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { color-scheme: light; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; width: 100%; max-width: 100%; overflow-x: hidden; }
    body {
      padding: 16px;
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      line-height: 1.5;
      color: #111827;
      background: #ffffff;
    }
    img, svg, video, iframe, canvas { max-width: 100%; height: auto; }
    pre { white-space: pre-wrap; word-break: break-word; }
    ${css}
  </style>
</head>
<body>
${html}
<script>
  (function () {
    const channel = ${JSON.stringify(channel)};
    const post = (payload) => {
      try {
        window.parent.postMessage({ channel, source: 'snippet-preview', ...payload }, '*');
      } catch (error) {}
    };

    window.onerror = function (message, source, lineno, colno) {
      post({ type: 'error', error: { message: String(message || 'Runtime error'), line: lineno || null, column: colno || null } });
      return false;
    };

    window.onunhandledrejection = function (event) {
      const reason = event && event.reason;
      post({ type: 'error', error: { message: String(reason && reason.message ? reason.message : reason || 'Unhandled promise rejection') } });
    };

    try {
      ${escapeScript(js)}
      post({ type: 'ready' });
    } catch (error) {
      post({
        type: 'error',
        error: {
          message: error && error.message ? error.message : 'Script error',
          stack: error && error.stack ? error.stack : '',
        },
      });
    }
  })();
</script>
</body>
</html>`;
}

window.snippetWorkspace = function snippetWorkspace(config = {}) {
    return {
        mode: config.mode || 'create',
        updateUrl: config.updateUrl || '',
        previewChannel: `snippet-preview-${Math.random().toString(36).slice(2, 10)}`,
        editorWidth: 52,
        workspaceHeight: 640,
        isResizingX: false,
        isResizingY: false,
        activePane: 'html',
        previewTab: 'preview',
        previewDoc: '',
        previewErrors: [],
        syntaxError: null,
        saveBusy: false,
        saveMessage: '',
        slugManuallyEdited: false,
        form: {
            name: config.initial?.name || '',
            slug: config.initial?.slug || '',
            snippet_group: config.initial?.snippet_group || '',
            content: config.initial?.content || '',
            css: config.initial?.css || '',
            js: config.initial?.js || '',
            tags: config.initial?.tags || '',
            status: config.initial?.status || 'Draft',
        },
        init() {
            this.slugManuallyEdited = this.form.slug.trim() !== '';
            this.refreshPreview();
            window.addEventListener('message', this.handlePreviewMessage.bind(this));
            window.addEventListener('pointermove', this.handlePointerMove.bind(this));
            window.addEventListener('pointerup', this.stopResize.bind(this));
        },
        workspaceStyle() {
            return `--snippet-editor-width:${this.editorWidth}%; --snippet-workspace-height:${this.workspaceHeight}px;`;
        },
        generateSlug() {
            if (this.slugManuallyEdited) {
                return;
            }

            this.form.slug = String(this.form.name || '')
                .trim()
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        },
        markSlugEdited() {
            this.slugManuallyEdited = true;
        },
        onCodeInput() {
            this.refreshPreview();
        },
        startHorizontalResize() {
            if (window.innerWidth < 1024) {
                return;
            }

            this.isResizingX = true;
        },
        startVerticalResize() {
            this.isResizingY = true;
        },
        handlePointerMove(event) {
            if (this.isResizingX && this.$refs.workspaceShell) {
                const rect = this.$refs.workspaceShell.getBoundingClientRect();
                const next = ((event.clientX - rect.left) / rect.width) * 100;
                this.editorWidth = Math.min(72, Math.max(32, next));
            }

            if (this.isResizingY && this.$refs.workspace) {
                const rect = this.$refs.workspace.getBoundingClientRect();
                const next = event.clientY - rect.top;
                this.workspaceHeight = Math.min(1100, Math.max(420, next));
            }
        },
        stopResize() {
            this.isResizingX = false;
            this.isResizingY = false;
        },
        refreshPreview() {
            this.previewErrors = [];
            this.syntaxError = null;

            if (String(this.form.js || '').trim() !== '') {
                try {
                    // Syntax-only validation before the isolated preview runs.
                    // eslint-disable-next-line no-new, no-new-func
                    new Function(this.form.js);
                } catch (error) {
                    this.syntaxError = {
                        message: error?.message || 'JavaScript syntax error',
                    };
                }
            }

            this.previewDoc = getPreviewDoc({
                html: this.form.content,
                css: this.form.css,
                js: this.form.js,
                channel: this.previewChannel,
            });
        },
        handlePreviewMessage(event) {
            const data = event?.data;
            if (!data || data.source !== 'snippet-preview' || data.channel !== this.previewChannel) {
                return;
            }

            if (data.type === 'error' && data.error) {
                this.previewErrors = [...this.previewErrors, data.error];
                this.previewTab = 'errors';
            }
        },
        syncScroll(event) {
            const pane = event.target.dataset.pane;
            const code = this.$refs[`${pane}Code`];
            if (!code) {
                return;
            }

            code.scrollTop = event.target.scrollTop;
            code.scrollLeft = event.target.scrollLeft;
        },
        highlighted(pane) {
            const value = this.form[pane === 'html' ? 'content' : pane] || '';

            if (pane === 'html') {
                return highlightHtml(value);
            }
            if (pane === 'css') {
                return highlightCss(value);
            }
            return highlightJs(value);
        },
        async submitEdit(event) {
            if (this.mode !== 'edit') {
                event.target.submit();
                return;
            }

            this.saveBusy = true;
            this.saveMessage = '';

            const formEl = event.target;
            const formData = new FormData(formEl);

            try {
                const response = await fetch(this.updateUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to update snippet.');
                }

                this.saveMessage = data.message || 'Snippet updated successfully.';
                window.toastNotifier.show({
                    message: this.saveMessage,
                    type: 'success',
                });
            } catch (error) {
                window.toastNotifier.show({
                    message: error.message || 'Failed to update snippet.',
                    type: 'error',
                });
            } finally {
                this.saveBusy = false;
            }
        },
    };
};
