@extends('web_curator::layouts.default')

@section('title', 'Website Appearance')

@php
    $entityWebBaseUrl = rtrim((string) config('web_curator.entity_web_base_url', ''), '/');
    if ($entityWebBaseUrl !== '' && !preg_match('#^https?://#i', $entityWebBaseUrl)) {
        $entityWebBaseUrl = '//' . ltrim($entityWebBaseUrl, '/');
    }
    $entitySlug = data_get($entity, 'entity_slug', data_get($entity, 'slug'));
    $entityWebsiteUrl = $entitySlug
        ? ($entityWebBaseUrl !== '' ? $entityWebBaseUrl . '/' . ltrim($entitySlug, '/') : '/' . ltrim($entitySlug, '/'))
        : null;
    $previewFontFamilies = collect($fontOptions)
        ->flatten(1)
        ->pluck('bunny_family')
        ->filter()
        ->unique()
        ->implode('|');
@endphp

@push('styles')
    @if ($previewFontFamilies !== '')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family={{ urlencode($previewFontFamilies) }}" rel="stylesheet">
    @endif
@endpush

@section('dashboard-content')
<div
    class="space-y-6"
    x-data="websiteAppearanceForm({
        initial: @js($appearanceValues),
        entity: @js($entity),
        imsLogo: @js(data_get($entity, 'cachedData.logo_url', data_get($entity, 'cached_data.logo_url'))),
        templates: @js($templates),
        fontOptions: @js($fontOptions),
        galleries: @js($galleries),
        sectionOptions: @js($sectionOptions),
        sectionOrder: @js($sectionOrder),
        disabledSections: @js($disabledSections),
    })"
