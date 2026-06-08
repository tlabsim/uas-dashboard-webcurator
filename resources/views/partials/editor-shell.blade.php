@props([
    'shellId',
    'fieldName',
    'initialContent' => '',
    'label' => 'Content',
    'primaryPlaceholder' => 'Start writing...',
    'primaryHeight' => 500,
    'visualHeight' => 600,
    'enableVisual' => false,
    'visualDescription' => 'Drag blocks from the left panel to build your content.',
    'defaultMode' => 'primary',
    'showHeader' => false,
    'allowFullscreen' => false,
    'stickyToolbar' => false,
    'visualDefaultContent' => '',
    'visualDefaultBlock' => '',
    'framedShell' => false,
    'mediaUploadContext' => 'gallery',
    'toolbarPresentation' => 'toggle',
    'toolbarBasicTools' => null,
    'toolbarAdvancedTools' => null,
])

@php
    $editorConfig = $webCuratorEditorConfig ?? ['primary' => 'tiptap', 'visual' => 'grapesjs'];
    $primaryEditor = $editorConfig['primary'] ?? 'tiptap';
    $visualEditor = $enableVisual ? ($editorConfig['visual'] ?? 'grapesjs') : 'none';
    $hasVisual = $enableVisual && $visualEditor !== 'none';
    $activeMode = old('editor_mode', $defaultMode);
    if (! $hasVisual) {
        $activeMode = 'primary';
    }

    $defaultToolbarBasicTools = [
        'heading',
        'undo',
        'redo',
        'bold',
        'italic',
        'underline',
        'strike',
        'bulletList',
        'orderedList',
        'blockquote',
        'codeBlock',
        'link',
        'image',
        'imageUpload',
        'mediaGallery',
        'youtube',
        'clearFormatting',
    ];

    $defaultToolbarAdvancedTools = [
        'fontSize',
        'alignLeft',
        'alignCenter',
        'alignRight',
        'subscript',
        'superscript',
        'horizontalRule',
        'insertTable',
        'addRowAfter',
        'addColumnAfter',
        'deleteTable',
        'inlineMath',
        'blockMath',
        'textColor',
        'highlightColor',
    ];

    $toolbarBasicTools = collect($toolbarBasicTools ?? $defaultToolbarBasicTools)
        ->map(fn ($tool) => (string) $tool)
        ->values();
    $toolbarAdvancedTools = collect($toolbarAdvancedTools ?? $defaultToolbarAdvancedTools)
        ->map(fn ($tool) => (string) $tool)
        ->values();

    $showBasicTool = fn (string $tool) => $toolbarBasicTools->contains($tool);
    $showAdvancedTool = fn (string $tool) => $toolbarAdvancedTools->contains($tool);
    $hasAnyBasic = fn (array $tools) => collect($tools)->contains(fn ($tool) => $showBasicTool($tool));
    $hasAnyAdvanced = fn (array $tools) => collect($tools)->contains(fn ($tool) => $showAdvancedTool($tool));
    $hasAdvancedToolbar = $toolbarAdvancedTools->isNotEmpty();
    $resolvedToolbarPresentation = $toolbarPresentation === 'all' ? 'all' : 'toggle';
@endphp

<div
    class="wc-editor-shell {{ $framedShell ? 'wc-editor-shell-framed' : '' }}"
    data-editor-shell
    data-shell-id="{{ $shellId }}"
    data-primary-editor="{{ $primaryEditor }}"
    data-visual-editor="{{ $visualEditor }}"
    data-active-mode="{{ $activeMode === 'visual' ? 'visual' : 'primary' }}"
    data-placeholder="{{ $primaryPlaceholder }}"
    data-primary-height="{{ $primaryHeight }}"
    data-visual-height="{{ $visualHeight }}px"
    data-allow-fullscreen="{{ $allowFullscreen ? 'true' : 'false' }}"
    data-sticky-toolbar="{{ $stickyToolbar ? 'true' : 'false' }}"
    data-visual-default-block="{{ $visualDefaultBlock }}"
    data-media-upload-context="{{ $mediaUploadContext }}"
