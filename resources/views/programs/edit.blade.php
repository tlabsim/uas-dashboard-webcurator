@extends('web_curator::layouts.default')

@php
    $programTitle = data_get($program, 'full_program_title', data_get($program, 'program_title', 'Academic program'));
    $heroUrl = data_get($profile, 'hero_media_item.full_url', '');
    $customSections = old('custom_section_titles')
        ? collect(old('custom_section_titles'))->map(fn ($title, $index) => ['title' => $title, 'content' => old('custom_section_contents.' . $index, '')])->values()->all()
        : collect(data_get($profile, 'custom_sections', []))->values()->all();
@endphp

@section('dashboard-content')
<div class="space-y-6" x-data="programProfileForm(@js(['heroId' => old('hero_media_item_id', data_get($profile, 'hero_media_item_id')), 'heroUrl' => $heroUrl, 'sections' => $customSections]))">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Programs', 'url' => route('dashboard.web_curator.programs.index')],
            ['label' => 'Manage'],
        ]" />
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">{{ data_get($program, 'program_code') }}</p>
                <h2 class="page-title mt-1 truncate">{{ $programTitle }}</h2>
            </div>
            @if($previewUrl)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn-base btn-outline h-9 gap-2 px-3 text-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 3h-6m6 0-9 9m9-9v6"/><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></svg>
                    Visit page
                </a>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        Program title, code, level, duration and study mode come from IMS. Fields below affect website presentation only.
    </div>

    <form method="POST" action="{{ route('dashboard.web_curator.programs.update', data_get($program, 'id')) }}" class="space-y-6" data-web-curator-form @submit="saving = true">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header"><h3 class="card-title">Publishing</h3></div>
            <div class="grid gap-5 md:grid-cols-4">
                <div>
                    <label class="label-base">Status</label>
                    <select name="status" class="select-base w-full">
                        @foreach(['Draft', 'Published'] as $status)
                            <option value="{{ $status }}" @selected(old('status', data_get($profile, 'status')) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-base">Display order</label>
                    <input type="number" min="0" max="10000" name="sort_order" value="{{ old('sort_order', data_get($profile, 'sort_order', 0)) }}" class="input-base w-full">
                </div>
                <label class="flex items-center gap-3 pt-7">
                    <input type="hidden" name="is_visible" value="0">
                    <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', data_get($profile, 'is_visible', true))) class="h-4 w-4 rounded border-gray-300 text-emerald-600">
                    <span class="text-sm font-medium text-gray-700">Show on website</span>
                </label>
                <label class="flex items-center gap-3 pt-7">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', data_get($profile, 'is_featured', false))) class="h-4 w-4 rounded border-gray-300 text-emerald-600">
                    <span class="text-sm font-medium text-gray-700">Feature program</span>
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Presentation</h3></div>
            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="label-base">Display title</label>
                    <input type="text" name="display_title" value="{{ old('display_title', data_get($profile, 'display_title')) }}" placeholder="{{ $programTitle }}" class="input-base w-full">
                    <p class="mt-1 text-xs text-gray-500">Leave empty to use the IMS title.</p>
                </div>
                <div>
                    <label class="label-base">URL slug</label>
                    <input type="text" name="slug" value="{{ old('slug', data_get($profile, 'slug')) }}" class="input-base w-full font-mono text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="label-base">Subtitle</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle', data_get($profile, 'subtitle')) }}" class="input-base w-full" maxlength="255">
                </div>
                <div class="lg:col-span-2">
                    <label class="label-base">Summary</label>
                    <textarea name="summary" rows="4" maxlength="2000" class="input-base w-full">{{ old('summary', data_get($profile, 'summary')) }}</textarea>
                </div>
                <div class="lg:col-span-2">
                    <div class="mb-2 flex items-center justify-between"><label class="label-base mb-0">Hero image</label><button type="button" x-show="heroUrl" @click="clearHero()" class="text-xs font-semibold text-red-600">Clear</button></div>
                    <input type="hidden" name="hero_media_item_id" :value="heroId">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex h-28 w-full items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50 sm:w-52">
                            <img x-show="heroUrl" :src="heroUrl" alt="" class="h-full w-full object-cover">
                            <span x-show="!heroUrl" class="text-xs text-gray-500">No image selected</span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn-base btn-primary" @click="chooseHero(false)">Choose image</button>
                            <button type="button" class="btn-base btn-secondary" @click="chooseHero(true)">Upload</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card !p-0 overflow-hidden">
            @include('web_curator::partials.editor-shell', [
                'shellId' => 'program-overview',
                'fieldName' => 'overview',
                'initialContent' => old('overview', data_get($profile, 'overview')),
                'label' => 'Program Overview',
                'showHeader' => true,
                'primaryPlaceholder' => 'Introduce the program, its purpose and distinctive strengths...',
                'primaryHeight' => 360,
                'allowFullscreen' => true,
                'mediaUploadContext' => 'program',
            ])
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Program Information</h3></div>
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach([
                    'learning_outcomes' => ['Learning outcomes', 'What graduates should know or be able to do.'],
                    'admission_requirements' => ['Admission requirements', 'Eligibility, prerequisites and selection notes.'],
                    'curriculum' => ['Curriculum', 'Curriculum structure, concentrations or links to detailed syllabi.'],
                    'career_opportunities' => ['Career opportunities', 'Typical careers, sectors and further-study pathways.'],
                    'fees_and_funding' => ['Fees and funding', 'Tuition guidance, scholarships and funding notes.'],
                ] as $field => [$label, $hint])
                    <div @class(['lg:col-span-2' => $field === 'curriculum'])>
                        <label class="label-base">{{ $label }}</label>
                        <textarea name="{{ $field }}" rows="6" class="input-base w-full" placeholder="{{ $hint }}">{{ old($field, data_get($profile, $field)) }}</textarea>
                    </div>
                @endforeach
                <div class="lg:col-span-2">
                    <label class="label-base">Accreditation</label>
                    <input type="text" name="accreditation" value="{{ old('accreditation', data_get($profile, 'accreditation')) }}" class="input-base w-full" maxlength="500">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between gap-4">
                <h3 class="card-title">Additional Sections</h3>
                <button type="button" class="btn-base btn-outline h-8 px-3 text-xs" @click="addSection()">Add section</button>
            </div>
            <div class="space-y-4">
                <template x-for="(section, index) in sections" :key="section.key">
                    <div class="rounded-lg border border-[var(--border-soft)] bg-gray-50 p-4">
                        <div class="flex gap-3">
                            <input type="text" name="custom_section_titles[]" x-model="section.title" placeholder="Section title" class="input-base flex-1 font-semibold">
                            <button type="button" class="btn-base btn-outline h-10 px-3 text-xs text-red-600" @click="removeSection(index)">Remove</button>
                        </div>
                        <textarea name="custom_section_contents[]" x-model="section.content" rows="5" placeholder="Section content" class="input-base mt-3 w-full"></textarea>
                    </div>
                </template>
                <p x-show="sections.length === 0" class="py-4 text-center text-sm text-gray-500">No additional sections.</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Actions and Contact</h3></div>
                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label-base">Application button label</label><input type="text" name="application_label" value="{{ old('application_label', data_get($profile, 'application_label')) }}" class="input-base w-full"></div>
                        <div><label class="label-base">Application URL</label><input type="url" name="application_url" value="{{ old('application_url', data_get($profile, 'application_url')) }}" class="input-base w-full"></div>
                    </div>
                    <div><label class="label-base">Brochure URL</label><input type="url" name="brochure_url" value="{{ old('brochure_url', data_get($profile, 'brochure_url')) }}" class="input-base w-full"></div>
                    <div><label class="label-base">Contact name or office</label><input type="text" name="contact_name" value="{{ old('contact_name', data_get($profile, 'contact_name')) }}" class="input-base w-full"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label-base">Contact email</label><input type="email" name="contact_email" value="{{ old('contact_email', data_get($profile, 'contact_email')) }}" class="input-base w-full"></div>
                        <div><label class="label-base">Contact phone</label><input type="text" name="contact_phone" value="{{ old('contact_phone', data_get($profile, 'contact_phone')) }}" class="input-base w-full"></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Search Appearance</h3></div>
                <div class="space-y-4">
                    <div><label class="label-base">SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', data_get($profile, 'seo_title')) }}" maxlength="255" class="input-base w-full"></div>
                    <div><label class="label-base">SEO description</label><textarea name="seo_description" rows="5" maxlength="500" class="input-base w-full">{{ old('seo_description', data_get($profile, 'seo_description')) }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            @if(data_get($profile, 'id'))
                <button type="submit" form="reset-program-profile" class="text-sm font-semibold text-red-600 hover:text-red-700">Reset website customization</button>
            @else
                <span></span>
            @endif
            <div class="flex gap-3">
                <a href="{{ route('dashboard.web_curator.programs.index') }}" class="btn-base btn-secondary">Cancel</a>
                <button type="submit" class="btn-base btn-primary min-w-32" :disabled="saving"><span x-text="saving ? 'Saving...' : 'Save program'"></span></button>
            </div>
        </div>
    </form>

    @if(data_get($profile, 'id'))
        <form id="reset-program-profile" method="POST" action="{{ route('dashboard.web_curator.programs.destroy', data_get($program, 'id')) }}" onsubmit="return confirm('Reset all website customization for this program?')">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>

@include('web_curator::partials.media-picker', [
    'mediaType' => 'image',
    'libraryUrl' => route('dashboard.web_curator.media.library-items'),
    'uploadUrl' => route('dashboard.web_curator.media.upload'),
    'uploadContext' => 'program',
])

@push('scripts')
<script>
    function programProfileForm(config) {
        return {
            saving: false,
            heroId: config.heroId || '',
            heroUrl: config.heroUrl || '',
            sections: (config.sections || []).map((section, index) => ({ key: `${Date.now()}-${index}`, title: section.title || '', content: section.content || '' })),
            addSection() { this.sections.push({ key: `${Date.now()}-${Math.random()}`, title: '', content: '' }); },
            removeSection(index) { this.sections.splice(index, 1); },
            clearHero() { this.heroId = ''; this.heroUrl = ''; },
            async chooseHero(preferUpload) {
                const item = await window.WebCuratorMediaPicker?.open({ title: 'Choose program hero image', mediaType: 'image', uploadContext: 'program', preferUpload });
                if (item) { this.heroId = item.id; this.heroUrl = item.full_url || item.public_url || ''; }
            },
        };
    }
</script>
@endpush
@endsection
