@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $initialSnippet = [
        'name' => old('name', $snippet['name'] ?? ''),
        'slug' => old('slug', $snippet['slug'] ?? ''),
        'snippet_group' => old('snippet_group', $snippet['snippet_group'] ?? ''),
        'content' => old('content', $snippet['content'] ?? ''),
        'css' => old('css', $snippet['css'] ?? ''),
        'js' => old('js', $snippet['js'] ?? ''),
        'tags' => old('tags', $snippet['tags'] ?? ''),
        'status' => old('status', $snippet['status'] ?? 'Draft'),
    ];
@endphp

<form method="POST"
      action="{{ $isEdit ? route('dashboard.web_curator.snippets.update', $snippet['id']) : route('dashboard.web_curator.snippets.store') }}"
      class="space-y-6"
      x-data="snippetWorkspace({
          mode: '{{ $isEdit ? 'edit' : 'create' }}',
          updateUrl: '{{ $isEdit ? route('dashboard.web_curator.snippets.update', $snippet['id']) : '' }}',
          initial: @js($initialSnippet),
      })"
      x-init="init()"
      @submit.prevent="{{ $isEdit ? 'submitEdit($event)' : '$event.target.submit()' }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Basic Information</h3>
        </div>

        <div class="snippet-meta-grid">
            <div>
                <label class="label-base label-required">Snippet Name</label>
                <input type="text"
                       name="name"
                       x-model="form.name"
                       @input="generateSlug"
                       class="input-base @error('name') border-red-500 @enderror"
                       placeholder="Reusable snippet name"
                       required>
                @error('name')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label-base">Slug</label>
                <input type="text"
                       name="slug"
                       x-model="form.slug"
                       @input="markSlugEdited"
                       class="input-base @error('slug') border-red-500 @enderror"
                       placeholder="Auto-generated if left empty">
                @error('slug')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label-base">Snippet Group</label>
                <input type="text"
                       name="snippet_group"
                       x-model="form.snippet_group"
                       class="input-base @error('snippet_group') border-red-500 @enderror"
                       placeholder="Example: homepage, callouts, embeds">
                @error('snippet_group')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label-base">Usage Reference</label>
                <div class="flex min-h-[40px] items-center">
                    <span class="snippet-shortcode-chip" x-text="form.slug ? `<snippet slug=&quot;${form.slug}&quot; />` : '<snippet slug=&quot;slug-will-appear-here&quot; />'"></span>
                </div>
                <p class="help-text">Use this tag in supported content areas to render the snippet by slug.</p>
            </div>
        </div>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div class="card-header m-0 p-4 lg:px-6">
            <h3 class="card-title">Snippet Workspace</h3>
        </div>

        <div class="snippet-workspace" x-ref="workspace" :style="workspaceStyle()">
            <div class="snippet-workspace-toolbar">
                <div class="snippet-workspace-tabs">
                    <button type="button"
                            @click="activePane = 'html'"
                            :class="activePane === 'html' ? 'snippet-workspace-tab is-active' : 'snippet-workspace-tab'">
                        HTML
                    </button>
                    <button type="button"
                            @click="activePane = 'css'"
                            :class="activePane === 'css' ? 'snippet-workspace-tab is-active' : 'snippet-workspace-tab'">
                        CSS
                    </button>
                    <button type="button"
                            @click="activePane = 'js'"
                            :class="activePane === 'js' ? 'snippet-workspace-tab is-active' : 'snippet-workspace-tab'">
                        JavaScript
                    </button>
                </div>                
            </div>

            <div class="snippet-workspace-shell" x-ref="workspaceShell">
                <div class="snippet-editor-pane">
                    <div class="snippet-code-shell" x-show="activePane === 'html'">
                        <div class="snippet-code-label">
                            <span>HTML Markup</span>
                            <span class="text-xs text-[var(--text-soft)]">Reusable markup fragment</span>
                        </div>
                        <div class="snippet-code-editor">
                            <pre x-ref="htmlCode" aria-hidden="true"><code x-html="highlighted('html') + '\n'"></code></pre>
                            <textarea x-model="form.content"
                                      name="content"
                                      data-pane="html"
                                      spellcheck="false"
                                      @input="onCodeInput(); syncScroll($event)"
                                      @scroll="syncScroll($event)"
                                      class="@error('content') border-red-500 @enderror"></textarea>
                            <div x-show="!form.content" class="snippet-code-empty">Write raw HTML for the snippet output…</div>
                        </div>
                    </div>

                    <div class="snippet-code-shell" x-show="activePane === 'css'">
                        <div class="snippet-code-label">
                            <span>CSS</span>
                            <span class="text-xs text-[var(--text-soft)]">Optional scoped styling</span>
                        </div>
                        <div class="snippet-code-editor">
                            <pre x-ref="cssCode" aria-hidden="true"><code x-html="highlighted('css') + '\n'"></code></pre>
                            <textarea x-model="form.css"
                                      name="css"
                                      data-pane="css"
                                      spellcheck="false"
                                      @input="onCodeInput(); syncScroll($event)"
                                      @scroll="syncScroll($event)"></textarea>
                            <div x-show="!form.css" class="snippet-code-empty">Optional CSS for the snippet preview/output…</div>
                        </div>
                    </div>

                    <div class="snippet-code-shell" x-show="activePane === 'js'">
                        <div class="snippet-code-label">
                            <span>JavaScript</span>
                            <span class="text-xs text-[var(--text-soft)]">Optional behavior layer</span>
                        </div>
                        <div class="snippet-code-editor">
                            <pre x-ref="jsCode" aria-hidden="true"><code x-html="highlighted('js') + '\n'"></code></pre>
                            <textarea x-model="form.js"
                                      name="js"
                                      data-pane="js"
                                      spellcheck="false"
                                      @input="onCodeInput(); syncScroll($event)"
                                      @scroll="syncScroll($event)"></textarea>
                            <div x-show="!form.js" class="snippet-code-empty">Optional JS executed inside the sandboxed preview…</div>
                        </div>
                    </div>
                </div>

                <button type="button"
                        class="snippet-resize-handle snippet-resize-handle-x hidden lg:flex"
                        @pointerdown.prevent="startHorizontalResize($event)"
                        aria-label="Resize code editor and preview panes">
                    <span></span>
                </button>

                <div class="snippet-preview-pane">
                    <div class="snippet-preview-header">
                        <div class="snippet-preview-tabs">
                            <button type="button"
                                    @click="previewTab = 'preview'"
                                    :class="previewTab === 'preview' ? 'snippet-preview-tab is-active' : 'snippet-preview-tab'">
                                Preview
                            </button>
                            <button type="button"
                                    @click="previewTab = 'errors'"
                                    :class="previewTab === 'errors' ? 'snippet-preview-tab is-active' : 'snippet-preview-tab'">
                                Errors
                                <span x-show="previewErrors.length || syntaxError" class="ml-1 text-xs">•</span>
                            </button>
                            <button type="button"
                                    @click="previewTab = 'srcdoc'"
                                    :class="previewTab === 'srcdoc' ? 'snippet-preview-tab is-active' : 'snippet-preview-tab'">
                                Combined Output
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" @click="refreshPreview()">Refresh</button>
                    </div>

                    <div x-show="previewTab === 'preview'" class="snippet-preview-frame-wrap">
                        <iframe class="snippet-preview-frame"
                                sandbox="allow-scripts"
                                :srcdoc="previewDoc"
                                title="Snippet preview"></iframe>
                    </div>

                    <div x-show="previewTab === 'errors'" class="snippet-preview-errors">
                        <div class="snippet-error-list">
                            <template x-if="syntaxError">
                                <div class="snippet-error-item">
                                    <p class="font-semibold mb-1">JavaScript syntax check</p>
                                    <p x-text="syntaxError.message"></p>
                                </div>
                            </template>

                            <template x-for="(error, index) in previewErrors" :key="index">
                                <div class="snippet-error-item">
                                    <p class="font-semibold mb-1">Preview runtime error</p>
                                    <p x-text="error.message || 'Unknown error'"></p>
                                    <p x-show="error.line" class="mt-1 text-xs text-[var(--text-soft)]">
                                        Line <span x-text="error.line"></span><span x-show="error.column">, column <span x-text="error.column"></span></span>
                                    </p>
                                </div>
                            </template>

                            <template x-if="!syntaxError && previewErrors.length === 0">
                                <div class="rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)] p-4 text-sm text-[var(--text-soft)]">
                                    No syntax or runtime errors detected in the current preview.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="previewTab === 'srcdoc'" class="snippet-preview-srcdoc">
                        <div class="snippet-srcdoc" x-text="previewDoc"></div>
                    </div>
                </div>
            </div>

            <button type="button"
                    class="snippet-resize-handle snippet-resize-handle-y"
                    @pointerdown.prevent="startVerticalResize($event)"
                    aria-label="Resize snippet workspace height">
                <span></span>
            </button>

            <p class="help-text flex items-center gap-2 m-0 px-4 pt-2 pb-3 border-t border-[var(--border-soft)]">
                <svg class="h-4 w-5" viewBox="0 0 32 32"><path fill="currentColor" d="M16 13a1 1 0 0 1 1 1v9a1 1 0 1 1-2 0v-9a1 1 0 0 1 1-1m0-2a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3M2 16C2 8.268 8.268 2 16 2s14 6.268 14 14s-6.268 14-14 14S2 23.732 2 16M16 4C9.373 4 4 9.373 4 16s5.373 12 12 12s12-5.373 12-12S22.627 4 16 4"/></svg>
                The preview runs in an isolated iframe with script sandboxing. Runtime and syntax issues are reported in the preview pane.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Publishing</h3>
        </div>

        <div class="snippet-meta-grid">
            <div>
                <label class="label-base">Status</label>
                <x-custom-select
                    :options="[
                        ['value' => 'Draft', 'label' => 'Draft'],
                        ['value' => 'Published', 'label' => 'Published'],
                    ]"
                    :value="$initialSnippet['status']"
                    name="status"
                    placeholder="Select status"
                    @select-change="form.status = $event.detail.value"
                />
                @error('status')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label-base">Tags</label>
                <input type="text"
                       name="tags"
                       x-model="form.tags"
                       class="input-base @error('tags') border-red-500 @enderror"
                       placeholder="hero, embeds, announcements">
                <p class="help-text">Comma-separated tags for internal organization.</p>
                @error('tags')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('dashboard.web_curator.snippets.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary gap-2" :disabled="saveBusy">
            <svg x-show="saveBusy" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none" class="h-5 w-5 animate-spin text-white"><g fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"><path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13z" opacity=".2"></path><path d="M7.25.75A.75.75 0 018 0a8 8 0 018 8 .75.75 0 01-1.5 0A6.5 6.5 0 008 1.5a.75.75 0 01-.75-.75z"></path></g></svg>
            <span x-text="saveBusy ? '{{ $isEdit ? 'Saving...' : 'Creating...' }}' : '{{ $isEdit ? 'Save Changes' : 'Create Snippet' }}'"></span>
        </button>
    </div>
</form>