>
    <input type="hidden" name="editor_mode" value="{{ $activeMode === 'visual' ? 'visual' : 'primary' }}" data-editor-mode-input>
    <textarea name="{{ $fieldName }}" data-editor-output class="hidden">{{ $initialContent }}</textarea>
    <template data-visual-default-template>{!! $visualDefaultContent !!}</template>

    @if ($hasVisual)
        <div class="editor-shell-header flex flex-wrap items-center justify-between">
            <h3 class="card-title">{{ $label }}</h3>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-500 sm:hidden">
                    {{ $primaryEditor === 'tiptap' ? 'Tiptap Editor' : 'TinyMCE Editor' }}
                </span>
                <button
                    type="button"
                    data-switch-primary
                    class="editor-mode-button hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-1 text-sm font-medium transition"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M19 19a1 1 0 0 1 .117 1.993L19 21h-7a1 1 0 0 1-.117-1.993L12 19zM16.096 4.368a2.5 2.5 0 0 1 3.657 3.405l-.122.13L8.735 18.8a1.5 1.5 0 0 1-.32.244l-.12.06l-3.804 1.73c-.808.367-1.638-.417-1.365-1.225l.04-.1l1.73-3.805a1.5 1.5 0 0 1 .213-.34l.09-.1L16.097 4.368Zm2.121 1.414a.5.5 0 0 0-.638-.057l-.069.057L6.678 16.614l-.589 1.297l1.296-.59L18.217 6.49a.5.5 0 0 0 0-.708"/></g></svg>
                    
                    <!-- {{ $primaryEditor === 'tiptap' ? 'Tiptap' : 'TinyMCE' }} -->
                      Default Editor
                </button>
                <button
                    type="button"
                    data-switch-visual
                    class="editor-mode-button hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-1 text-sm font-medium transition"
                >
                    <svg width="20" height="20" viewBox="0 0 512 512"><path fill="currentColor" d="m491.693 256.705l-54.957-49.461l16.407-13.406a80.5 80.5 0 0 0 18.363-21.522c18.148-31.441 12.867-70.042-13.144-96.052s-64.612-31.291-96.051-13.142a80.5 80.5 0 0 0-21.52 18.362l-13.408 16.407l-49.461-54.956l-.579-.611a24.03 24.03 0 0 0-33.941 0l-65.6 65.605l1.19 23.7l33.108 27.056a48.6 48.6 0 0 1 11.079 12.889c10.807 18.722 7.57 41.8-8.056 57.426s-38.7 18.862-57.426 8.058a48.7 48.7 0 0 1-12.9-11.086l-27.047-33.1l-23.7-1.189l-71.26 71.26a24 24 0 0 0 0 33.942l175.357 175.359a80 80 0 0 0 113.138 0L492.3 291.225a24.03 24.03 0 0 0 0-33.94ZM288.657 449.617a48 48 0 0 1-67.883 0L51.069 279.911l53.1-53.095l15.91 19.473l.1.119a80.5 80.5 0 0 0 21.521 18.363c31.441 18.149 70.041 12.867 96.052-13.144s31.291-64.61 13.143-96.05a80.5 80.5 0 0 0-18.363-21.521l-19.591-16.01l47.124-47.124l56.018 62.241l24.282-.579l25.062-30.67a48.6 48.6 0 0 1 12.888-11.078c18.722-10.807 41.8-7.569 57.426 8.056s18.864 38.7 8.057 57.426a48.6 48.6 0 0 1-11.079 12.889l-30.67 25.061l-.58 24.282l62.243 56.018Z"/></svg>
                    Visual Builder
                </button>
                @if ($allowFullscreen)
                    <button
                        type="button"
                        data-editor-fullscreen-toggle
                        class="editor-mode-button hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-1 text-sm font-medium transition"
                        aria-pressed="false"
                        title="Enter fullscreen"
                    >
                        <svg data-editor-fullscreen-enter xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 4h13a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3m0 1a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3h7v7h-1V9.71l-7.15 7.14l-.7-.7L15.29 9H10z"/></svg>
                        <svg data-editor-fullscreen-exit class="hidden" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 21H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h13a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3Zm0-1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h13Zm-5-3H6v-7h1v5.293l7.146-7.147l.708.708L7.707 16H13v1Z"/></svg>
                        <span data-editor-fullscreen-label>Fullscreen</span>
                    </button>
                @endif
            </div>
        </div>
    @elseif ($showHeader)
        <div class="editor-shell-header flex items-center justify-between">
            <h3 class="card-title">{{ $label }}</h3>
            @if ($allowFullscreen)
                <button
                    type="button"
                    data-editor-fullscreen-toggle
                    class="editor-mode-button inline-flex items-center gap-2 rounded-lg px-3 py-1 text-sm font-medium transition"
                    aria-pressed="false"
                    title="Enter fullscreen"
                >
                    <svg data-editor-fullscreen-enter xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 4h13a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3m0 1a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3h7v7h-1V9.71l-7.15 7.14l-.7-.7L15.29 9H10z"/></svg>
                    <svg data-editor-fullscreen-exit class="hidden" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 21H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h13a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3Zm0-1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h13Zm-5-3H6v-7h1v5.293l7.146-7.147l.708.708L7.707 16H13v1Z"/></svg>
                    <span data-editor-fullscreen-label>Fullscreen</span>
                </button>
            @endif
        </div>
    @endif

    <div data-editor-pane="primary" class="{{ $hasVisual ? '' : 'mt-0' }}">
        @if ($primaryEditor === 'tiptap')
            <div
                data-tiptap-root
                data-toolbar-mode-default="{{ $resolvedToolbarPresentation === 'all' ? 'all' : 'basic' }}"
                data-toolbar-presentation="{{ $resolvedToolbarPresentation }}"
                class="overflow-hidden rounded-[22px] wc-editor-frame"
            >
                <div data-editor-toolbar data-toolbar-mode="basic" class="wc-editor-toolbar px-3 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($showBasicTool('heading'))
                            <select data-editor-heading class="editor-toolbar-select h-11 rounded-full text-sm outline-none transition">
                                <option value="paragraph">Paragraph</option>
                                <option value="1">Heading 1</option>
                                <option value="2">Heading 2</option>
                                <option value="3">Heading 3</option>
                            </select>
                        @endif

                        @if ($hasAnyBasic(['undo', 'redo']))
                            <div class="editor-toolbar-group" data-toolbar-mode-group="basic">
                                @if ($showBasicTool('undo'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="undo" title="Undo" aria-label="Undo">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m4 10l-.707.707L2.586 10l.707-.707zm17 8a1 1 0 1 1-2 0zM8.293 15.707l-5-5l1.414-1.414l5 5zm-5-6.414l5-5l1.414 1.414l-5 5zM4 9h10v2H4zm17 7v2h-2v-2zm-7-7a7 7 0 0 1 7 7h-2a5 5 0 0 0-5-5z"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('redo'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="redo" title="Redo" aria-label="Redo">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m20 10l.707.707l.707-.707l-.707-.707zM3 18a1 1 0 1 0 2 0zm12.707-2.293l5-5l-1.414-1.414l-5 5zm5-6.414l-5-5l-1.414 1.414l5 5zM20 9H10v2h10zM3 16v2h2v-2zm7-7a7 7 0 0 0-7 7h2a5 5 0 0 1 5-5z"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyBasic(['bold', 'italic', 'underline', 'strike']))
                            <div class="editor-toolbar-group" data-toolbar-mode-group="basic">
                                @if ($showBasicTool('bold'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="bold" title="Bold" aria-label="Bold">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 12H14C16.2091 12 18 10.2091 18 8C18 5.79086 16.2091 4 14 4H6V12ZM6 12H15C17.2091 12 19 13.7909 19 16C19 18.2091 17.2091 20 15 20H6V12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('italic'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="italic" title="Italic" aria-label="Italic">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 4H10M14 20H5M15 4L9 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('underline'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="underline" title="Underline" aria-label="Underline">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 4V11C18 14.3137 15.3137 17 12 17C8.68629 17 6 14.3137 6 11V4M4 21H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('strike'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="strike" title="Strikethrough" aria-label="Strikethrough">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 16C6 18.2091 7.79086 20 10 20H14C16.2091 20 18 18.2091 18 16C18 13.7909 16.2091 12 14 12M18 8C18 5.79086 16.2091 4 14 4H10C7.79086 4 6 5.79086 6 8M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if ($showAdvancedTool('fontSize'))
                            <div class="editor-toolbar-group hidden" data-toolbar-mode-group="advanced" hidden>
                                <button type="button" class="editor-toolbar-button" data-editor-command="fontSizeDecrease" title="Decrease text size" aria-label="Decrease text size">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="M3.5 13h6"/><path d="m2 16 4.5-9 4.5 9"/><path d="M18 7v9"/><path d="m14 12 4 4 4-4"/></svg>
                                </button>
                                <span class="inline-flex min-w-11 items-center justify-center text-xs font-semibold text-slate-500" data-editor-font-size>16px</span>
                                <button type="button" class="editor-toolbar-button" data-editor-command="fontSizeIncrease" title="Increase text size" aria-label="Increase text size">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="M3.5 13h6"/><path d="m2 16 4.5-9 4.5 9"/><path d="M18 16V7"/><path d="m14 11 4-4 4 4"/></svg>
                                </button>
                            </div>
                        @endif

                        @if ($hasAnyBasic(['bulletList', 'orderedList', 'blockquote', 'codeBlock']))
                            <div class="editor-toolbar-group" data-toolbar-mode-group="basic">
                                @if ($showBasicTool('bulletList'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="bulletList" title="Bullet list" aria-label="Bullet list">
                                        <svg viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 2.5a1 1 0 1 1-2 0a1 1 0 0 1 2 0M1 8a1 1 0 1 0 0-2a1 1 0 0 0 0 2m0 4.5a1 1 0 1 0 0-2a1 1 0 0 0 0 2M4.75 1.75a.75.75 0 0 0 0 1.5h8.5a.75.75 0 0 0 0-1.5zM4 7a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 4 7m.75 3.75a.75.75 0 0 0 0 1.5h8.5a.75.75 0 0 0 0-1.5z" clip-rule="evenodd"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('orderedList'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="orderedList" title="Numbered list" aria-label="Numbered list">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 22v-1.5h2.5v-.75H4v-1.5h1.5v-.75H3V16h3q.425 0 .713.288T7 17v1q0 .425-.288.713T6 19q.425 0 .713.288T7 20v1q0 .425-.288.713T6 22zm0-7v-2.75q0-.425.288-.712T4 11.25h1.5v-.75H3V9h3q.425 0 .713.288T7 10v1.75q0 .425-.288.713T6 12.75H4.5v.75H7V15zm1.5-7V3.5H3V2h3v6zM9 19v-2h12v2zm0-6v-2h12v2zm0-6V5h12v2z"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('blockquote'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="blockquote" title="Quote" aria-label="Quote">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.983 3v7.391C9.983 16.095 6.252 19.961 1 21l-.995-2.151C2.437 17.932 4 15.211 4 13H0V3zM24 3v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151C16.437 17.932 18 15.211 18 13h-3.983V3z"/></svg>
                                    </button>
                                @endif
                                @if ($showBasicTool('codeBlock'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="codeBlock" title="Code block" aria-label="Code block">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 17L22 12L17 7M7 7L2 12L7 17M14 3L10 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyAdvanced(['alignLeft', 'alignCenter', 'alignRight']))
                            <div class="editor-toolbar-group hidden" data-toolbar-mode-group="advanced" hidden>
                                @if ($showAdvancedTool('alignLeft'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="alignLeft" title="Align left" aria-label="Align left"><svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path d="M3.75 5a.75.75 0 0 1 .75-.75h11a.75.75 0 0 1 0 1.5h-11A.75.75 0 0 1 3.75 5Zm0 5a.75.75 0 0 1 .75-.75h8a.75.75 0 0 1 0 1.5h-8A.75.75 0 0 1 3.75 10Zm0 5a.75.75 0 0 1 .75-.75h11a.75.75 0 0 1 0 1.5h-11a.75.75 0 0 1-.75-.75Z"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('alignCenter'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="alignCenter" title="Align center" aria-label="Align center"><svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path d="M4.75 5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9A.75.75 0 0 1 4.75 5Zm-2 5a.75.75 0 0 1 .75-.75h13a.75.75 0 0 1 0 1.5h-13A.75.75 0 0 1 2.75 10Zm2 5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9A.75.75 0 0 1 4.75 15Z"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('alignRight'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="alignRight" title="Align right" aria-label="Align right"><svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path d="M4.5 4.25a.75.75 0 0 0 0 1.5h11a.75.75 0 0 0 0-1.5h-11ZM7.5 9.25a.75.75 0 0 0 0 1.5h8a.75.75 0 0 0 0-1.5h-8ZM4.5 14.25a.75.75 0 0 0 0 1.5h11a.75.75 0 0 0 0-1.5h-11Z"/></svg></button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyAdvanced(['subscript', 'superscript', 'horizontalRule']))
                            <div class="editor-toolbar-group hidden" data-toolbar-mode-group="advanced" hidden>
                                @if ($showAdvancedTool('subscript'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="subscript" title="Subscript" aria-label="Subscript"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 7 8 10m-8 0 8-10m8 13h-4l3.5-4a1.73 1.73 0 0 0-3.5-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('superscript'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="superscript" title="Superscript" aria-label="Superscript"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 7 8 10m-8 0 8-10m8 4h-4l3.5-4A1.73 1.73 0 0 0 17 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('horizontalRule'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="horizontalRule" title="Divider" aria-label="Divider"><svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path d="M3 10a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Z"/></svg></button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyBasic(['link', 'image', 'imageUpload', 'mediaGallery', 'youtube']))
                            <div class="editor-toolbar-group" data-toolbar-mode-group="basic">
                                @if ($showBasicTool('link'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="link" title="Link" aria-label="Link"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21.1525 10.8995L12.1369 19.9151C10.0866 21.9653 6.7625 21.9653 4.71225 19.9151C2.662 17.8648 2.662 14.5407 4.71225 12.4904L13.7279 3.47483C15.0947 2.108 17.3108 2.108 18.6776 3.47483C20.0444 4.84167 20.0444 7.05775 18.6776 8.42458L10.0156 17.0866C9.33213 17.7701 8.22409 17.7701 7.54068 17.0866C6.85726 16.4032 6.85726 15.2952 7.54068 14.6118L15.1421 7.01037" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                @endif
                                @if ($showBasicTool('image'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="image" title="Add image using URL" aria-label="Add image using URL"><svg class="h-5 w-5" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M15 8h.01M12.5 21H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v6.5"/><path d="m3 16l5-5c.928-.893 2.072-.893 3 0l4 4"/><path d="m14 14l1-1c.67-.644 1.45-.824 2.182-.54M16 19h6m-3-3v6"/></g></svg></button>
                                @endif
                                @if ($showBasicTool('imageUpload'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="imageUpload" title="Upload image" aria-label="Upload image"><svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path d="M9.25 13.25a.75.75 0 0 0 1.5 0V4.636l2.955 3.129a.75.75 0 0 0 1.09-1.03l-4.25-4.5a.75.75 0 0 0-1.09 0l-4.25 4.5a.75.75 0 1 1 1.09 1.03L9.25 4.636v8.614Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg></button>
                                @endif
                                @if ($showBasicTool('mediaGallery'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="mediaGallery" title="Insert gallery" aria-label="Insert gallery"><svg viewBox="0 0 512 512" class="h-5 w-5"><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M432 112V96a48.14 48.14 0 0 0-48-48H64a48.14 48.14 0 0 0-48 48v256a48.14 48.14 0 0 0 48 48h16"/><rect width="400" height="336" x="96" y="128" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" rx="45.99" ry="45.99"/><ellipse cx="372.92" cy="219.64" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" rx="30.77" ry="30.55"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M342.15 372.17L255 285.78a30.93 30.93 0 0 0-42.18-1.21L96 387.64M265.23 464l118.59-117.73a31 31 0 0 1 41.46-1.87L496 402.91"/></svg></button>
                                @endif
                                @if ($showBasicTool('youtube'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="youtube" title="YouTube video" aria-label="YouTube video"><svg viewBox="0 0 32 32" class="h-5 w-5"><path fill="currentColor" d="M6.5 4A4.5 4.5 0 0 0 2 8.5v15A4.5 4.5 0 0 0 6.5 28h19a4.5 4.5 0 0 0 4.5-4.5v-15A4.5 4.5 0 0 0 25.5 4zM4 8.5A2.5 2.5 0 0 1 6.5 6h19A2.5 2.5 0 0 1 28 8.5v15a2.5 2.5 0 0 1-2.5 2.5h-19A2.5 2.5 0 0 1 4 23.5zm8 3.501V20a1 1 0 0 0 1.47.882l7.498-3.999a1 1 0 0 0 0-1.764l-7.497-4a1 1 0 0 0-1.471.883"/></svg></button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyAdvanced(['insertTable', 'addRowAfter', 'addColumnAfter', 'deleteTable']))
                            <div class="editor-toolbar-group hidden" data-toolbar-mode-group="advanced" hidden>
                                @if ($showAdvancedTool('insertTable'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="insertTable" title="Insert table" aria-label="Insert table"><svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path d="M3.5 4A1.5 1.5 0 0 0 2 5.5v9A1.5 1.5 0 0 0 3.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 16.5 4h-13Zm0 1.5H8v3H3.5v-3Zm6 0h7v3h-7v-3Zm-6 4.5H8v4.5H3.5V10Zm6 4.5V10h7v4.5h-7Z"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('addRowAfter'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="addRowAfter" title="Add row" aria-label="Add row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><rect x="2" y="2" width="20" height="12" rx="2"/><path d="M2 8h20M8 2v12"/><path d="M12 17v6M9 20h6"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('addColumnAfter'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="addColumnAfter" title="Add column" aria-label="Add column"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><rect x="2" y="2" width="12" height="20" rx="2"/><path d="M2 8h12M2 14h12M8 2v20"/><path d="M18 12h6M21 9v6"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('deleteTable'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="deleteTable" title="Delete table" aria-label="Delete table"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><rect x="2" y="2" width="14" height="14" rx="2"/><path d="M2 8h14M8 2v14"/><path d="M17 17l5 5M22 17l-5 5"/></svg></button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyAdvanced(['inlineMath', 'blockMath']))
                            <div class="editor-toolbar-group hidden" data-toolbar-mode-group="advanced" hidden>
                                @if ($showAdvancedTool('inlineMath'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="inlineMath" title="Inline equation" aria-label="Inline equation"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="M5 6h14l-7 6 7 6H5"/></svg></button>
                                @endif
                                @if ($showAdvancedTool('blockMath'))
                                    <button type="button" class="editor-toolbar-button" data-editor-command="blockMath" title="Block equation" aria-label="Block equation"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="2.5"/><path d="M7 8h10l-5 4 5 4H7"/></svg></button>
                                @endif
                            </div>
                        @endif

                        @if ($hasAnyAdvanced(['textColor', 'highlightColor']))
                            <div class="editor-toolbar-group hidden" data-toolbar-mode-group="advanced" hidden>
                                @if ($showAdvancedTool('textColor'))
                                    <label class="editor-toolbar-color">
                                        <span>Text</span>
                                        <input type="color" value="#0f172a" data-editor-text-color title="Text color">
                                    </label>
                                @endif
                                @if ($showAdvancedTool('highlightColor'))
                                    <label class="editor-toolbar-color">
                                        <span>Highlight</span>
                                        <input type="color" value="#fff3b0" data-editor-highlight-color title="Highlight color">
                                    </label>
                                @endif
                            </div>
                        @endif

                        @if ($showBasicTool('clearFormatting'))
                            <div class="editor-toolbar-group" data-toolbar-mode-group="basic">
                                <button type="button" class="editor-toolbar-button" data-editor-command="clearFormatting" title="Clear formatting" aria-label="Clear formatting"><svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M4.29 5.973 3.083 7.57l24.693 18.608 1.203-1.598-10.953-8.254L20.285 10H25v1.5a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-4a.5.5 0 0 0-.5-.5h-16a.5.5 0 0 0-.5.5v2.777zM12 10h5.285l-1.623 4.545-3.728-2.81A.5.5 0 0 0 12 11.5zm2.254 8.49L13 22h-1.5a.5.5 0 0 0-.5.5v2a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 0-.5-.5H16l.62-1.734z"/></svg></button>
                            </div>
                        @endif

                        @if ($hasAdvancedToolbar && $resolvedToolbarPresentation !== 'all')
                            <button type="button" class="editor-toolbar-toggle ml-auto inline-flex h-10 items-center gap-2 rounded-full px-3 text-sm font-medium transition" data-toolbar-mode-toggle aria-expanded="false">
                                <span data-toolbar-mode-label>Advanced</span>
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 transition-transform duration-200" data-toolbar-mode-icon aria-hidden="true"><path fill-rule="evenodd" d="M9.47 16.78a.75.75 0 0 0 1.06 0l5.25-5.25a.75.75 0 1 0-1.06-1.06l-3.97 3.97V4a.75.75 0 0 0-1.5 0v10.44l-3.97-3.97a.75.75 0 1 0-1.06 1.06l5.25 5.25Z" clip-rule="evenodd" /></svg>
                            </button>
                        @endif
                    </div>
                    <input type="file" accept="image/*" class="hidden" data-editor-image-upload>
                </div>

                <div data-rich-editor class="tiptap-editor min-h-[22rem]"></div>
            </div>
        @else
            <textarea data-primary-textarea class="w-full border border-gray-300 rounded-lg" rows="15">{{ $initialContent }}</textarea>
        @endif
    </div>

    @if ($hasVisual)
        <div data-editor-pane="visual" class="hidden">
            <div class="wc-grapes-shell" data-grapes-shell style="height: {{ $visualHeight }}px;">
                <div class="wc-grapes-layout">
                    <aside class="wc-grapes-sidebar wc-grapes-sidebar-left">
                        <div class="wc-grapes-sidebar-header">
                            <h4 class="wc-grapes-sidebar-title">Blocks</h4>
                            <!-- <p class="wc-grapes-sidebar-note">Drag compact content blocks into the canvas.</p> -->
                        </div>
                        <div id="{{ $shellId }}-blocks" data-grapes-blocks class="wc-grapes-sidebar-body"></div>
                    </aside>

                    <div class="wc-grapes-canvas-wrap">
                        <div id="{{ $shellId }}-canvas" data-grapes-canvas class="wc-grapes-canvas">
                            {!! $initialContent !!}
                        </div>
                    </div>

                    <aside class="wc-grapes-sidebar wc-grapes-sidebar-right" data-grapes-right-panel>
                        <div class="wc-grapes-sidebar-header">
                            <div class="wc-grapes-sidebar-header-row">
                                <button type="button" class="wc-grapes-sidebar-icon-button" data-grapes-right-toggle aria-expanded="false" title="Show panel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m10 17l5-5l-5-5z"/></svg>
                                </button>
                                <h4 class="wc-grapes-sidebar-title">Inspector</h4>
                            </div>
                            <div class="wc-grapes-panel-tabbar">
                                <div class="wc-grapes-panel-tabs">
                                    <button type="button" class="wc-grapes-panel-tab is-active" data-grapes-panel-tab="styles">Styles</button>
                                    <button type="button" class="wc-grapes-panel-tab" data-grapes-panel-tab="layers">Layers</button>
                                    <button type="button" class="wc-grapes-panel-tab" data-grapes-panel-tab="traits">Traits</button>
                                    <button type="button" class="wc-grapes-panel-tab" data-grapes-panel-tab="code">Code</button>
                                </div>
                            </div>
                        </div>

                        <div class="wc-grapes-sidebar-body">
                            <div class="wc-grapes-panel is-active" data-grapes-panel="styles">
                                <div id="{{ $shellId }}-styles" data-grapes-styles></div>
                            </div>
                            <div class="wc-grapes-panel" data-grapes-panel="layers">
                                <div id="{{ $shellId }}-layers" data-grapes-layers></div>
                            </div>
                            <div class="wc-grapes-panel" data-grapes-panel="traits">
                                <div id="{{ $shellId }}-traits" data-grapes-traits></div>
                            </div>
                            <div class="wc-grapes-panel" data-grapes-panel="code">
                                <div class="wc-grapes-code-panel">
                                    <label class="wc-grapes-code-label" for="{{ $shellId }}-code">HTML + CSS</label>
                                    <textarea id="{{ $shellId }}-code" data-grapes-code-editor class="wc-grapes-code-textarea" spellcheck="false"></textarea>
                                    <p class="wc-grapes-code-help">Use regular HTML. Place custom CSS inside one trailing <code>&lt;style&gt;</code> block.</p>
                                    <div class="wc-grapes-code-actions">
                                        <button type="button" class="btn-outline" data-grapes-code-load>Load from canvas</button>
                                        <button type="button" class="btn-base btn-primary" data-grapes-code-apply>Apply changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    @endif
</div>
