@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Static Pages', 'url' => route('dashboard.web_curator.pages.index')],
            ['label' => 'Add New'],
        ]" />
        <h2 class="page-title">Create New Static Page</h2>
        <p class="text-sm text-gray-600 mt-1">Add a new page to your entity website</p>
    </div>

    <form method="POST" action="{{ route('dashboard.web_curator.pages.store') }}" 
          data-web-curator-form
          data-enable-quick-save="true"
          class="space-y-6"
          x-data="pageForm" x-init="init()" @submit.prevent="syncAndSubmit($event)">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Basic Information</h3>
            </div>
            
            <div class="mb-4">
                <label class="label-base label-required">Page Title</label>
                <input type="text" name="page_title" value="{{ old('page_title') }}"
                       x-model="pageTitle" @input="generateSlug"
                       class="input-base @error('page_title') border-red-500 @enderror" 
                       placeholder="Enter the page title"
                       required>
                @error('page_title')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="label-base">Slug</label>
                <input type="text" name="page_slug" value="{{ old('page_slug') }}"
                       x-model="pageSlug" @input="slugManuallyEdited = true"
                       placeholder="Auto-generated from title if left empty"
                       class="input-base @error('page_slug') border-red-500 @enderror">
                @error('page_slug')
                    <p class="error-text">{{ $message }}</p>
                @enderror
                <!-- <p class="help-text">
                    <span x-show="!slugManuallyEdited" class="text-emerald-600">Auto-generating from title...</span>
                    <span x-show="slugManuallyEdited" class="text-green-600">✓ Using custom slug</span>
                </p> -->
            </div>

            <div class="mb-4">
                <label class="label-base">Excerpt</label>
                <textarea name="page_excerpt" rows="3"
                          class="textarea-base"
                          placeholder="Brief summary or description...">{{ old('page_excerpt') }}</textarea>
                <p class="help-text">Short description shown in page listings and previews</p>
            </div>

        </div>

        <div class="card" x-data="pagePlacementState({
                selectedCategory: '{{ old('page_category') }}',
                selectedSubcategory: '{{ old('page_subcategory') }}',
                isMenu: {{ old('is_menu', 'false') }},
                menuOrder: '{{ old('menu_order', '999') }}',
                categoryMeta: @js($categories->keyBy('id')->map(fn($cat) => [
                    'is_menu' => $cat->is_menu,
                    'subcategories' => $cat->subcategories->map(fn($sub) => [
                        'id' => (string) $sub->id,
                        'label' => ($sub->is_menu ? ' 📁 ' : '') . $sub->subcategory_name,
                        'is_menu' => $sub->is_menu,
                    ])->values(),
                ])),
            })" x-init="init()">
            <div class="card-header">
                <h3 class="card-title">Placement & Navigation</h3>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Category -->
                    <div>
                        <label class="label-base">Category</label>
                        <x-custom-select
                            :options="collect([['value' => '', 'label' => 'None']])
                                ->merge($categories->map(fn($cat) => [
                                    'value' => (string) $cat->id,
                                    'label' => ($cat->is_menu ? ' 📂 ' : '') . $cat->category_name,
                                ]))
                                ->values()
                                ->all()"
                            :value="old('page_category', '')"
                            name="page_category"
                            placeholder="Select category"
                            @select-change="selectedCategory = $event.detail.value"
                        />
                        <p class="help-text" x-show="categoryIsMenu">
                            📂 This category is a menu - you can make this page a submenu
                        </p>
                    </div>

                    <!-- Subcategory -->
                    <div x-show="selectedCategory">
                        <label class="label-base">Subcategory</label>
                        <x-custom-select
                            data-subcategory-select
                            :options="[['value' => '', 'label' => 'None']]"
                            :value="old('page_subcategory', '')"
                            name="page_subcategory"
                            placeholder="Select subcategory"
                            @select-change="selectedSubcategory = $event.detail.value"
                        />
                        <p class="help-text" x-show="subcategoryIsMenu">
                            📁 This subcategory is a submenu
                        </p>
                    </div>
                </div>

                {{-- Menu Options - Top-level or Submenu --}}
                <div x-show="shouldShowMenuOptions" x-collapse class="rounded-2xl p-4 bg-transparent border transition-colors duration-200"
                    :class="isMenu ? 'border-emerald-600/50' : 'border-emerald-600/25'">                     
                    <div class="flex items-center justify-between gap-3">                        
                        <label for="is_menu" class="flex-1 cursor-pointer text-sm font-medium" style="color: var(--accent);">
                            <span x-show="!selectedCategory" class="flex items-center gap-2">
                                <!-- <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M4.5 17.27q-.213 0-.356-.145T4 16.768t.144-.356t.356-.143h15q.213 0 .356.144q.144.144.144.357t-.144.356t-.356.143zm0-4.77q-.213 0-.356-.144T4 11.999t.144-.356t.356-.143h15q.213 0 .356.144t.144.357t-.144.356t-.356.143zm0-4.77q-.213 0-.356-.143Q4 7.443 4 7.23t.144-.356t.356-.143h15q.213 0 .356.144T20 7.23t-.144.356t-.356.144z"/></svg> -->
                                 <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M5.5 3.483c0-1.248 1.436-1.95 2.421-1.184l13.514 10.513c1.128.877.508 2.684-.92 2.684h-6.853c-.505 0-.981.23-1.294.626l-4.191 5.3c-.882 1.116-2.677.492-2.677-.93zm15.014 10.513L7 3.483v17.009l4.191-5.3a3.15 3.15 0 0 1 2.47-1.196z"/></svg>
                                Make this page a top-level menu
                            </span>
                            <span x-show="selectedCategory" class="flex items-center gap-2">
                                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M5.5 3.483c0-1.248 1.436-1.95 2.421-1.184l13.514 10.513c1.128.877.508 2.684-.92 2.684h-6.853c-.505 0-.981.23-1.294.626l-4.191 5.3c-.882 1.116-2.677.492-2.677-.93zm15.014 10.513L7 3.483v17.009l4.191-5.3a3.15 3.15 0 0 1 2.47-1.196z"/></svg>
                                Make this page a submenu
                            </span>
                        </label>
                        <input type="checkbox" 
                               id="is_menu" 
                               name="is_menu" 
                               value="1"
                               x-model="isMenu"
                               class="toggle-switch">
                    </div>

                    <div x-show="isMenu" x-collapse class="ml-7 mt-3 space-y-3 empty:hidden">
                        <div class="rounded-xl px-4 py-3 text-sm border border-emerald-600/25">
                            <span class="label-base">Menu Placement</span>
                            <template x-if="!selectedCategory">
                                <span class="text-green-700">This page will appear as a <strong>top-level menu</strong> in the main navigation.</span>
                            </template>
                            <template x-if="selectedCategory && !selectedSubcategory">
                                <span class="text-green-700">This page will appear <strong>directly under</strong> the category menu.</span>
                            </template>
                            <template x-if="subcategoryIsMenu">
                                <span class="text-green-700">This page will appear <strong>under</strong> the subcategory submenu.</span>
                            </template>
                            <template x-if="selectedCategory && selectedSubcategory && !subcategoryIsMenu">
                                <span class="text-yellow-700">⚠️ The selected subcategory is not a submenu. Page will appear under the parent category menu instead.</span>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label-base">Menu Order</label>
                                <input type="number" 
                                       name="menu_order" 
                                       x-model="menuOrder"
                                       value="{{ old('menu_order', 999) }}"
                                       min="0"
                                       class="input-base">
                                <p class="help-text">Lower numbers appear first (default: 999)</p>
                            </div>

                            <div>
                                <label class="label-base">Menu Text <span class="text-xs text-gray-500">(optional)</span></label>
                                <input type="text" name="menu_text" value="{{ old('menu_text') }}"
                                    placeholder="Text to display in navigation menu"
                                    class="input-base">
                                <p class="help-text">If empty, page title will be used in menus</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Publishing</h3>
            </div>
            <div class="space-y-4">
            <div x-data="featuredImageField(@js(old('featured_image_uri', '')))">
                <label class="label-base">Featured Image</label>
                
                <div class="flex flex-wrap gap-3">
                    <div class="flex w-full sm:w-auto grow h-10 items-stretch overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--surface)]">
                        <input type="text" 
                                name="featured_image_uri" 
                                x-model="imageUrl"
                                @input="updatePreview($event.target.value)"
                                placeholder="https://example.com/image.jpg"
                                class="input-base h-10 flex-1 border-0 bg-transparent focus:border-transparent focus:ring-0 rounded-none">
                        <button
                            type="button"
                            x-show="hasImageUrl"
                            x-cloak
                            @click="clearSelection()"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center border-0 border-l border-[var(--border)] bg-[var(--surface)] px-0 text-[var(--text-soft)] transition-colors hover:bg-[var(--surface-raised)] hover:text-[var(--color-text-accent)]"
                            aria-label="Clear featured image"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71a.996.996 0 0 0-1.41 0L12 10.59L7.11 5.7A.996.996 0 1 0 5.7 7.11L10.59 12L5.7 16.89a.996.996 0 1 0 1.41 1.41L12 13.41l4.89 4.89a.996.996 0 1 0 1.41-1.41L13.41 12l4.89-4.89c.38-.38.38-1.02 0-1.4"/></svg>
                        </button>
                    </div>
                    <button type="button" 
                            @click="openFeaturedMediaPicker(false)"
                            class="btn-base btn-secondary h-10 gap-2 grow sm:grow-0">                      
                        <svg class="h-5 w-5" viewBox="0 0 512 512"><path d="M457.6 140.2l-82.5-4-4.8-53.8c-1-11.3-11.1-19.2-22.9-18.3L51.5 88.4c-11.8 1-20.3 10.5-19.4 21.7l21.2 235.8c1 11.3 11.2 19.2 22.9 18.3l15-1.2-2.4 45.8c-.6 12.6 9.2 22.8 22.4 23.5L441.3 448c13.2.6 24.1-8.6 24.8-21.2L480 163.5c.6-12.5-9.3-22.7-22.4-23.3zm-354.9 5.3l-7.1 134.8L78.1 305 62 127v-.5-.5c1-5 4.4-9 9.6-9.4l261-21.4c5.2-.4 9.7 3 10.5 7.9 0 .2.3.2.3.4 0 .1.3.2.3.4l2.7 30.8-219-10.5c-13.2-.4-24.1 8.8-24.7 21.3zm334 236.9l-84.8-99.5-37.4 34.3-69.2-80.8-122.7 130.7L133 168v-.4c1-5.4 6.2-9.3 11.9-9l291.2 14c5.8.3 10.3 4.7 10.4 10.2 0 .2.3.3.3.5l-10.1 199.1z" fill="currentColor"/><path d="M384 256c17.6 0 32-14.4 32-32s-14.3-32-32-32c-17.6 0-32 14.3-32 32s14.3 32 32 32z" fill="currentColor"/></svg>
                        Library
                    </button>
                    <button type="button" 
                            @click="openFeaturedMediaPicker(true)"
                            class="btn-base btn-secondary h-10 gap-2 grow sm:grow-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path>
                        </svg>
                        <span>Upload</span>
                    </button>
                </div>

                {{-- Live Preview - Works with URLs and Base64 --}}
                <div x-show="imageUrl && imageUrl.trim() !== ''" class="mt-3">
                    <div class="relative inline-block">
                        <img :src="imageUrl" 
                             alt="Featured image preview" 
                             x-on:load="imageError = false"
                             x-on:error="imageError = true"
                             class="max-w-xs max-h-48 rounded-xl object-cover"
                             style="border: 1px solid var(--border-soft); box-shadow: var(--shadow);">
                        <div x-show="imageError" class="max-w-xs p-4 bg-red-50 border border-red-200 rounded text-red-600 text-sm">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Failed to load image. Check URL or base64 format.
                        </div>
                    </div>
                </div>
                
                <p class="mt-1 text-xs text-gray-500">
                    Supports external image URLs or media picked from your library.
                </p>
            </div>

            <div>
                <label class="label-base label-required">Status</label>
                <div x-data="customSelect({
                    options: [
                        {value: 'Draft', label: 'Draft'},
                        {value: 'Published', label: 'Published'}
                    ],
                    placeholder: 'Select status',
                    name: 'page_status',
                    value: '{{ old('page_status', 'Draft') }}',
                    required: true,
                    editable: false
                })">
                    <x-custom-select-template />
                </div>
            </div>
        </div>
        </div>

        {{-- Content Editor Section --}}
        <div class="card !p-0 overflow-hidden">
            @include('web_curator::partials.editor-shell', [
                'shellId' => 'page-create-content',
                'fieldName' => 'page_content',
                'initialContent' => old('page_content', ''),
                'label' => 'Page Content',
                'showHeader' => true,
                'primaryPlaceholder' => 'Write the page content here...',
                'enableVisual' => true,
                'defaultMode' => 'primary',
                'allowFullscreen' => true,
                'stickyToolbar' => true,
                'visualDefaultBlock' => 'layout-one-column',
                'mediaUploadContext' => 'page',
            ])
        </div>

        {{-- Custom CSS/JS Section --}}
        <div class="card" x-data="{ showAdvanced: false }">
            <div class="flex items-center justify-between">
                <h3 class="card-title ">Advanced Customization</h3>
                <button type="button" 
                        class="text-sm text-emerald-600 hover:text-emerald-700 font-medium flex items-center gap-1"
                        @click="showAdvanced = !showAdvanced">
                    <span x-show="!showAdvanced" class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><!-- Icon from Solar by 480 Design - https://creativecommons.org/licenses/by/4.0/ --><path fill="currentColor" fill-rule="evenodd" d="M4.43 8.512a.75.75 0 0 1 1.058-.081L12 14.012l6.512-5.581a.75.75 0 0 1 .976 1.138l-7 6a.75.75 0 0 1-.976 0l-7-6a.75.75 0 0 1-.081-1.057" clip-rule="evenodd"/></svg>
                        Show Custom CSS/JS
                    </span>
                <span x-show="showAdvanced" class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><!-- Icon from Solar by 480 Design - https://creativecommons.org/licenses/by/4.0/ --><path fill="currentColor" fill-rule="evenodd" d="M11.512 8.43a.75.75 0 0 1 .976 0l7 6a.75.75 0 1 1-.976 1.14L12 9.987l-6.512 5.581a.75.75 0 1 1-.976-1.138z" clip-rule="evenodd"/></svg>
                        Hide Custom CSS/JS
                    </span>
                </button>
            </div>

            <div x-show="showAdvanced" x-cloak class="space-y-4 empty:hidden mt-4 border-t border-[var(--border-soft)] pt-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Custom CSS</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="formatCode('custom_css')" 
                                    class="text-xs text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                Format
                            </button>
                            <button type="button" onclick="validateCSS()" 
                                    class="text-xs text-green-600 hover:text-green-700 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Validate
                            </button>
                        </div>
                    </div>
                    <textarea id="custom_css" name="custom_css" rows="8" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-emerald-600 focus:border-transparent bg-gray-50"
                              placeholder=".my-class {&#10;    color: blue;&#10;    font-size: 16px;&#10;}" 
                              spellcheck="false"
                              onblur="validateCSS()">{{ old('custom_css') }}</textarea>
                    <div id="css_validation_feedback"></div>
                    <p class="text-xs text-gray-500">Additional CSS styles for this page only</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Custom JavaScript</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="formatCode('custom_js')" 
                                    class="text-xs text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                Format
                            </button>
                            <button type="button" onclick="validateJS()" 
                                    class="text-xs text-green-600 hover:text-green-700 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Validate
                            </button>
                        </div>
                    </div>
                    <textarea id="custom_js" name="custom_js" rows="8" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-emerald-600 focus:border-transparent bg-gray-50"
                              placeholder="document.addEventListener('DOMContentLoaded', function() {&#10;    console.log('Page loaded');&#10;});" 
                              spellcheck="false"
                              onblur="validateJS()">{{ old('custom_js') }}</textarea>
                    <div id="js_validation_feedback"></div>
                    <p class="text-xs text-gray-500">Custom JavaScript code for this page only</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard.web_curator.pages.index') }}" 
               class="btn-base btn-secondary">
                Cancel
            </a>
            <button type="submit" 
                    class="btn-base btn-primary"
                    :disabled="isSubmitting"
                    :class="isSubmitting ? 'cursor-not-allowed opacity-70' : ''">
                <span x-show="!isSubmitting">Save Page</span>
                <span x-show="isSubmitting" x-cloak>Saving...</span>
            </button>
        </div>
    </form>