>
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Website Appearance'],
        ]" />
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="page-title">Website Appearance</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Template, hero, theme, and homepage sections.
                </p>
            </div>
            @if ($entityWebsiteUrl)
                <a
                    href="{{ $entityWebsiteUrl }}"
                    target="_blank"
                    rel="noreferrer"
                    class="btn-base btn-outline inline-flex items-center gap-2 self-start md:self-auto"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 3h-6m6 0l-9 9m9-9v6" />
                        <path stroke-linecap="round" d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6" />
                    </svg>
                    Visit Site
                </a>
            @endif
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <!-- @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif -->

    <form method="POST" action="{{ route('dashboard.web_curator.website_appearance.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Template</h3>
            </div>

            <div class="space-y-4">
                <div class="space-y-4">
                    <div>
                        <label for="template_key" class="label-base">Website Template</label>
                        <select name="template_key" id="template_key" x-model="form.template_key" class="select-base w-full">
                            <option value="">Inherit entity-type default</option>
                            <template x-for="template in templates" :key="template.key">
                                <option :value="template.key" x-text="template.label"></option>
                            </template>
                        </select>
                    </div>

                    <div class="rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)] p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex shrink-0 items-center justify-center text-[var(--accent)]">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M20.399 2.551a.75.75 0 0 1 1.202.898l-7.232 9.69a1.537 1.537 0 0 1-2.364.11l-3.561-3.94a.75.75 0 1 1 1.112-1.006l3.562 3.94l.007.004q.006.003.02.003l.017-.004l.004-.004zM9.367 2.25H9.4a.75.75 0 1 1 0 1.5c-1.132 0-1.937 0-2.566.052c-.62.05-1.005.147-1.31.302a3.25 3.25 0 0 0-1.42 1.42c-.155.305-.251.69-.302 1.31c-.051.63-.052 1.434-.052 2.566v5.2c0 1.133 0 1.937.052 2.566c.05.62.147 1.005.302 1.31a3.25 3.25 0 0 0 1.42 1.42c.305.155.69.251 1.31.302c.63.052 1.434.052 2.566.052h5.2c1.133 0 1.937 0 2.566-.052c.62-.05 1.005-.147 1.31-.302a3.25 3.25 0 0 0 1.42-1.42c.155-.305.251-.69.302-1.31c.051-.63.052-1.434.052-2.566v-1.1a.75.75 0 0 1 1.5 0v1.133c0 1.092 0 1.958-.057 2.655c-.058.714-.18 1.317-.46 1.869a4.75 4.75 0 0 1-2.076 2.075c-.552.281-1.155.403-1.869.461c-.697.057-1.563.057-2.655.057H9.367c-1.092 0-1.958 0-2.655-.057c-.714-.058-1.317-.18-1.868-.46a4.75 4.75 0 0 1-2.076-2.076c-.281-.552-.403-1.155-.461-1.869c-.057-.697-.057-1.563-.057-2.655V9.367c0-1.092 0-1.958.057-2.655c.058-.714.18-1.317.46-1.868a4.75 4.75 0 0 1 2.077-2.076c.55-.281 1.154-.403 1.868-.461c.697-.057 1.563-.057 2.655-.057"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Selected Template</p>
                                <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]" x-text="selectedTemplateLabel"></p>
                                <p class="mt-1 text-sm text-[var(--text-soft)]" x-text="selectedTemplateDescription"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Entity Logos</h3>
            </div>

            <input type="hidden" name="website_logo_on_light" x-model="form.website_logo_on_light">
            <input type="hidden" name="website_logo_on_dark" x-model="form.website_logo_on_dark">

            <div class="grid gap-5 lg:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border border-[var(--border-soft)]">
                    <div class="flex min-h-40 items-center justify-center bg-slate-50 p-6">
                        <template x-if="logoPreview('website_logo_on_light')">
                            <img :src="logoPreview('website_logo_on_light')" alt="Logo preview for light backgrounds" class="max-h-24 max-w-full object-contain">
                        </template>
                        <template x-if="!logoPreview('website_logo_on_light')">
                            <p class="text-sm text-slate-500">No logo available</p>
                        </template>
                    </div>
                    <div class="border-t border-[var(--border-soft)] bg-[var(--surface)] p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h4 class="font-semibold text-[var(--text-strong)]">For light backgrounds</h4>
                                <p class="mt-1 text-xs text-[var(--text-soft)]" x-text="logoSourceLabel('website_logo_on_light')"></p>
                            </div>
                            <button type="button" class="text-sm font-medium text-[var(--accent)] hover:underline" x-show="hasLogoOverride('website_logo_on_light')" @click="clearLogo('website_logo_on_light')">Clear</button>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" class="btn-base btn-primary" @click="openLogoMediaPicker('website_logo_on_light', false)">Choose Image</button>
                            <button type="button" class="btn-base btn-secondary" @click="openLogoMediaPicker('website_logo_on_light', true)">Upload Image</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[var(--border-soft)]">
                    <div class="flex min-h-40 items-center justify-center bg-slate-900 p-6">
                        <template x-if="logoPreview('website_logo_on_dark')">
                            <img :src="logoPreview('website_logo_on_dark')" alt="Logo preview for dark backgrounds" class="max-h-24 max-w-full object-contain">
                        </template>
                        <template x-if="!logoPreview('website_logo_on_dark')">
                            <p class="text-sm text-slate-400">No logo available</p>
                        </template>
                    </div>
                    <div class="border-t border-[var(--border-soft)] bg-[var(--surface)] p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h4 class="font-semibold text-[var(--text-strong)]">For dark backgrounds</h4>
                                <p class="mt-1 text-xs text-[var(--text-soft)]" x-text="logoSourceLabel('website_logo_on_dark')"></p>
                            </div>
                            <button type="button" class="text-sm font-medium text-[var(--accent)] hover:underline" x-show="hasLogoOverride('website_logo_on_dark')" @click="clearLogo('website_logo_on_dark')">Clear</button>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" class="btn-base btn-primary" @click="openLogoMediaPicker('website_logo_on_dark', false)">Choose Image</button>
                            <button type="button" class="btn-base btn-secondary" @click="openLogoMediaPicker('website_logo_on_dark', true)">Upload Image</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hero</h3>
            </div>

            <input type="hidden" name="hero_overlay_color" :value="heroOverlayRgba">
            <input type="hidden" name="website_hero_image" x-model="form.website_hero_image">

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="space-y-5">
                    <div class="grid gap-4 grid-cols-1">
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="hero_title" class="label-base mb-0">Title</label>
                                <button type="button" class="text-sm font-medium text-[var(--accent)] hover:underline" @click="form.hero_title = defaults.hero_title">Reset</button>
                            </div>
                            <input type="text" name="hero_title" id="hero_title" x-model="form.hero_title" class="input-base w-full" maxlength="255">
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="hero_subheading" class="label-base mb-0">Subtitle</label>
                                <button type="button" class="text-sm font-medium text-[var(--accent)] hover:underline" @click="form.hero_subheading = defaults.hero_subheading">Reset</button>
                            </div>
                            <input type="text" name="hero_subheading" id="hero_subheading" x-model="form.hero_subheading" class="input-base w-full" maxlength="255">
                        </div>
                        <div>
                            <label for="hero_subheading_position" class="label-base">Subtitle Position</label>
                            <select name="hero_subheading_position" id="hero_subheading_position" x-model="form.hero_subheading_position" class="select-base w-full">
                                <option value="auto">Automatic</option>
                                <option value="above">Above title</option>
                                <option value="below">Below title</option>
                            </select>
                            <p class="mt-1.5 text-xs text-[var(--text-soft)]">Automatic places it below when a logo is available.</p>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label for="hero_summary" class="label-base mb-0">Summary</label>
                            <button type="button" class="text-sm font-medium text-[var(--accent)] hover:underline" @click="form.hero_summary = defaults.hero_summary">Reset</button>
                        </div>
                        <textarea name="hero_summary" id="hero_summary" x-model="form.hero_summary" rows="4" class="textarea-base w-full" maxlength="1200"></textarea>
                    </div>

                </div>

                <div class="space-y-5 self-start">
                    <div class="grid gap-4 grid-cols-1">
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="website_hero_bg_type" class="label-base mb-0">Background</label>
                                <span class="text-sm font-medium opacity-0 select-none">Reset</span>
                            </div>
                            <select name="website_hero_bg_type" id="website_hero_bg_type" x-model="form.website_hero_bg_type" class="select-base w-full">
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                            </select>
                        </div>

                        <div x-show="form.website_hero_bg_type === 'video'" x-cloak>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="website_hero_video" class="label-base mb-0">Video URL</label>
                                <span class="text-sm font-medium opacity-0 select-none">Reset</span>
                            </div>
                            <input
                                type="text"
                                name="website_hero_video"
                                id="website_hero_video"
                                x-model="form.website_hero_video"
                                class="input-base w-full"
                                placeholder="https://example.com/hero-video.mp4"
                            >
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_7rem]">
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="hero_overlay_hex" class="label-base mb-0">Overlay</label>
                                <span class="text-sm font-medium opacity-0 select-none">Reset</span>
                            </div>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="h-11 w-11 overflow-hidden rounded-lg border border-[var(--border-soft)] bg-[var(--surface)] shadow-sm" style="padding: 2px;">
                                    <input type="color" id="hero_overlay_hex" x-model="form.hero_overlay_hex" class="h-full w-full cursor-pointer border-0 bg-transparent p-0" style="appearance: none; -webkit-appearance: none; border-radius: 8px;">
                                </div>
                                <input type="text" x-model="form.hero_overlay_hex" class="input-base flex-1 font-mono text-sm" maxlength="7">
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="hero_overlay_alpha" class="label-base mb-0">Opacity</label>
                                <span class="text-sm font-medium opacity-0 select-none">Reset</span>
                            </div>
                            <input id="hero_overlay_alpha" type="number" min="0" max="100" step="1" x-model="form.hero_overlay_alpha" class="input-base w-full">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="hero_title_color" class="label-base mb-0">Title Color</label>
                                <span class="text-sm font-medium opacity-0 select-none">Reset</span>
                            </div>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="h-11 w-11 overflow-hidden rounded-lg border border-[var(--border-soft)] bg-[var(--surface)] shadow-sm" style="padding: 2px;">
                                    <input type="color" name="hero_title_color" id="hero_title_color" x-model="form.hero_title_color" class="h-full w-full cursor-pointer border-0 bg-transparent p-0" style="appearance: none; -webkit-appearance: none; border-radius: 8px;">
                                </div>
                                <input type="text" x-model="form.hero_title_color" class="input-base flex-1 font-mono text-sm" maxlength="7">
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="hero_subtitle_color" class="label-base mb-0">Subtitle Color</label>
                                <span class="text-sm font-medium opacity-0 select-none">Reset</span>
                            </div>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="h-11 w-11 overflow-hidden rounded-lg border border-[var(--border-soft)] bg-[var(--surface)] shadow-sm" style="padding: 2px;">
                                    <input type="color" name="hero_subtitle_color" id="hero_subtitle_color" x-model="form.hero_subtitle_color" class="h-full w-full cursor-pointer border-0 bg-transparent p-0" style="appearance: none; -webkit-appearance: none; border-radius: 8px;">
                                </div>
                                <input type="text" x-model="form.hero_subtitle_color" class="input-base flex-1 font-mono text-sm" maxlength="7">
                            </div>
                        </div>
                    </div>

                    <div x-show="form.website_hero_bg_type === 'image'" x-cloak class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn-base btn-primary" @click="openHeroMediaPicker(false)">Choose Image</button>
                            <button type="button" class="btn-base btn-secondary" @click="openHeroMediaPicker(true)">Upload Image</button>
                            <button type="button" class="btn-base btn-secondary" @click="clearHeroImage()" x-show="hasHeroImage">Clear</button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)]">
                <div class="aspect-[16/6] bg-[var(--surface-muted)]">
                    <div class="relative h-full w-full">
                        <template x-if="form.website_hero_bg_type === 'image' && hasHeroImage">
                            <img :src="form.website_hero_image" alt="Hero preview" class="h-full w-full object-cover">
                        </template>
                        <template x-if="form.website_hero_bg_type === 'image' && !hasHeroImage">
                            <div class="flex h-full min-h-[100px] items-center justify-center px-6 text-center text-sm text-[var(--text-soft)]">
                                No hero image selected
                            </div>
                        </template>
                        <template x-if="form.website_hero_bg_type === 'video'">
                            <div class="flex h-full flex-col items-center justify-center gap-2 px-6 text-center text-sm text-white">
                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m10 8 6 4-6 4V8Z" />
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                </svg>
                                <span x-text="form.website_hero_video || 'No video URL provided'"></span>
                            </div>
                        </template>

                        <div class="absolute inset-0" :style="{ backgroundColor: heroOverlayRgba }"></div>

                        <div class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
                            <template x-if="logoPreview('website_logo_on_dark')">
                                <img :src="logoPreview('website_logo_on_dark')" alt="" class="mb-4 max-h-14 max-w-24 object-contain">
                            </template>
                            <template x-if="hasSubtitle && effectiveSubtitlePosition === 'above'">
                                <p
                                    class="text-sm font-semibold uppercase tracking-[0.22em]"
                                    :style="{ color: form.hero_subtitle_color, fontFamily: selectedSansFamily }"
                                    x-text="form.hero_subheading"
                                ></p>
                            </template>
                            <h4
                                class="text-3xl font-semibold md:text-4xl"
                                :class="hasSubtitle && effectiveSubtitlePosition === 'above' ? 'mt-3' : ''"
                                :style="{ color: form.hero_title_color, fontFamily: selectedSerifFamily }"
                                x-text="form.hero_title || defaults.hero_title"
                            ></h4>
                            <template x-if="hasSubtitle && effectiveSubtitlePosition === 'below'">
                                <p
                                    class="mt-3 text-base italic"
                                    :style="{ color: form.hero_subtitle_color, fontFamily: selectedSerifFamily }"
                                    x-text="form.hero_subheading"
                                ></p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Theme</h3>
            </div>

            <div class="space-y-6">
                <div class="grid gap-5 md:grid-cols-3">
                    <div>
                        <label for="accent" class="label-base">Accent</label>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-11 w-11 overflow-hidden rounded-lg border border-[var(--border-soft)] bg-[var(--surface)] shadow-sm" style="padding: 2px;">
                                <input type="color" name="accent" id="accent" x-model="form.accent" class="h-full w-full cursor-pointer border-0 bg-transparent p-0" style="appearance: none; -webkit-appearance: none; border-radius: 8px;">
                            </div>
                            <input type="text" x-model="form.accent" class="input-base flex-1 font-mono text-sm" maxlength="7">
                        </div>
                    </div>

                    <div>
                        <label for="accent_soft" class="label-base">Accent Soft</label>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-11 w-11 overflow-hidden rounded-lg border border-[var(--border-soft)] bg-[var(--surface)] shadow-sm" style="padding: 2px;">
                                <input type="color" name="accent_soft" id="accent_soft" x-model="form.accent_soft" class="h-full w-full cursor-pointer border-0 bg-transparent p-0" style="appearance: none; -webkit-appearance: none; border-radius: 8px;">
                            </div>
                            <input type="text" x-model="form.accent_soft" class="input-base flex-1 font-mono text-sm" maxlength="7">
                        </div>
                    </div>

                    <div>
                        <label for="surface_tint" class="label-base">Surface Tint</label>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-11 w-11 overflow-hidden rounded-lg border border-[var(--border-soft)] bg-[var(--surface)] shadow-sm" style="padding: 2px;">
                                <input type="color" name="surface_tint" id="surface_tint" x-model="form.surface_tint" class="h-full w-full cursor-pointer border-0 bg-transparent p-0" style="appearance: none; -webkit-appearance: none; border-radius: 8px;">
                            </div>
                            <input type="text" x-model="form.surface_tint" class="input-base flex-1 font-mono text-sm" maxlength="7">
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="default_serif_font" class="label-base">Default Serif Font</label>
                        <select name="default_serif_font" id="default_serif_font" x-model="form.default_serif_font" class="select-base w-full">
                            <template x-for="font in fontOptions.serif || []" :key="font.key">
                                <option :value="font.key" x-text="font.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="default_sans_font" class="label-base">Default Sans-Serif Font</label>
                        <select name="default_sans_font" id="default_sans_font" x-model="form.default_sans_font" class="select-base w-full">
                            <template x-for="font in fontOptions.sans || []" :key="font.key">
                                <option :value="font.key" x-text="font.label"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)]">
                    <div class="px-5 py-6" :style="themePreviewStyle">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em]" style="color: rgba(15, 23, 42, 0.72);">Preview</p>
                        <h4 class="mt-2 text-xl font-semibold text-slate-900" :style="{ fontFamily: selectedSerifFamily }" x-text="form.hero_title || defaults.hero_title"></h4>
                        <p class="mt-1 text-sm text-slate-700" :style="{ fontFamily: selectedSansFamily }" x-text="form.hero_subheading || defaults.hero_subheading"></p>
                        <div class="mt-4 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" :style="{ backgroundColor: form.accent_soft, color: form.accent }">
                            Accent
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" x-show="!isSectionDisabled('featured_gallery')" x-cloak>
            <div class="card-header">
                <h3 class="card-title">Featured Gallery</h3>
            </div>

            <div class="space-y-3">
                <div>
                    <label for="featured_gallery_id" class="label-base">Gallery</label>
                    <select name="featured_gallery_id" id="featured_gallery_id" x-model="form.featured_gallery_id" class="select-base w-full">
                        <option value="">Use auto-selected featured gallery</option>
                        <template x-for="gallery in galleries" :key="gallery.id">
                            <option :value="String(gallery.id)" x-text="galleryOptionLabel(gallery)"></option>
                        </template>
                    </select>
                </div>
                <p class="text-sm text-[var(--text-soft)]">
                    Choose a specific gallery for the homepage. Leave empty to use the currently featured public gallery.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between gap-3">
                <h3 class="card-title">Homepage Sections</h3>
                <button type="button" class="btn-base btn-secondary btn-sm" @click="resetSections()">Reset order</button>
            </div>

            <div class="space-y-2">
                <template x-for="(sectionKey, index) in sectionOrder" :key="sectionKey">
                    <div class="flex min-h-[3.5rem] items-center gap-3 rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)] px-4 py-2">
                        <input type="hidden" name="section_order[]" :value="sectionKey">
                        <template x-if="isSectionDisabled(sectionKey)">
                            <input type="hidden" name="disabled_sections[]" :value="sectionKey">
                        </template>

                        <div class="flex w-10 shrink-0 flex-col items-center gap-1">
                            <button type="button" class="btn-icon h-6 w-6 disabled:cursor-not-allowed disabled:opacity-100" :class="index === 0 ? 'text-gray-300 border-gray-200 bg-gray-50' : ''" @click="moveSection(index, -1)" :disabled="index === 0" aria-label="Move up">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 14 6-6 6 6" />
                                </svg>
                            </button>
                            <button type="button" class="btn-icon h-6 w-6 disabled:cursor-not-allowed disabled:opacity-100" :class="index === sectionOrder.length - 1 ? 'text-gray-300 border-gray-200 bg-gray-50' : ''" @click="moveSection(index, 1)" :disabled="index === sectionOrder.length - 1" aria-label="Move down">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 10 6 6 6-6" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[var(--surface-muted)] text-sm font-semibold text-[var(--text-soft)]" x-text="index + 1"></div>

                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-[var(--text-strong)]" x-text="sectionOptions[sectionKey]"></p>
                            <p class="text-xs font-mono text-[var(--text-soft)]" x-text="sectionKey"></p>
                        </div>

                        <label class="shrink-0">
                            <input
                                type="checkbox"
                                class="toggle-switch"
                                :checked="!isSectionDisabled(sectionKey)"
                                @change="toggleSection(sectionKey)"
                            >
                        </label>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard.web_curator.index') }}" class="btn-base btn-secondary">Cancel</a>
            <button type="submit" class="btn-base btn-primary">Save Website Appearance</button>
        </div>
    </form>
