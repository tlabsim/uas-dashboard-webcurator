<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UasDashboard\WebCurator\Concerns\InteractsWithWebApi;

class WebsiteAppearanceController extends Controller
{
    use InteractsWithWebApi;

    private const SETTINGS_GROUP = 'Website appearance';

    private const REQUIRED_KEYS = [
        'accent' => 'string',
        'accent_soft' => 'string',
        'surface_tint' => 'string',
        'website-hero-bg-type' => 'string',
        'hero_subheading' => 'string',
        'hero_subheading_position' => 'string',
        'homepage_sections_order' => 'string',
        'homepage_sections_disabled' => 'string',
    ];

    private const OPTIONAL_KEYS = [
        'template_key' => 'string',
        'hero_title' => 'string',
        'hero_summary' => 'string',
        'hero_overlay_color' => 'string',
        'hero_title_color' => 'string',
        'hero_subtitle_color' => 'string',
        'default_serif_font' => 'string',
        'default_sans_font' => 'string',
        'featured_gallery_id' => 'string',
        'website-logo-on-light' => 'string',
        'website-logo-on-dark' => 'string',
        'website-hero-image' => 'string',
        'website-hero-video' => 'string',
    ];

    private const SECTION_ORDER = [
        'head_message' => 'Head Message',
        'research' => 'Research',
        'news' => 'News',
        'notices' => 'Notices',
        'events' => 'Events & Activities',
        'scholarships' => 'Scholarships',
        'other_updates' => 'Other Updates',
        'featured_gallery' => 'Featured Gallery',
    ];