</div>

@include('web_curator::partials.media-picker', [
    'mediaType' => 'image',
    'libraryUrl' => route('dashboard.web_curator.media.library-items'),
    'uploadUrl' => route('dashboard.web_curator.media.upload'),
    'uploadContext' => 'page',
])

@push('styles')
<style>
</style>
@endpush

@push('scripts')
<script>
// Helper function to decode HTML entities
function decodeHtmlEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('featuredImageField', (initialUrl = '') => ({
        imageUrl: initialUrl || '',
        imageError: false,

        get hasImageUrl() {
            return String(this.imageUrl || '').trim() !== '';
        },

        updatePreview(value) {
            this.imageUrl = value;
            this.imageError = false;
        },

        clearSelection() {
            const input = this.$root.querySelector("input[name='featured_image_uri']");
            this.imageUrl = '';
            this.imageError = false;

            if (!input) {
                return;
            }

            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        },

        async openFeaturedMediaPicker(preferUpload = false) {
            const picker = window.WebCuratorMediaPicker;
            if (!picker?.open) {
                return;
            }

            const item = await picker.open({
                title: 'Choose featured image',
                mediaType: 'image',
                uploadContext: 'page',
                preferUpload,
            });

            if (!item) {
                return;
            }

            const url = item.full_url || item.public_url || '';
            if (!url) {
                return;
            }

            const input = this.$root.querySelector("input[name='featured_image_uri']");
            if (!input) {
                return;
            }

            input.value = url;
            this.updatePreview(url);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        },
    }));

    Alpine.data('pagePlacementState', (config = {}) => ({
        selectedCategory: config.selectedCategory || '',
        selectedSubcategory: config.selectedSubcategory || '',
        isMenu: config.isMenu === true || config.isMenu === 1 || config.isMenu === '1' || config.isMenu === 'true',
        menuOrder: config.menuOrder || '999',
        categoryMeta: config.categoryMeta || {},
        categoryIsMenu: false,
        subcategoryIsMenu: false,
        availableMenuSubcats: [],

        get shouldShowMenuOptions() {
            return true;
        },

        getSubcategoryOptions() {
            const category = this.selectedCategory ? this.categoryMeta[this.selectedCategory] : null;
            return [
                { value: '', label: 'None' },
                ...(category ? category.subcategories.map(sub => ({ value: sub.id, label: sub.label })) : []),
            ];
        },

        syncSubcategorySelect() {
            this.$nextTick(() => {
                const selectEl = this.$root.querySelector('[data-subcategory-select]');
                if (!selectEl || typeof Alpine === 'undefined' || typeof Alpine.$data !== 'function') {
                    return;
                }

                const select = Alpine.$data(selectEl);
                if (!select) {
                    return;
                }

                select.setOptions(this.getSubcategoryOptions());
                select.setValue(this.selectedSubcategory ? String(this.selectedSubcategory) : '');
                select.setDisabled(!this.selectedCategory);
            });
        },

        updateSubcategoryState() {
            const category = this.selectedCategory ? this.categoryMeta[this.selectedCategory] : null;
            this.categoryIsMenu = !!(category && category.is_menu);
            this.availableMenuSubcats = category ? category.subcategories.filter(sub => sub.is_menu) : [];

            const availableSubcategoryIds = category ? category.subcategories.map(sub => sub.id) : [];
            if (this.selectedSubcategory && !availableSubcategoryIds.includes(String(this.selectedSubcategory))) {
                this.selectedSubcategory = '';
            }

            const selectedSub = category
                ? category.subcategories.find(sub => sub.id === String(this.selectedSubcategory))
                : null;

            this.subcategoryIsMenu = !!(selectedSub && selectedSub.is_menu);
            this.syncSubcategorySelect();
        },

        init() {
            this.$watch('selectedCategory', () => {
                this.updateSubcategoryState();
            });

            this.$watch('selectedSubcategory', () => {
                this.updateSubcategoryState();
            });

            this.updateSubcategoryState();
        },
    }));

    Alpine.data('pageForm', () => ({
        pageTitle: '{{ old('page_title') ? addslashes(html_entity_decode(old('page_title'))) : '' }}',
        pageSlug: '{{ old('page_slug') }}',
        slugManuallyEdited: {{ old('page_slug') ? 'true' : 'false' }},
        isSubmitting: false,

        generateSlug() {
            if (!this.slugManuallyEdited) {
                this.pageSlug = this.pageTitle
                    ? this.pageTitle
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                    : '';
            }
        },

        init() {
        },

        async syncAndSubmit(event) {
            if (this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            try {
                await window.WebCuratorEditors.prepareFormSubmission(event.currentTarget);
                event.currentTarget.submit();
            } catch (error) {
                this.isSubmitting = false;
                alert(error?.message || 'Failed to prepare the page for submission.');
            }
        }
    }));
});