</div>

@include('web_curator::partials.media-picker', [
    'mediaType' => 'image',
    'libraryUrl' => route('dashboard.web_curator.media.library-items'),
    'uploadUrl' => route('dashboard.web_curator.media.upload'),
    'uploadContext' => 'website-appearance',
])

@push('scripts')
<script>
    function websiteAppearanceForm(config) {
        const defaults = {
            hero_title: config.initial.hero_title || '',
            hero_subheading: config.initial.hero_subheading || '',
            hero_summary: config.initial.hero_summary || '',
        };

        return {
            entity: config.entity || {},
            imsLogo: String(config.imsLogo || '').trim(),
            templates: Array.isArray(config.templates) ? config.templates : [],
            fontOptions: config.fontOptions || {},
            galleries: Array.isArray(config.galleries) ? config.galleries : [],
            sectionOptions: config.sectionOptions || {},
            defaults,
            form: {
                template_key: config.initial.template_key || '',
                hero_title: config.initial.hero_title || '',
                hero_subheading: config.initial.hero_subheading || '',
                hero_subheading_position: config.initial.hero_subheading_position || 'auto',
                hero_summary: config.initial.hero_summary || '',
                hero_overlay_hex: '#0f172a',
                hero_overlay_alpha: 28,
                hero_title_color: config.initial.hero_title_color || '#ffffff',
                hero_subtitle_color: config.initial.hero_subtitle_color || '#e2e8f0',
                default_serif_font: config.initial.default_serif_font || 'source-serif-4',
                default_sans_font: config.initial.default_sans_font || 'source-sans-3',
                featured_gallery_id: config.initial.featured_gallery_id ? String(config.initial.featured_gallery_id) : '',
                website_logo_on_light: config.initial['website-logo-on-light'] || '',
                website_logo_on_dark: config.initial['website-logo-on-dark'] || '',
                website_hero_bg_type: config.initial['website-hero-bg-type'] || 'image',
                website_hero_image: config.initial['website-hero-image'] || '',
                website_hero_video: config.initial['website-hero-video'] || '',
                accent: config.initial.accent || '#0e7490',
                accent_soft: config.initial.accent_soft || '#dbeafe',
                surface_tint: config.initial.surface_tint || '#f8fafc',
            },
            sectionOrder: Array.isArray(config.sectionOrder) ? [...config.sectionOrder] : Object.keys(config.sectionOptions || {}),
            disabledSections: Array.isArray(config.disabledSections) ? [...config.disabledSections] : [],

            get hasHeroImage() {
                return String(this.form.website_hero_image || '').trim() !== '';
            },

            get hasSubtitle() {
                return String(this.form.hero_subheading || '').trim() !== '';
            },

            get effectiveSubtitlePosition() {
                if (this.form.hero_subheading_position === 'above' || this.form.hero_subheading_position === 'below') {
                    return this.form.hero_subheading_position;
                }

                return this.logoPreview('website_logo_on_dark') ? 'below' : 'above';
            },

            hasLogoOverride(field) {
                return String(this.form[field] || '').trim() !== '';
            },

            logoPreview(field) {
                return String(this.form[field] || '').trim() || this.imsLogo;
            },

            logoSourceLabel(field) {
                if (this.hasLogoOverride(field)) return 'Using website override';
                if (this.imsLogo) return 'Using IMS logo';
                return 'No logo configured';
            },

            get selectedTemplate() {
                return this.templates.find((template) => template.key === this.form.template_key) || null;
            },

            get selectedTemplateLabel() {
                return this.selectedTemplate?.label || 'Entity-type default template';
            },

            get selectedTemplateDescription() {
                return this.selectedTemplate?.description || 'Uses the default template resolved for this entity type.';
            },

            get heroOverlayRgba() {
                const alpha = Math.max(0, Math.min(100, Number(this.form.hero_overlay_alpha || 0))) / 100;
                const hex = String(this.form.hero_overlay_hex || '#0f172a').replace('#', '');
                const normalized = hex.length === 3 ? hex.split('').map((char) => char + char).join('') : hex.padEnd(6, '0').slice(0, 6);
                const r = Number.parseInt(normalized.slice(0, 2), 16);
                const g = Number.parseInt(normalized.slice(2, 4), 16);
                const b = Number.parseInt(normalized.slice(4, 6), 16);
                return `rgba(${r}, ${g}, ${b}, ${alpha.toFixed(2)})`;
            },

            get selectedSerifFamily() {
                return this.findFontFamily('serif', this.form.default_serif_font);
            },

            get selectedSansFamily() {
                return this.findFontFamily('sans', this.form.default_sans_font);
            },

            get themePreviewStyle() {
                return {
                    backgroundColor: this.form.surface_tint,
                    borderTop: `4px solid ${this.form.accent}`,
                };
            },

            init() {
                const parsed = this.parseOverlayColor(config.initial.hero_overlay_color || 'rgba(15, 23, 42, 0.28)');
                this.form.hero_overlay_hex = parsed.hex;
                this.form.hero_overlay_alpha = parsed.alpha;
            },

            parseOverlayColor(value) {
                const input = String(value || '').trim();

                if (/^#[0-9a-fA-F]{8}$/.test(input)) {
                    const hex = `#${input.slice(1, 7)}`;
                    const alpha = Math.round((Number.parseInt(input.slice(7, 9), 16) / 255) * 100);
                    return { hex, alpha };
                }

                const rgbaMatch = input.match(/^rgba?\(([^)]+)\)$/i);
                if (rgbaMatch) {
                    const parts = rgbaMatch[1].split(',').map((part) => part.trim());
                    if (parts.length >= 3) {
                        const toHex = (value) => Number.parseInt(value, 10).toString(16).padStart(2, '0');
                        const hex = `#${toHex(parts[0])}${toHex(parts[1])}${toHex(parts[2])}`;
                        const alpha = parts[3] !== undefined ? Math.round(Number.parseFloat(parts[3]) * 100) : 100;
                        return { hex, alpha: Number.isFinite(alpha) ? alpha : 100 };
                    }
                }

                if (/^#[0-9a-fA-F]{6}$/.test(input)) {
                    return { hex: input, alpha: 100 };
                }

                return { hex: '#0f172a', alpha: 28 };
            },

            findFontFamily(group, key) {
                const options = Array.isArray(this.fontOptions[group]) ? this.fontOptions[group] : [];
                return options.find((font) => font.key === key)?.family || '';
            },

            galleryOptionLabel(gallery) {
                const status = gallery.gallery_status ? ` (${gallery.gallery_status})` : '';
                return `${gallery.title || 'Untitled gallery'}${status}`;
            },

            moveSection(index, delta) {
                const targetIndex = index + delta;
                if (targetIndex < 0 || targetIndex >= this.sectionOrder.length) {
                    return;
                }

                const next = [...this.sectionOrder];
                const [moved] = next.splice(index, 1);
                next.splice(targetIndex, 0, moved);
                this.sectionOrder = next;
            },

            resetSections() {
                this.sectionOrder = Object.keys(this.sectionOptions || {});
                this.disabledSections = [];
            },

            isSectionDisabled(sectionKey) {
                return this.disabledSections.includes(sectionKey);
            },

            toggleSection(sectionKey) {
                if (this.isSectionDisabled(sectionKey)) {
                    this.disabledSections = this.disabledSections.filter((value) => value !== sectionKey);
                    return;
                }

                this.disabledSections = [...this.disabledSections, sectionKey];
            },

            clearHeroImage() {
                this.form.website_hero_image = '';
            },

            clearLogo(field) {
                this.form[field] = '';
            },

            async openLogoMediaPicker(field, preferUpload = false) {
                const picker = window.WebCuratorMediaPicker;
                if (!picker?.open) {
                    return;
                }

                const item = await picker.open({
                    title: field === 'website_logo_on_dark' ? 'Choose logo for dark backgrounds' : 'Choose logo for light backgrounds',
                    mediaType: 'image',
                    uploadContext: 'website-appearance',
                    preferUpload,
                });

                if (item) {
                    this.form[field] = item.full_url || item.public_url || '';
                }
            },

            async openHeroMediaPicker(preferUpload = false) {
                const picker = window.WebCuratorMediaPicker;
                if (!picker?.open) {
                    return;
                }

                const item = await picker.open({
                    title: 'Choose hero image',
                    mediaType: 'image',
                    uploadContext: 'website-appearance',
                    preferUpload,
                });

                if (!item) {
                    return;
                }

                this.form.website_hero_image = item.full_url || item.public_url || '';
            },
        };
    }
</script>
@endpush
@endsection