    public function edit(Request $request)
    {
        $context = $this->entityContext($request);
        $entityId = $context['entity_id'];

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            $settingsResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/entity/settings'), [
                'entity_id' => $entityId,
            ]);

            $entityResponse = $this->webApiClient($request)->get($this->webApiUrl('entity'), [
                'entity_id' => $entityId,
            ]);

            $galleriesResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/galleries'), [
                'fetch_all' => true,
            ]);

            if (!$settingsResponse->successful() || !$entityResponse->successful() || !$galleriesResponse->successful()) {
                return redirect()
                    ->route('dashboard.web_curator.index')
                    ->with('error', 'Failed to load website appearance settings.');
            }

            $entity = $this->normalizeEntity((array) $this->responseData($entityResponse, []), $context);
            $appearanceSettings = $this->settingsCollection($settingsResponse)
                ->where('key_group', self::SETTINGS_GROUP)
                ->keyBy('setting_key');

            $defaults = $this->defaultAppearance($entity);
            $values = $defaults;

            foreach ($appearanceSettings as $settingKey => $setting) {
                $values[$settingKey] = data_get($setting, 'value');
            }

            $templates = $this->templateOptionsForEntity($entity);
            $fontOptions = config('web_curator.website_font_options', []);
            $sectionOrder = $this->normalizeSectionOrder((string) ($values['homepage_sections_order'] ?? ''));
            $disabledSections = $this->normalizeSectionState((string) ($values['homepage_sections_disabled'] ?? ''));
            $galleries = collect(data_get($this->responseData($galleriesResponse, []), 'data', $this->responseData($galleriesResponse, [])))
                ->filter(fn ($gallery) => is_array($gallery) || is_object($gallery))
                ->map(fn ($gallery) => (array) $gallery)
                ->values()
                ->all();

            return view('web_curator::website_appearance.edit', [
                'entity' => $entity,
                'appearanceValues' => $values,
                'templates' => $templates,
                'fontOptions' => $fontOptions,
                'galleries' => $galleries,
                'sectionOptions' => self::SECTION_ORDER,
                'sectionOrder' => $sectionOrder,
                'disabledSections' => $disabledSections,
            ]);
        } catch (\Throwable $exception) {
            \Log::error('Failed to load website appearance screen.', [
                'entity_id' => $entityId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('dashboard.web_curator.index')
                ->with('error', 'Failed to load website appearance settings.');
        }
    }

    public function update(Request $request)
    {
        $context = $this->entityContext($request);
        $entityId = $context['entity_id'];

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        $validated = $request->validate([
            'template_key' => 'nullable|string|max:100',
            'hero_title' => 'nullable|string|max:255',
            'hero_subheading' => 'nullable|string|max:255',
            'hero_subheading_position' => 'required|in:auto,above,below',
            'hero_summary' => 'nullable|string|max:1200',
            'hero_overlay_color' => ['nullable', 'regex:/^(rgba?\([^)]+\)|#[0-9A-Fa-f]{8}|#[0-9A-Fa-f]{6})$/'],
            'hero_title_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'hero_subtitle_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'default_serif_font' => 'nullable|string|max:100',
            'default_sans_font' => 'nullable|string|max:100',
            'featured_gallery_id' => 'nullable|integer|min:1',
            'website_logo_on_light' => 'nullable|string|max:2048',
            'website_logo_on_dark' => 'nullable|string|max:2048',
            'website_hero_bg_type' => 'required|in:image,video',
            'website_hero_image' => 'nullable|string|max:2048',
            'website_hero_video' => 'nullable|string|max:2048',
            'accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_soft' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_tint' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'section_order' => 'required|array|min:1',
            'section_order.*' => 'required|string|in:' . implode(',', array_keys(self::SECTION_ORDER)),
            'disabled_sections' => 'nullable|array',
            'disabled_sections.*' => 'required|string|in:' . implode(',', array_keys(self::SECTION_ORDER)),
        ]);

        try {
            $settingsResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/entity/settings'), [
                'entity_id' => $entityId,
            ]);

            if (!$settingsResponse->successful()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Failed to load existing website appearance settings.');
            }

            $existingSettings = $this->settingsCollection($settingsResponse)
                ->where('key_group', self::SETTINGS_GROUP)
                ->keyBy('setting_key');

            $payload = [
                'template_key' => trim((string) ($validated['template_key'] ?? '')),
                'hero_title' => trim((string) ($validated['hero_title'] ?? '')),
                'hero_subheading' => trim((string) ($validated['hero_subheading'] ?? '')),
                'hero_subheading_position' => $validated['hero_subheading_position'],
                'hero_summary' => trim((string) ($validated['hero_summary'] ?? '')),
                'hero_overlay_color' => trim((string) ($validated['hero_overlay_color'] ?? '')),
                'hero_title_color' => trim((string) ($validated['hero_title_color'] ?? '')),
                'hero_subtitle_color' => trim((string) ($validated['hero_subtitle_color'] ?? '')),
                'default_serif_font' => trim((string) ($validated['default_serif_font'] ?? '')),
                'default_sans_font' => trim((string) ($validated['default_sans_font'] ?? '')),
                'featured_gallery_id' => isset($validated['featured_gallery_id']) ? (string) $validated['featured_gallery_id'] : '',
                'website-logo-on-light' => trim((string) ($validated['website_logo_on_light'] ?? '')),
                'website-logo-on-dark' => trim((string) ($validated['website_logo_on_dark'] ?? '')),
                'website-hero-bg-type' => $validated['website_hero_bg_type'],
                'website-hero-image' => trim((string) ($validated['website_hero_image'] ?? '')),
                'website-hero-video' => trim((string) ($validated['website_hero_video'] ?? '')),
                'accent' => $validated['accent'],
                'accent_soft' => $validated['accent_soft'],
                'surface_tint' => $validated['surface_tint'],
                'homepage_sections_order' => json_encode(array_values(array_unique($validated['section_order']))),
                'homepage_sections_disabled' => json_encode(array_values(array_unique($validated['disabled_sections'] ?? []))),
            ];

            foreach (self::REQUIRED_KEYS as $settingKey => $valueType) {
                $value = $payload[$settingKey] ?? '';
                $this->upsertSetting($request, $existingSettings->get($settingKey), $entityId, $settingKey, $value, $valueType);
            }

            foreach (self::OPTIONAL_KEYS as $settingKey => $valueType) {
                $value = $payload[$settingKey] ?? '';
                if ($value === '') {
                    $this->deleteSettingIfPresent($request, $existingSettings->get($settingKey), $entityId);
                    continue;
                }

                $this->upsertSetting($request, $existingSettings->get($settingKey), $entityId, $settingKey, $value, $valueType);
            }

            return redirect()
                ->route('dashboard.web_curator.website_appearance.edit')
                ->with('success', 'Website appearance updated successfully.');
        } catch (\Throwable $exception) {
            \Log::error('Failed to update website appearance settings.', [
                'entity_id' => $entityId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update website appearance settings.');
        }
    }

    protected function upsertSetting(
        Request $request,
        ?array $existingSetting,
        int $entityId,
        string $settingKey,
        string $value,
        string $valueType
    ): void {
        if ($existingSetting) {
            $response = $this->webApiClient($request)->put(
                $this->webApiUrl('editor/entity/settings/' . data_get($existingSetting, 'id')),
                [
                    'entity_id' => $entityId,
                    'key_group' => self::SETTINGS_GROUP,
                    'value' => $value,
                    'value_type' => $valueType,
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException($this->responseMessage($response, "Failed to update {$settingKey}."));
            }

            return;
        }

        $response = $this->webApiClient($request)->post($this->webApiUrl('editor/entity/settings/create'), [
            'entity_id' => $entityId,
            'key_group' => self::SETTINGS_GROUP,
            'setting_key' => $settingKey,
            'value' => $value,
            'value_type' => $valueType,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException($this->responseMessage($response, "Failed to save {$settingKey}."));
        }
    }

    protected function deleteSettingIfPresent(Request $request, ?array $existingSetting, int $entityId): void
    {
        if (!$existingSetting) {
            return;
        }

        $response = $this->webApiClient($request)->delete(
            $this->webApiUrl('editor/entity/settings/' . data_get($existingSetting, 'id')),
            ['entity_id' => $entityId]
        );

        if (!$response->successful()) {
            throw new \RuntimeException($this->responseMessage($response, 'Failed to clear a website appearance setting.'));
        }
    }

    protected function settingsCollection($response): Collection
    {
        return collect($this->responseData($response, []))
            ->filter(fn ($setting) => is_array($setting) || is_object($setting))
            ->map(fn ($setting) => (array) $setting)
            ->values();
    }

    protected function defaultAppearance(array $entity): array
    {
        return [
            'template_key' => '',
            'hero_title' => (string) data_get($entity, 'cachedData.full_name', data_get($entity, 'cachedData.name', data_get($entity, 'entity_name', ''))),
            'hero_subheading' => (string) data_get($entity, 'cachedData.short_name', data_get($entity, 'entity_short_name', '')),
            'hero_subheading_position' => 'auto',
            'hero_summary' => (string) data_get($entity, 'cachedData.description', ''),
            'hero_overlay_color' => 'rgba(15, 23, 42, 0.28)',
            'hero_title_color' => '#ffffff',
            'hero_subtitle_color' => '#e2e8f0',
            'default_serif_font' => 'source-serif-4',
            'default_sans_font' => 'source-sans-3',
            'featured_gallery_id' => '',
            'website-logo-on-light' => '',
            'website-logo-on-dark' => '',
            'website-hero-bg-type' => 'image',
            'website-hero-image' => '',
            'website-hero-video' => '',
            'accent' => '#0e7490',
            'accent_soft' => '#dbeafe',
            'surface_tint' => '#f8fafc',
            'homepage_sections_order' => json_encode(array_keys(self::SECTION_ORDER)),
            'homepage_sections_disabled' => json_encode([]),
        ];
    }

    protected function normalizeSectionOrder(string $raw): array
    {
        if ($raw === '') {
            return array_keys(self::SECTION_ORDER);
        }

        $decoded = json_decode($raw, true);
        $values = is_array($decoded) ? $decoded : array_map('trim', explode(',', $raw));

        $order = collect($values)
            ->filter(fn ($value) => array_key_exists($value, self::SECTION_ORDER))
            ->unique()
            ->values();

        // Existing saved orders predate Research; insert it at its intended default position.
        if (!$order->contains('research')) {
            $newsIndex = $order->search('news');
            $newsIndex === false
                ? $order->push('research')
                : $order->splice($newsIndex, 0, ['research']);
        }

        return $order->merge(array_keys(self::SECTION_ORDER))->unique()->values()->all();
    }

    protected function normalizeSectionState(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        $values = is_array($decoded) ? $decoded : array_map('trim', explode(',', $raw));

        return collect($values)
            ->filter(fn ($value) => array_key_exists($value, self::SECTION_ORDER))
            ->unique()
            ->values()
            ->all();
    }

    protected function templateOptionsForEntity(array $entity): array
    {
        $entityType = Str::lower(trim((string) data_get($entity, 'cachedData.entity_type', data_get($entity, 'entity_type', ''))));
        $templates = config('web_curator.website_templates', []);

        return collect($templates)
            ->filter(function ($template) use ($entityType) {
                $allowedTypes = collect(data_get($template, 'entity_types', []))
                    ->map(fn ($value) => Str::lower(trim((string) $value)))
                    ->filter()
                    ->values();

                return $allowedTypes->isEmpty() || $allowedTypes->contains($entityType);
            })
            ->values()
            ->all();
    }

    protected function normalizeEntity(array $entity, array $context): array
    {
        if (!isset($entity['cachedData']) && isset($entity['cached_data']) && is_array($entity['cached_data'])) {
            $entity['cachedData'] = $entity['cached_data'];
        }

        if (!isset($entity['cached_data']) && isset($entity['cachedData']) && is_array($entity['cachedData'])) {
            $entity['cached_data'] = $entity['cachedData'];
        }

        $cached = data_get($entity, 'cachedData', []);

        if (!is_array($cached)) {
            $cached = [];
        }

        $cached['name'] = $cached['name'] ?? data_get($entity, 'entity_name', $context['entity_name']);
        $cached['short_name'] = $cached['short_name'] ?? data_get($entity, 'entity_short_name');
        $entity['cachedData'] = $cached;
        $entity['entity_name'] = data_get($entity, 'entity_name', $context['entity_name']);
        $entity['entity_slug'] = data_get($entity, 'slug', $context['entity_slug']);

        return $entity;
    }
}