// Basic code formatter for CSS/JS
function formatCode(textareaId) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;
    
    let code = textarea.value.trim();
    if (!code) return;
    
    // Basic formatting: add proper indentation
    let formatted = '';
    let indentLevel = 0;
    const indentChar = '    '; // 4 spaces
    
    // Remove existing indentation and split by lines
    const lines = code.split('\n').map(line => line.trim());
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (!line) {
            formatted += '\n';
            continue;
        }
        
        // Decrease indent for closing braces
        if (line.startsWith('}') || line.startsWith(']') || line.startsWith(')')) {
            indentLevel = Math.max(0, indentLevel - 1);
        }
        
        // Add indentation
        formatted += indentChar.repeat(indentLevel) + line + '\n';
        
        // Increase indent after opening braces
        if (line.endsWith('{') || line.endsWith('[') || line.endsWith('(')) {
            indentLevel++;
        }
        
        // Handle lines with both opening and closing (like "} else {")
        if ((line.includes('}') && line.includes('{')) || 
            (line.includes(']') && line.includes('['))) {
            // Keep indent level the same
        }
    }
    
    textarea.value = formatted.trim();
    console.log('Code formatted for', textareaId);
}

// Validate CSS syntax
function validateCSS() {
    const textarea = document.getElementById('custom_css');
    const feedback = document.getElementById('css_validation_feedback');
    if (!textarea || !feedback) return;
    
    const css = textarea.value.trim();
    if (!css) {
        feedback.innerHTML = '';
        return;
    }
    
    // Basic CSS validation
    const errors = [];
    const braceCount = (css.match(/{/g) || []).length - (css.match(/}/g) || []).length;
    
    if (braceCount !== 0) {
        errors.push(`Mismatched braces (${Math.abs(braceCount)} ${braceCount > 0 ? 'missing closing' : 'extra'} brace${Math.abs(braceCount) > 1 ? 's' : ''})`);
    }
    
    // Check for basic syntax issues
    const lines = css.split('\n');
    lines.forEach((line, i) => {
        const trimmed = line.trim();
        // Property lines should end with ; or be a selector/brace
        if (trimmed && 
            !trimmed.endsWith('{') && 
            !trimmed.endsWith('}') && 
            !trimmed.endsWith(';') && 
            !trimmed.startsWith('/*') && 
            !trimmed.endsWith('*/') &&
            !trimmed.startsWith('@') &&
            trimmed !== '') {
            // Might be missing semicolon
            if (trimmed.includes(':') && !trimmed.includes('/*')) {
                errors.push(`Line ${i + 1}: Missing semicolon?`);
            }
        }
    });
    
    if (errors.length > 0) {
        feedback.innerHTML = `<div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
            <strong>Potential issues:</strong><br>
            ${errors.slice(0, 3).join('<br>')}
            ${errors.length > 3 ? '<br>... and ' + (errors.length - 3) + ' more issue(s)' : ''}
        </div>`;
    } else {
        feedback.innerHTML = `<div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-800">
            CSS looks good!
        </div>`;
        setTimeout(() => feedback.innerHTML = '', 3000);
    }
}

// Validate JavaScript syntax
function validateJS() {
    const textarea = document.getElementById('custom_js');
    const feedback = document.getElementById('js_validation_feedback');
    if (!textarea || !feedback) return;
    
    const js = textarea.value.trim();
    if (!js) {
        feedback.innerHTML = '';
        return;
    }
    
    try {
        // Try to create a Function to check syntax (doesn't execute)
        new Function(js);
        feedback.innerHTML = `<div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-800">
            JavaScript syntax is valid!
        </div>`;
        setTimeout(() => feedback.innerHTML = '', 3000);
    } catch (error) {
        feedback.innerHTML = `<div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-800">
            <strong>Syntax error:</strong><br>
            ${error.message}
        </div>`;
    }
}
</script>
@endpush

@endsection
