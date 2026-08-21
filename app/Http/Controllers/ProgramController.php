<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use UasDashboard\WebCurator\Concerns\InteractsWithWebApi;

class ProgramController extends Controller
{
    use InteractsWithWebApi;

    public function index(Request $request)
    {
        $context = $this->entityContext($request);
        abort_if(!$context['entity_id'], 403, 'No entity scope set for this role.');

        try {
            $programs = $this->fetchPrograms($request, $context['entity_id']);
            $profilesResponse = $this->webApiClient($request)->get($this->webApiUrl('editor/program-profiles'));
            $entityResponse = $this->webApiClient($request)->get($this->webApiUrl('entity/profile'), [
                'entity_id' => $context['entity_id'],
            ]);

            if (!$profilesResponse->successful() || !$entityResponse->successful()) {
                throw new \RuntimeException('Failed to load program publishing data.');
            }

            $profiles = collect($this->responseData($profilesResponse, []))->keyBy('ims_program_id');
            $entity = (array) $this->responseData($entityResponse, []);
            $isAcademic = strcasecmp((string) data_get($entity, 'entity_category'), 'Academic') === 0;
            $entitySlug = (string) (data_get($entity, 'slug') ?: $context['entity_slug']);

            session()->put("web_curator_entity_capabilities.{$context['entity_id']}.programs", $isAcademic);

            $programs = collect($programs)->map(function (array $program) use ($profiles, $entitySlug) {
                $profile = $profiles->get((int) data_get($program, 'id'));
                $routeSlug = data_get($profile, 'slug') ?: data_get($program, 'program_code') ?: data_get($program, 'id');

                return array_merge($program, [
                    'website_profile' => $profile,
                    'preview_url' => $this->programUrl($entitySlug, (string) $routeSlug),
                ]);
            })->values();

            return view('web_curator::programs.index', compact('programs', 'entity', 'isAcademic'));
        } catch (\Throwable $exception) {
            \Log::error('Failed to load program publishing screen.', [
                'entity_id' => $context['entity_id'],
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('dashboard.web_curator.index')
                ->with('error', 'Failed to load academic programs from IMS.');
        }
    }

    public function edit(Request $request, int $programId)
    {
        $context = $this->entityContext($request);
        abort_if(!$context['entity_id'], 403, 'No entity scope set for this role.');

        $program = collect($this->fetchPrograms($request, $context['entity_id']))
            ->firstWhere('id', $programId);
        abort_if(!$program, 404, 'Program not found for this entity.');

        $profileResponse = $this->webApiClient($request)
            ->get($this->webApiUrl("editor/program-profiles/{$programId}"));

        if (!$profileResponse->successful()) {
            return redirect()->route('dashboard.web_curator.programs.index')
                ->with('error', 'Failed to load the program website profile.');
        }

        $profile = (array) ($this->responseData($profileResponse, []) ?: []);
        $profile = array_merge($this->profileDefaults($program), $profile);
        $entityResponse = $this->webApiClient($request)->get($this->webApiUrl('entity/profile'), [
            'entity_id' => $context['entity_id'],
        ]);
        $entitySlug = (string) ($entityResponse->successful()
            ? data_get($this->responseData($entityResponse, []), 'slug')
            : $context['entity_slug']);
        $previewUrl = $this->programUrl(
            $entitySlug,
            (string) ($profile['slug'] ?: data_get($program, 'program_code') ?: $programId)
        );

        return view('web_curator::programs.edit', compact('program', 'profile', 'previewUrl'));
    }

    public function update(Request $request, int $programId)
    {
        $entityId = $this->currentEntityScope($request);
        abort_if(!$entityId, 403, 'No entity scope set for this role.');

        $programExists = collect($this->fetchPrograms($request, $entityId))->contains('id', $programId);
        abort_unless($programExists, 404, 'Program not found for this entity.');

        $validated = $request->validate([
            'slug' => 'nullable|string|max:180|alpha_dash',
            'display_title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'hero_media_item_id' => 'nullable|integer',
            'overview' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
            'admission_requirements' => 'nullable|string',
            'curriculum' => 'nullable|string',
            'career_opportunities' => 'nullable|string',
            'fees_and_funding' => 'nullable|string',
            'accreditation' => 'nullable|string|max:500',
            'application_label' => 'nullable|string|max:100',
            'application_url' => 'nullable|url|max:1000',
            'brochure_url' => 'nullable|url|max:1000',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:100',
            'custom_section_titles' => 'nullable|array|max:12',
            'custom_section_titles.*' => 'nullable|string|max:255',
            'custom_section_contents' => 'nullable|array|max:12',
            'custom_section_contents.*' => 'nullable|string',
            'status' => 'required|in:Draft,Published',
            'is_visible' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'sort_order' => 'required|integer|min:0|max:10000',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $titles = $validated['custom_section_titles'] ?? [];
        $contents = $validated['custom_section_contents'] ?? [];
        $customSections = collect($titles)->map(function ($title, $index) use ($contents) {
            return ['title' => trim((string) $title), 'content' => trim((string) ($contents[$index] ?? ''))];
        })->filter(fn ($section) => $section['title'] !== '')->values()->all();

        unset($validated['custom_section_titles'], $validated['custom_section_contents']);
        $validated['custom_sections'] = $customSections;

        $response = $this->webApiClient($request)
            ->put($this->webApiUrl("editor/program-profiles/{$programId}"), $validated);

        if (!$response->successful()) {
            return redirect()->back()->withInput()
                ->withErrors($this->flattenErrors($response, 'Failed to save the program website profile.'));
        }

        return redirect()->route('dashboard.web_curator.programs.edit', $programId)
            ->with('success', 'Program website profile updated successfully.');
    }

    public function destroy(Request $request, int $programId)
    {
        $response = $this->webApiClient($request)
            ->delete($this->webApiUrl("editor/program-profiles/{$programId}"));

        if (!$response->successful()) {
            return redirect()->back()->with('error', $this->responseMessage($response, 'Failed to reset the customization.'));
        }

        return redirect()->route('dashboard.web_curator.programs.index')
            ->with('success', 'Program website customization reset successfully.');
    }

    private function fetchPrograms(Request $request, int $entityId): array
    {
        $response = $this->imsClient($request)->get(
            rtrim((string) config('ims.api_base_url'), '/') . "/entities/{$entityId}/programs",
            ['status' => 'Active', 'per_page' => 100]
        );

        if (!$response->successful()) {
            throw new \RuntimeException((string) ($response->json('message') ?? $response->json('error') ?? 'IMS program request failed.'));
        }

        return collect($response->json('data', []))
            ->filter(fn ($program) => is_array($program))
            ->values()
            ->all();
    }

    private function imsClient(Request $request): PendingRequest
    {
        return Http::acceptJson()
            ->withToken((string) $request->cookie('ims_access_token'))
            ->connectTimeout(3)
            ->timeout(10)
            ->retry(2, 150, fn (\Throwable $exception) => $exception instanceof ConnectionException);
    }

    private function profileDefaults(array $program): array
    {
        return [
            'slug' => Str::slug((string) (data_get($program, 'program_code') ?: data_get($program, 'short_program_title'))),
            'display_title' => '',
            'subtitle' => '',
            'summary' => '',
            'hero_media_item_id' => null,
            'hero_media_item' => null,
            'overview' => '',
            'learning_outcomes' => '',
            'admission_requirements' => '',
            'curriculum' => '',
            'career_opportunities' => '',
            'fees_and_funding' => '',
            'accreditation' => '',
            'application_label' => 'Apply now',
            'application_url' => '',
            'brochure_url' => '',
            'contact_name' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'custom_sections' => [],
            'status' => 'Draft',
            'is_visible' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'seo_title' => '',
            'seo_description' => '',
        ];
    }

    private function programUrl(?string $entitySlug, string $programSlug): ?string
    {
        if (!$entitySlug) {
            return null;
        }

        return rtrim((string) config('web_curator.entity_web_base_url'), '/')
            . '/' . rawurlencode($entitySlug) . '/programs/' . rawurlencode($programSlug);
    }
}
