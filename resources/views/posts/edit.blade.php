@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Posts', 'url' => route('dashboard.web_curator.posts.index')],
            ['label' => 'Edit Post'],
        ]" />
        <div class="mt-2 flex items-end justify-between gap-4">
            <div>
                <h2 class="page-title">Edit Post</h2>
                <p class="mt-1 text-sm text-gray-600">Update your post content and settings</p>
            </div>
            <a href="{{ route('dashboard.web_curator.posts.preview', $post['id']) }}"
               target="_blank"
               rel="noopener"
               class="btn-base btn-outline bg-[var(--surface)] rounded-lg h-8 shrink-0 gap-2 px-3 text-[13px]">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"><path stroke-linejoin="round" d="M21 3h-6m6 0l-9 9m9-9v6"/><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></g></svg>
                Preview
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('dashboard.web_curator.posts.update', $post['id']) }}" 
          enctype="multipart/form-data"
          data-web-curator-form
          data-enable-quick-save="true"
          class="space-y-6"
          x-data="postForm" x-init="init()" @submit.prevent="syncAndSubmit($event)">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Basic Information</h3>
            </div>
            
            <div class="mb-4">
                <label class="label-base label-required">Post Title</label>
                <input type="text" name="post_title" value="{{ html_entity_decode(old('post_title', $post['post_title'])) }}"
                       x-model="postTitle"
                       placeholder="Enter the post title"
                       class="input-base @error('post_title') border-red-500 @enderror" 
                       required>
                @error('post_title')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="label-base label-required">Category</label>
                    <x-combo-box
                        :options="collect($categories)->map(fn($cat) => ['value' => (string)$cat['id'], 'label' => $cat['name']])->toArray()"
                        :value="old('category_id', $post['category_id'] ?? '')"
                        name="category_id"
                        placeholder="Select a category"
                        required
                        x-on:select-change="selectedCategoryId = $event.detail.value; loadCategorySchema();"
                        class="@error('category_id') border-red-500 @enderror"
                    />
                    @error('category_id')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label-base w-full flex items-center justify-between">
                        <span>Author</span>
                        <span class="help-text font-normal">Optional - defaults to your name</span>
                    </label>
                    <input type="text" name="author" value="{{ html_entity_decode(old('author', $post['author'])) }}"
                           placeholder="Author name"
                           class="input-base">
                    
                </div>
            </div>

            <div 
                x-data="{ isFeatured: {{ old('is_featured', $post['is_featured'] ?? false) ? 'true' : 'false' }} }"
                x-on:change="isFeatured = $event.target.checked"
                class="mb-4 rounded-2xl p-4 border bg-transparent transition-colors duration-200"
                :class="isFeatured ? 'border-emerald-600/50' : 'border-emerald-600/25'">
                <div class="flex items-center justify-between gap-3">
                    <label for="is_featured" class="flex-1 cursor-pointer">
                        <span class="label-base mb-1 inline-flex items-center gap-2" style="color: var(--accent);">
                            <svg class="h-5 w-5" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true"><path d="M238.76 51.73A8 8 0 0 0 232 48H40a8 8 0 0 0-5.66 13.66L76.69 104l-42.35 42.34A8 8 0 0 0 40 160h133.62l-28.84 60.56a8 8 0 1 0 14.44 6.88l80-168a8 8 0 0 0-.46-7.71M181.23 144H59.31l34.35-34.34a8 8 0 0 0 0-11.32L59.31 64h160Z"/></svg>
                            Feature this post
                        </span>
                        <p class="help-text text-emerald-700">Featured posts appear prominently on the homepage</p>
                    </label>
                    <input type="checkbox" name="is_featured" id="is_featured" value="1"
                           {{ old('is_featured', $post['is_featured'] ?? false) ? 'checked' : '' }}
                           class="toggle-switch">
                </div>
            </div>

            <div class="mb-4" x-data="tagInputManager({
                initialTags: @js(collect(explode(',', html_entity_decode(old('tags', $post['tags']))))->map(fn($tag) => trim($tag))->filter()->values()->all()),
                commonTagsByCategory: @js(session('commonTagsByCategory', $commonTagsByCategory ?? []))
            })">
                <label class="label-base">Tags</label>

                <div x-show="activeCommonTags.length > 0" class="mb-3">
                    <p class="text-xs font-medium uppercase tracking-wide mb-2" style="color: var(--text-soft);">Common Tags</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="tag in activeCommonTags" :key="tag">
                            <button type="button"
                                    @click="toggleTag(tag)"
                                    class="text-xs px-2 py-0.5 rounded-lg transition-colors"
                                    :class="isSelected(tag) ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200'">
                                <span x-text="tag"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="rounded-xl border px-3 py-3 transition-shadow"
                     style="border-color: var(--border); background: var(--surface-raised); box-shadow: var(--shadow);">
                    <div class="flex flex-wrap gap-2 mb-2" x-show="selectedTags.length > 0">
                        <template x-for="tag in selectedTags" :key="tag">
                            <span class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 text-slate-600 font-semibold rounded-lg text-sm">
                                <span x-text="tag"></span>
                                <button type="button" @click="removeTag(tag)" class="text-slate-600 hover:text-slate-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        </template>
                    </div>

                    <input type="hidden" name="tags" :value="serializedTags">

                    <input type="text"
                           x-model="inputValue"
                           @keydown.enter.prevent="commitInput()"
                           @keydown.tab.prevent="commitInput()"
                           @keydown="if ($event.key === ',') { $event.preventDefault(); commitInput(); }"
                           @blur="commitInput()"
                           placeholder="Type a tag and press Enter or comma"
                           class="w-full border-0 bg-transparent p-0 text-sm placeholder:text-sm focus:outline-none focus:ring-0">
                </div>

                <!-- <p class="help-text">Type a tag and press Enter, Tab, or comma. Click a suggested tag to add or remove it.</p> -->
            </div>

            <div class="mb-4">
                <label class="label-base">Excerpt</label>
                <textarea name="post_excerpt" rows="3"
                          class="textarea-base"
                          placeholder="Brief summary of the post...">{{ html_entity_decode(old('post_excerpt', $post['post_excerpt'] ?? '')) }}</textarea>
                <!-- <p class="help-text">Short summary shown in post listings</p> -->
            </div>

            <div class="mb-4" x-data="featuredImageField(@js(old('featured_image_uri', $post['featured_image_uri'] ?? '')))">
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

                {{-- Live Preview --}}
                <div x-show="imageUrl && imageUrl.trim() !== ''" class="mt-3">
                    <div class="relative inline-block">
                        <img :src="imageUrl" 
                             alt="Featured image preview" 
                             x-on:load="imageError = false"
                             x-on:error="imageError = true"
                             x-show="!imageError"
                             class="max-w-xs max-h-48 rounded-xl object-cover"
                             style="border: 1px solid var(--border-soft); box-shadow: var(--shadow);">
                        <div x-show="imageError" class="max-w-xs rounded-xl border p-4 text-sm text-red-600"
                             style="border-color: color-mix(in srgb, #dc2626 20%, var(--border-soft)); background: color-mix(in srgb, #dc2626 6%, var(--surface-raised));">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Failed to load image. Check URL or base64 format.
                        </div>
                    </div>
                </div>
                
                <p class="help-text">Supports external image URLs or media picked from your library.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="label-base label-required">Status</label>
                    <x-combo-box
                        :options="[
                            ['value' => 'Draft', 'label' => 'Draft'],
                            ['value' => 'Published', 'label' => 'Published'],
                            ['value' => 'Withdrawn', 'label' => 'Withdrawn']
                        ]"
                        :value="old('post_status', $post['post_status'] ?? 'Draft')"
                        name="post_status"
                        placeholder="Select status"
                        required
                        x-on:select-change="selectedPostStatus = $event.detail.value"
                    />
                </div>

                <div x-show="isPublishedStatus" x-cloak>
                    <label class="label-base">Published Date</label>
                    @php
                        $publishedAt = old('published_at', $post['published_at'] ?? '');
                        // Convert ISO 8601 to datetime-local format if needed
                        if ($publishedAt && !str_contains($publishedAt, 'T')) {
                            // Already in correct format or needs conversion
                            $publishedAt = date('Y-m-d\TH:i', strtotime($publishedAt));
                        } elseif ($publishedAt) {
                            // ISO 8601 format - convert to datetime-local
                            $publishedAt = date('Y-m-d\TH:i', strtotime($publishedAt));
                        }
                    @endphp
                    <input type="datetime-local" name="published_at" 
                           value="{{ $publishedAt }}"
                           :disabled="!isPublishedStatus"
                           class="input-base">
                    <p class="help-text">Leave empty for current time</p>
                </div>
            </div>
        </div>

        {{-- Dynamic Metadata Fields --}}
        <div class="card" x-show="selectedCategoryId && categorySchema" x-cloak>
            <div class="card-header">
                <h3 class="card-title">Category-Specific Information</h3>
                <p class="text-sm text-gray-600 mt-1">Additional fields for <span x-text="categorySchema?.name"></span></p>
            </div>

            {{-- Required Fields --}}
            <template x-if="categorySchema?.meta_schema?.required_fields?.length > 0">
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Required Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="field in categorySchema.meta_schema.required_fields" :key="field.key">
                            <div>
                                <label class="label-base" :class="{'label-required': field.required}" x-text="field.label"></label>
                                
                                {{-- Text Input --}}
                                <template x-if="field.type === 'text' || field.type === 'string'">
                                    <input type="text" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           :placeholder="field.help_text || ''"
                                           class="input-base">
                                </template>

                                {{-- Number Input --}}
                                <template x-if="field.type === 'number' || field.type === 'integer'">
                                    <input type="number" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           :placeholder="field.help_text || ''"
                                           class="input-base">
                                </template>

                                {{-- Date Input with Picker --}}
                                <template x-if="field.type === 'date'">
                                    <input type="date" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           class="input-base cursor-pointer">
                                </template>

                                {{-- DateTime Input --}}
                                <template x-if="field.type === 'datetime'">
                                    <input type="datetime-local" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           class="input-base">
                                </template>

                                {{-- Time Input --}}
                                <template x-if="field.type === 'time'">
                                    <input type="time" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           class="input-base">
                                </template>

                                {{-- Email Input --}}
                                <template x-if="field.type === 'email'">
                                    <input type="email" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           :placeholder="field.help_text || ''"
                                           class="input-base">
                                </template>

                                {{-- URL Input --}}
                                <template x-if="field.type === 'url'">
                                    <input type="url" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :required="field.required"
                                           :placeholder="field.help_text || 'https://'"
                                           class="input-base">
                                </template>

                                {{-- Textarea --}}
                                <template x-if="field.type === 'textarea'">
                                    <textarea :name="'metadata[' + field.key + ']'"
                                              :required="field.required"
                                              :placeholder="field.help_text || ''"
                                              rows="3"
                                              class="textarea-base"
                                              x-text="existingMetadata[field.key] || ''"></textarea>
                                </template>

                                {{-- Select Dropdown --}}
                                <template x-if="field.type === 'select' && field.options">
                                    <div :data-saved-value="existingMetadata[field.key] || ''"
                                         x-data="customSelect({
                                        options: [],
                                        placeholder: 'Select ' + field.label.toLowerCase(),
                                        name: 'metadata[' + field.key + ']',
                                        value: '',
                                        required: field.required || false,
                                        editable: false
                                    })" x-init="
                                        const saved = $el.dataset.savedValue;
                                        updateOptions(field.options.map(opt => ({value: opt, label: opt})));
                                        if (saved) {
                                            $nextTick(() => {
                                                selectedValue = saved;
                                            });
                                        }
                                    ">
                                        <x-custom-select-template />
                                    </div>
                                </template>

                                {{-- Editable Select (Combo Box) --}}
                                <template x-if="field.type === 'select_editable' && field.options">
                                    <div>
                                        <div :data-saved-value="existingMetadata[field.key] || ''"
                                             x-data="customSelect({
                                            options: [],
                                            placeholder: 'Select or enter custom ' + field.label.toLowerCase(),
                                            name: 'metadata[' + field.key + ']',
                                            value: '',
                                            required: field.required || false,
                                            editable: true
                                        })" x-init="
                                            const saved = $el.dataset.savedValue;
                                            updateOptions(field.options.map(opt => ({value: opt, label: opt})));
                                            if (saved) {
                                                $nextTick(() => {
                                                    selectedValue = saved;
                                                });
                                            }
                                        ">
                                            <x-custom-select-template />
                                        </div>
                                        <p class="help-text mt-1">💡 You can select from suggestions or type your own</p>
                                    </div>
                                </template>

                                {{-- Boolean Checkbox --}}
                                <template x-if="field.type === 'boolean'">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               :name="'metadata[' + field.key + ']'"
                                               value="1"
                                               :checked="existingMetadata[field.key] == 1"
                                               class="checkbox-base">
                                        <span class="ml-2 text-sm text-gray-700" x-text="field.help_text || 'Yes'"></span>
                                    </label>
                                </template>

                                <p x-show="field.help_text" x-text="field.help_text" class="help-text"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Extra/Optional Fields --}}
            <template x-if="categorySchema?.meta_schema?.extra_fields?.length > 0">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Additional Information (Optional)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="field in categorySchema.meta_schema.extra_fields" :key="field.key">
                            <div>
                                <label class="label-base" x-text="field.label"></label>
                                
                                {{-- Text Input --}}
                                <template x-if="field.type === 'text' || field.type === 'string'">
                                    <input type="text" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :placeholder="field.help_text || ''"
                                           class="input-base">
                                </template>

                                {{-- Number Input --}}
                                <template x-if="field.type === 'number' || field.type === 'integer'">
                                    <input type="number" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :placeholder="field.help_text || ''"
                                           class="input-base">
                                </template>

                                {{-- Date Input with Picker --}}
                                <template x-if="field.type === 'date'">
                                    <input type="date" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           class="input-base cursor-pointer">
                                </template>

                                {{-- DateTime Input --}}
                                <template x-if="field.type === 'datetime'">
                                    <input type="datetime-local" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           class="input-base">
                                </template>

                                {{-- Time Input --}}
                                <template x-if="field.type === 'time'">
                                    <input type="time" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           class="input-base">
                                </template>

                                {{-- Email Input --}}
                                <template x-if="field.type === 'email'">
                                    <input type="email" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :placeholder="field.help_text || ''"
                                           class="input-base">
                                </template>

                                {{-- URL Input --}}
                                <template x-if="field.type === 'url'">
                                    <input type="url" 
                                           :name="'metadata[' + field.key + ']'"
                                           :value="existingMetadata[field.key] || ''"
                                           :placeholder="field.help_text || 'https://'"
                                           class="input-base">
                                </template>

                                {{-- Textarea --}}
                                <template x-if="field.type === 'textarea'">
                                    <textarea :name="'metadata[' + field.key + ']'"
                                              :placeholder="field.help_text || ''"
                                              rows="3"
                                              class="textarea-base"
                                              x-text="existingMetadata[field.key] || ''"></textarea>
                                </template>

                                {{-- Select Dropdown --}}
                                <template x-if="field.type === 'select' && field.options">
                                    <div :data-saved-value="existingMetadata[field.key] || ''"
                                         x-data="customSelect({
                                        options: [],
                                        placeholder: 'Select ' + field.label.toLowerCase(),
                                        name: 'metadata[' + field.key + ']',
                                        value: '',
                                        required: false,
                                        editable: false
                                    })" x-init="
                                        const saved = $el.dataset.savedValue;
                                        updateOptions(field.options.map(opt => ({value: opt, label: opt})));
                                        if (saved) {
                                            $nextTick(() => {
                                                selectedValue = saved;
                                            });
                                        }
                                    ">
                                        <x-custom-select-template />
                                    </div>
                                </template>

                                {{-- Editable Select (Combo Box) --}}
                                <template x-if="field.type === 'select_editable' && field.options">
                                    <div>
                                        <div :data-saved-value="existingMetadata[field.key] || ''"
                                             x-data="customSelect({
                                            options: [],
                                            placeholder: 'Select or enter custom ' + field.label.toLowerCase(),
                                            name: 'metadata[' + field.key + ']',
                                            value: '',
                                            required: false,
                                            editable: true
                                        })" x-init="
                                            const saved = $el.dataset.savedValue;
                                            updateOptions(field.options.map(opt => ({value: opt, label: opt})));
                                            if (saved) {
                                                $nextTick(() => {
                                                    selectedValue = saved;
                                                });
                                            }
                                        ">
                                            <x-custom-select-template />
                                        </div>
                                        <p class="help-text mt-1">💡 You can select from suggestions or type your own</p>
                                    </div>
                                </template>

                                {{-- Boolean Checkbox --}}
                                <template x-if="field.type === 'boolean'">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               :name="'metadata[' + field.key + ']'"
                                               value="1"
                                               :checked="existingMetadata[field.key] == 1"
                                               class="checkbox-base">
                                        <span class="ml-2 text-sm text-gray-700" x-text="field.help_text || 'Yes'"></span>
                                    </label>
                                </template>

                                <p x-show="field.help_text" x-text="field.help_text" class="help-text"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Attachments Section --}}
        <div class="card" x-data="attachmentManager()" x-show="selectedCategoryId" x-cloak x-init="initExistingAttachments()">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h3 class="card-title">File Attachments</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        <span x-show="categorySchema?.attachment_config?.required" class="text-red-600 font-medium">
                            ⚠️ At least one file is required for this category
                        </span>
                        <span x-show="categorySchema?.attachment_config?.max_files" class="text-gray-600">
                            (Max: <span x-text="categorySchema?.attachment_config?.max_files"></span> files)
                        </span>
                        <span x-show="categorySchema?.attachment_config?.allowed_types?.length > 0" class="text-gray-600">
                            | Allowed: <span x-text="categorySchema?.attachment_config?.allowed_types?.join(', ')"></span>
                        </span>
                    </p>
                </div>
                <button type="button" 
                        @click="$refs.fileInput.click()"
                        :disabled="(files.length + existingAttachments.length) >= (categorySchema?.attachment_config?.max_files || 10)"
                        class="btn-base btn-primary gap-2"
                        :class="(files.length + existingAttachments.length) >= (categorySchema?.attachment_config?.max_files || 10) ? 'opacity-50 cursor-not-allowed' : ''">
                    <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M17.523 5.38c-1.966-1.849-5.065-2.416-7.418-.146l-5.74 5.53a.75.75 0 1 1-1.04-1.08l5.738-5.53c3.076-2.966 7.1-2.112 9.487.133c1.2 1.127 2.047 2.65 2.181 4.309c.137 1.682-.468 3.425-2.03 4.932l-7.112 6.862c-2.203 2.125-5.083 1.5-6.77-.088c-.85-.798-1.459-1.884-1.556-3.08c-.098-1.218.343-2.47 1.451-3.54l7.01-6.763c1.33-1.283 3.065-.886 4.053.043c.5.47.87 1.12.93 1.851c.06.755-.217 1.517-.872 2.148l-.001.002l-5.823 5.579a.75.75 0 0 1-1.038-1.084l5.821-5.577c.355-.342.44-.671.417-.946c-.024-.3-.184-.619-.461-.88c-.567-.533-1.376-.643-1.985-.056l-7.01 6.763c-.808.78-1.056 1.6-.997 2.34c.062.761.46 1.517 1.088 2.108c1.266 1.19 3.22 1.529 4.701.1l7.112-6.862c1.263-1.218 1.675-2.529 1.577-3.731c-.1-1.226-.736-2.418-1.713-3.337"/></svg>
                    Add Files
                </button>
            </div>

            <input type="file" 
                   x-ref="fileInput"
                   @change="addFiles($event)"
                   :accept="acceptAttribute"
                   multiple
                   class="hidden">
            <div id="edit-file-inputs-container" style="display: none;"></div>

            {{-- Existing Attachments --}}
            <div x-show="existingAttachments.length > 0" class="space-y-3 mb-4">
                <h4 class="text-sm font-semibold text-gray-700">Current Attachments</h4>
                <template x-for="(attachment, index) in existingAttachments" :key="attachment.id">
                    <div class="flex items-center gap-3 rounded-xl border p-3"
                         style="border-color: var(--border-soft); background: var(--surface);">
                        {{-- File Icon --}}
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6" viewBox="0 0 24 24"><path fill="currentColor" d="M17.523 5.38c-1.966-1.849-5.065-2.416-7.418-.146l-5.74 5.53a.75.75 0 1 1-1.04-1.08l5.738-5.53c3.076-2.966 7.1-2.112 9.487.133c1.2 1.127 2.047 2.65 2.181 4.309c.137 1.682-.468 3.425-2.03 4.932l-7.112 6.862c-2.203 2.125-5.083 1.5-6.77-.088c-.85-.798-1.459-1.884-1.556-3.08c-.098-1.218.343-2.47 1.451-3.54l7.01-6.763c1.33-1.283 3.065-.886 4.053.043c.5.47.87 1.12.93 1.851c.06.755-.217 1.517-.872 2.148l-.001.002l-5.823 5.579a.75.75 0 0 1-1.038-1.084l5.821-5.577c.355-.342.44-.671.417-.946c-.024-.3-.184-.619-.461-.88c-.567-.533-1.376-.643-1.985-.056l-7.01 6.763c-.808.78-1.056 1.6-.997 2.34c.062.761.46 1.517 1.088 2.108c1.266 1.19 3.22 1.529 4.701.1l7.112-6.862c1.263-1.218 1.675-2.529 1.577-3.731c-.1-1.226-.736-2.418-1.713-3.337"/></svg>
                        </div>

                        {{-- File Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate" style="color: var(--text-strong);" x-text="attachment.file_name"></p>
                            <p class="text-xs" style="color: var(--text-soft);" x-text="attachment.formatted_size || formatFileSize(attachment.file_size)"></p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <a :href="attachment.url" target="_blank" style="color: var(--accent);" title="Download">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"><path fill="currentColor" d="M12.554 16.506a.75.75 0 0 1-1.107 0l-4-4.375a.75.75 0 0 1 1.107-1.012l2.696 2.95V3a.75.75 0 0 1 1.5 0v11.068l2.697-2.95a.75.75 0 1 1 1.107 1.013z"/><path fill="currentColor" d="M3.75 15a.75.75 0 0 0-1.5 0v.055c0 1.367 0 2.47.117 3.337c.12.9.38 1.658.981 2.26c.602.602 1.36.86 2.26.982c.867.116 1.97.116 3.337.116h6.11c1.367 0 2.47 0 3.337-.116c.9-.122 1.658-.38 2.26-.982s.86-1.36.982-2.26c.116-.867.116-1.97.116-3.337V15a.75.75 0 0 0-1.5 0c0 1.435-.002 2.436-.103 3.192c-.099.734-.28 1.122-.556 1.399c-.277.277-.665.457-1.4.556c-.755.101-1.756.103-3.191.103H9c-1.435 0-2.437-.002-3.192-.103c-.734-.099-1.122-.28-1.399-.556c-.277-.277-.457-.665-.556-1.4c-.101-.755-.103-1.756-.103-3.191"/></svg>
                            </a>
                            <button type="button" 
                                    @click="removeExistingAttachment(index, attachment.id)"
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- New Attachments --}}
            <div x-show="files.length > 0" class="space-y-3">
                <h4 class="text-sm font-semibold text-gray-700">New Attachments</h4>
                <template x-for="(file, index) in files" :key="index">
                    <div class="flex items-center gap-3 rounded-xl border p-3"
                         style="border-color: var(--border-soft); background: var(--surface);">
                        {{-- File Icon --}}
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6" viewBox="0 0 24 24"><path fill="currentColor" d="M17.523 5.38c-1.966-1.849-5.065-2.416-7.418-.146l-5.74 5.53a.75.75 0 1 1-1.04-1.08l5.738-5.53c3.076-2.966 7.1-2.112 9.487.133c1.2 1.127 2.047 2.65 2.181 4.309c.137 1.682-.468 3.425-2.03 4.932l-7.112 6.862c-2.203 2.125-5.083 1.5-6.77-.088c-.85-.798-1.459-1.884-1.556-3.08c-.098-1.218.343-2.47 1.451-3.54l7.01-6.763c1.33-1.283 3.065-.886 4.053.043c.5.47.87 1.12.93 1.851c.06.755-.217 1.517-.872 2.148l-.001.002l-5.823 5.579a.75.75 0 0 1-1.038-1.084l5.821-5.577c.355-.342.44-.671.417-.946c-.024-.3-.184-.619-.461-.88c-.567-.533-1.376-.643-1.985-.056l-7.01 6.763c-.808.78-1.056 1.6-.997 2.34c.062.761.46 1.517 1.088 2.108c1.266 1.19 3.22 1.529 4.701.1l7.112-6.862c1.263-1.218 1.675-2.529 1.577-3.731c-.1-1.226-.736-2.418-1.713-3.337"/></svg>
                        </div>

                        {{-- File Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate" style="color: var(--text-strong);" x-text="file.name"></p>
                            <p class="text-xs" style="color: var(--text-soft);" x-text="formatFileSize(file.size)"></p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <span class="rounded-md px-2 py-1 text-xs font-medium"
                                  style="background: color-mix(in srgb, var(--accent) 10%, var(--surface)); color: var(--accent);">New</span>
                            <button type="button" 
                                    @click="removeFile(index)"
                                    class="text-red-600 hover:text-red-800">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="files.length === 0 && existingAttachments.length === 0" class="py-8 text-center" style="color: var(--text-soft);">
                <svg class="mx-auto mb-3 h-12 w-12" style="color: var(--text-soft);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="text-sm">No files attached</p>
                <p class="mt-1 text-xs">Click "Add Files" to attach documents, images, or other files</p>
            </div>
        </div>

        {{-- Content Editor Section --}}
        <div class="card !p-0 overflow-hidden">
            @include('web_curator::partials.editor-shell', [
                'shellId' => 'post-edit-content',
                'fieldName' => 'post_content',
                'initialContent' => old('post_content', $post['post_content']),
                'label' => 'Post Content',
                'showHeader' => true,
                'primaryPlaceholder' => 'Write the post content here...',
                'enableVisual' => true,
                'defaultMode' => 'primary',
                'allowFullscreen' => true,
                'stickyToolbar' => true,
                'mediaUploadContext' => 'post',
            ])
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard.web_curator.posts.index') }}" 
               class="btn-base btn-secondary"
               :class="isSubmitting ? 'pointer-events-none opacity-60' : ''">
                Cancel
            </a>
            <button type="submit" 
                    class="btn-base btn-primary"
                    :disabled="isSubmitting"
                    :class="isSubmitting ? 'cursor-not-allowed opacity-70' : ''">
                <span x-show="!isSubmitting">Update Post</span>
                <span x-show="isSubmitting" x-cloak>Updating...</span>
            </button>
        </div>

    </form>

    @include('web_curator::posts.partials.entity-tagging', [
        'entities' => $entities,
        'initialSelectedEntities' => $taggedEntities ?? [],
        'postId' => $post['id'],
        'saveUrl' => route('dashboard.web_curator.posts.update-tagged-entities', $post['id']),
        'editable' => true,
    ])
</div>

@include('web_curator::partials.media-picker', [
    'mediaType' => 'image',
    'libraryUrl' => route('dashboard.web_curator.media.library-items'),
    'uploadUrl' => route('dashboard.web_curator.media.upload'),
    'uploadContext' => 'post',
])

@push('styles')
{{-- Flatpickr for date picker --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    [x-cloak] { display: none !important; }
    
    /* Flatpickr custom styling */
    .flatpickr-calendar {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
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
                uploadContext: 'post',
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

    Alpine.data('tagInputManager', ({ initialTags = [], commonTagsByCategory = {} } = {}) => ({
        inputValue: '',
        selectedTags: [],
        commonTagsByCategory: commonTagsByCategory || {},
        currentCategoryId: '',

        init() {
            this.selectedTags = [];
            (initialTags || []).forEach(tag => this.addTag(tag));
            this.currentCategoryId = String(window.postFormComponent?.selectedCategoryId || '');
            this._handleCategoryChanged = (event) => {
                this.currentCategoryId = String(event.detail?.categoryId || '');
            };
            window.addEventListener('post-category-changed', this._handleCategoryChanged);
        },

        normalizeTag(tag) {
            return (tag || '').replace(/\s+/g, ' ').trim();
        },

        commitInput() {
            if (!this.inputValue) return;

            this.inputValue
                .split(',')
                .forEach(tag => this.addTag(tag));

            this.inputValue = '';
        },

        addTag(tag) {
            const normalized = this.normalizeTag(tag);
            if (!normalized) return;

            const exists = this.selectedTags.some(existing => existing.toLowerCase() === normalized.toLowerCase());
            if (exists) return;

            this.selectedTags.push(normalized);
        },

        removeTag(tag) {
            this.selectedTags = this.selectedTags.filter(existing => existing !== tag);
        },

        toggleTag(tag) {
            if (this.isSelected(tag)) {
                this.removeTag(tag);
                return;
            }

            this.addTag(tag);
        },

        isSelected(tag) {
            return this.selectedTags.some(existing => existing.toLowerCase() === String(tag).toLowerCase());
        },

        get activeCommonTags() {
            return this.commonTagsByCategory[this.currentCategoryId] || [];
        },

        get serializedTags() {
            return this.selectedTags.join(', ');
        }
    }));

    Alpine.data('postForm', () => ({
        postTitle: decodeHtmlEntities('{{ addslashes($post['post_title']) }}'),
        selectedCategoryId: '{{ old('category_id', $post['category_id'] ?? '') }}',
        selectedPostStatus: '{{ old('post_status', $post['post_status'] ?? 'Draft') }}',
        categorySchema: null,
        existingMetadata: @json($post['organized_metadata'] ?? []),
        isSubmitting: false,

        get isPublishedStatus() {
            return this.selectedPostStatus === 'Published';
        },

        loadCategorySchema() {
            window.dispatchEvent(new CustomEvent('post-category-changed', {
                detail: { categoryId: this.selectedCategoryId || '' }
            }));

            if (!this.selectedCategoryId) {
                this.categorySchema = null;
                return;
            }
            
            // Fetch from API to get full schema
            fetch(`{{ config('web-api.api_base_url') }}/post-categories/${this.selectedCategoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        this.categorySchema = data.data;
                        // Re-initialize date pickers after schema loads and fields render
                        this.$nextTick(() => {
                            setTimeout(() => {
                                if (window.initDatePickers) {
                                    window.initDatePickers();
                                }
                            }, 100);
                        });
                    } else {
                        console.error('Failed to load category schema');
                        this.categorySchema = null;
                    }
                })
                .catch(error => {
                    console.error('Error loading category schema:', error);
                    this.categorySchema = null;
                });
        },

        init() {
            // Expose to global scope for cross-component communication
            window.postFormComponent = this;
            
            // Convert organized metadata to flat structure for form fields (only if not already flat)
            if (this.existingMetadata) {
                // Check if already flat (no 'required' or 'extra' properties)
                if (this.existingMetadata.required || this.existingMetadata.extra) {
                    const flatMetadata = {};
                    if (this.existingMetadata.required) {
                        Object.entries(this.existingMetadata.required).forEach(([key, value]) => {
                            flatMetadata[key] = value;
                        });
                    }
                    if (this.existingMetadata.extra) {
                        Object.entries(this.existingMetadata.extra).forEach(([key, value]) => {
                            flatMetadata[key] = value;
                        });
                    }
                    this.existingMetadata = flatMetadata;
                }
            }
            
            // Load schema if category is already selected
            if (this.selectedCategoryId) {
                this.$nextTick(() => this.loadCategorySchema());
            }
            window.dispatchEvent(new CustomEvent('post-category-changed', {
                detail: { categoryId: this.selectedCategoryId || '' }
            }));
            
        },

        async syncAndSubmit(event) {
            const form = event.currentTarget;

            if (this.isSubmitting) {
                return;
            }

            // Validate required custom selects
            const requiredSelects = form.querySelectorAll('input[type="hidden"][required]');
            let hasErrors = false;
            let errorMessages = [];
            
            requiredSelects.forEach(input => {
                if (!input.value || input.value.trim() === '') {
                    hasErrors = true;
                    const selectContainer = input.closest('[x-data]');
                    if (selectContainer) {
                        const button = selectContainer.querySelector('button[type="button"]');
                        if (button) {
                            button.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                            setTimeout(() => {
                                button.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                            }, 3000);
                        }
                    }
                    
                    // Try to get field label
                    const labelEl = input.closest('.mb-4')?.querySelector('label');
                    const fieldName = labelEl ? labelEl.textContent.replace('*', '').trim() : 'A required field';
                    errorMessages.push(`${fieldName} is required`);
                }
            });
            
            // Validate required metadata fields
            if (this.categorySchema && this.categorySchema.required_fields) {
                this.categorySchema.required_fields.forEach(field => {
                    const value = this.metadataValues[field.key];
                    
                    // Check if field is required and empty
                    if (field.required && (!value || value.toString().trim() === '')) {
                        hasErrors = true;
                        errorMessages.push(`${field.label} is required`);
                        
                        // Highlight the field
                        const fieldInput = form.querySelector(`[name="meta[${field.key}]"]`);
                        if (fieldInput) {
                            fieldInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                            setTimeout(() => {
                                fieldInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                            }, 3000);
                        }
                    }
                });
            }
            
            // Also check extra fields if they have required flag
            if (this.categorySchema && this.categorySchema.extra_fields) {
                this.categorySchema.extra_fields.forEach(field => {
                    const value = this.metadataValues[field.key];
                    
                    if (field.required && (!value || value.toString().trim() === '')) {
                        hasErrors = true;
                        errorMessages.push(`${field.label} is required`);
                        
                        // Highlight the field
                        const fieldInput = form.querySelector(`[name="meta[${field.key}]"]`);
                        if (fieldInput) {
                            fieldInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                            setTimeout(() => {
                                fieldInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                            }, 3000);
                        }
                    }
                });
            }
            
            if (hasErrors) {
                // Show all error messages
                const errorList = errorMessages.length > 1 
                    ? '\n• ' + errorMessages.join('\n• ')
                    : errorMessages[0];
                    
                alert('Please fix the following errors:\n' + errorList);
                
                // Scroll to first error
                const firstErrorField = form.querySelector('.border-red-500');
                if (firstErrorField) {
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            this.isSubmitting = true;

            try {
                await window.WebCuratorEditors.prepareFormSubmission(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: new FormData(form),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationMessage = data?.errors
                        ? Object.values(data.errors).flat()[0]
                        : null;

                    throw new Error(validationMessage || data?.message || 'Failed to save post.');
                }

                const previewUrl = @json(route('dashboard.web_curator.posts.preview', $post['id']));
                const successMessage = `Post updated successfully. <a href="${previewUrl}" target="_blank" rel="noopener" class="underline underline-offset-2 font-medium opacity-90 hover:opacity-100">Preview post</a>`;

                window.showToast?.(successMessage, 'success', {
                    duration: 4200,
                });

                await window.WebCuratorEditors.prepareFormSubmission(form, { quickSave: false });
            } catch (error) {
                window.showToast?.(error?.message || 'Failed to save post.', 'error', {
                    duration: 4200,
                });
            } finally {
                this.isSubmitting = false;
            }
        }
    }));

    Alpine.data('attachmentManager', () => ({
        files: [],
        existingAttachments: @json($post['attachments'] ?? []),
        attachmentsToDelete: [],

        get attachmentConfig() {
            return window.postFormComponent?.categorySchema?.attachment_config || {};
        },

        get acceptAttribute() {
            return this.buildAcceptAttribute(this.attachmentConfig.allowed_types || []);
        },

        initExistingAttachments() {
            // Existing attachments are already loaded from the post data
        },

        addFiles(event) {
            const newFiles = Array.from(event.target.files);
            const attachmentConfig = this.attachmentConfig;
            const maxFiles = attachmentConfig.max_files || 10;
            const allowedTypes = attachmentConfig.allowed_types || [];
            
            let totalFiles = this.files.length + this.existingAttachments.length;
            
            for (const file of newFiles) {
                if (totalFiles >= maxFiles) {
                    alert(`Maximum ${maxFiles} files allowed`);
                    break;
                }

                // Check file type if restrictions exist
                if (allowedTypes.length > 0 && !this.isAllowedFile(file, allowedTypes)) {
                    const fileExt = this.fileExtension(file);
                    alert(`File type ${fileExt ? '.' + fileExt : file.type || 'unknown'} is not allowed. Allowed groups: ${allowedTypes.join(', ')}`);
                    continue;
                }

                this.files.push(file);
                totalFiles++;

                const container = document.getElementById('edit-file-inputs-container');
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = 'attachments[]';
                fileInput.style.display = 'none';

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                container.appendChild(fileInput);
                file._hiddenInputIndex = this.files.length - 1;
                file._hiddenInputRef = fileInput;
            }

            event.target.value = ''; // Reset input
        },

        removeFile(index) {
            const file = this.files[index];
            file?._hiddenInputRef?.remove();
            this.files.splice(index, 1);
        },

        removeExistingAttachment(index, attachmentId) {
            if (confirm('Are you sure you want to delete this attachment?')) {
                this.existingAttachments.splice(index, 1);
                this.attachmentsToDelete.push(attachmentId);
                
                // Add hidden input to mark for deletion
                const form = document.querySelector('form');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_attachments[]';
                input.value = attachmentId;
                form.appendChild(input);
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        fileExtension(file) {
            return String(file?.name || '').split('.').pop()?.toLowerCase() || '';
        },

        isAllowedFile(file, allowedTypes) {
            const extension = this.fileExtension(file);
            const mimeType = String(file?.type || '').toLowerCase();
            const typeSet = new Set((allowedTypes || []).map(type => String(type).toLowerCase()));

            const documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv', 'odt', 'ods', 'odp'];
            const compressedExtensions = ['zip', 'rar', '7z', 'tar', 'gz', 'tgz'];

            if (typeSet.has('image') && mimeType.startsWith('image/')) return true;
            if (typeSet.has('video') && mimeType.startsWith('video/')) return true;
            if (typeSet.has('audio') && mimeType.startsWith('audio/')) return true;
            if (typeSet.has('document') && (documentExtensions.includes(extension) || mimeType === 'application/pdf' || mimeType.startsWith('text/'))) return true;
            if (typeSet.has('compressed') && compressedExtensions.includes(extension)) return true;

            return typeSet.has(extension);
        },

        buildAcceptAttribute(allowedTypes) {
            const acceptMap = {
                image: 'image/*,.jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.tif,.tiff',
                video: 'video/*,.mp4,.mov,.avi,.mkv,.webm,.m4v,.3gp',
                audio: 'audio/*,.mp3,.wav,.ogg,.m4a,.aac',
                document: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.odt,.ods,.odp',
                compressed: '.zip,.rar,.7z,.tar,.gz,.tgz',
            };

            return (allowedTypes || [])
                .flatMap(type => {
                    const key = String(type).toLowerCase();
                    const mapped = acceptMap[key];
                    return mapped ? mapped.split(',') : ['.' + key.replace(/^\./, '')];
                })
                .filter(Boolean)
                .join(',');
        }
    }));
});

// Flatpickr initialization for date inputs
window.initDatePickers = function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input._flatpickr) {
            flatpickr(input, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'F j, Y',
                allowInput: true,
            });
        }
    });
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => initDatePickers(), 500);
});

// Also initialize after Alpine updates the DOM with metadata fields
document.addEventListener('alpine:initialized', () => {
    setTimeout(() => initDatePickers(), 500);
});
</script>

{{-- Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endpush

@endsection
