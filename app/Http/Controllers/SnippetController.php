<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class SnippetController extends Controller
{
    public function index(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/editor/snippets', [
                'entity_id' => $entityId,
            ]);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to load snippets. Please try again.');
            }

            $snippetsData = collect($response->json()['data'] ?? [])
                ->map(fn ($snippet) => $this->normalizeSnippet($snippet))
                ->values();

            $search = trim((string) $request->input('search', ''));
            $status = (string) $request->input('status', '');
            $group = (string) $request->input('group', '');
            $sort = (string) $request->input('sort', 'updated_at');
            $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

            $filtered = $snippetsData->filter(function ($snippet) use ($search, $status, $group) {
                $matchesSearch = $search === ''
                    || str_contains(mb_strtolower((string) $snippet['name']), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $snippet['slug']), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $snippet['snippet_group']), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $snippet['tags']), mb_strtolower($search));

                $matchesStatus = $status === '' || (string) $snippet['status'] === $status;
                $matchesGroup = $group === '' || (string) $snippet['snippet_group'] === $group;

                return $matchesSearch && $matchesStatus && $matchesGroup;
            });

            $sortableColumns = [
                'name' => fn ($snippet) => mb_strtolower((string) ($snippet['name'] ?? '')),
                'slug' => fn ($snippet) => mb_strtolower((string) ($snippet['slug'] ?? '')),
                'snippet_group' => fn ($snippet) => mb_strtolower((string) ($snippet['snippet_group'] ?? '')),
                'status' => fn ($snippet) => mb_strtolower((string) ($snippet['status'] ?? '')),
                'updated_at' => fn ($snippet) => strtotime((string) ($snippet['updated_at'] ?? '')) ?: 0,
            ];

            if (isset($sortableColumns[$sort])) {
                $filtered = $filtered->sortBy(
                    $sortableColumns[$sort],
                    SORT_NATURAL | SORT_FLAG_CASE,
                    $direction === 'desc'
                )->values();
            }

            $groups = $snippetsData
                ->pluck('snippet_group')
                ->filter(fn ($value) => filled($value))
                ->unique()
                ->sort()
                ->values();

            $perPage = 15;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $items = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $snippets = new LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('web_curator::snippets.index', compact('snippets', 'groups'));
        } catch (\Exception $e) {
            \Log::error('Error fetching snippets', ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to load snippets.');
        }
    }

    public function create(Request $request)
    {
        return view('web_curator::snippets.create');
    }

    public function store(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        $validated = $request->validate([
            'name' => 'required|string|max:240',
            'slug' => 'nullable|string|max:50',
            'snippet_group' => 'nullable|string|max:240',
            'content' => 'nullable|string',
            'css' => 'nullable|string',
            'js' => 'nullable|string',
            'tags' => 'nullable|string',
            'status' => 'required|in:Draft,Published',
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->post(config('web-api.api_base_url') . '/editor/snippets', array_merge($validated, [
            'entity_id' => $entityId,
        ]));

        if ($response->successful()) {
            $snippet = $this->normalizeSnippet($response->json('data', []));

            return redirect()
                ->route('dashboard.web_curator.snippets.edit', $snippet['id'])
                ->with('success', 'Snippet created successfully.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to create snippet: ' . ($response->json('message') ?? 'Unknown error'));
    }

    public function edit(Request $request, $id)
    {
        $entityId = $request->attributes->get('current_role_scope');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->get(config('web-api.api_base_url') . '/editor/snippets', [
            'entity_id' => $entityId,
        ]);

        if (!$response->successful()) {
            return redirect()->route('dashboard.web_curator.snippets.index')->with('error', 'Failed to load snippet.');
        }

        $snippet = collect($response->json('data', []))
            ->map(fn ($item) => $this->normalizeSnippet($item))
            ->firstWhere('id', (int) $id);

        if (!$snippet) {
            return redirect()->route('dashboard.web_curator.snippets.index')->with('error', 'Snippet not found.');
        }

        return view('web_curator::snippets.edit', compact('snippet'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:240',
            'slug' => 'nullable|string|max:50',
            'snippet_group' => 'nullable|string|max:240',
            'content' => 'nullable|string',
            'css' => 'nullable|string',
            'js' => 'nullable|string',
            'tags' => 'nullable|string',
            'status' => 'required|in:Draft,Published',
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->put(config('web-api.api_base_url') . '/editor/snippets/' . $id, $validated);

        if ($response->successful()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $response->json('message') ?? 'Snippet updated successfully.',
                    'data' => $this->normalizeSnippet($response->json('data', [])),
                ]);
            }

            return redirect()->route('dashboard.web_curator.snippets.edit', $id)
                ->with('success', 'Snippet updated successfully.');
        }

        $message = $response->json('message') ?? 'Failed to update snippet.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'error', 'message' => $message], $response->status());
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    public function destroy(Request $request, $id)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
        ])->delete(config('web-api.api_base_url') . '/editor/snippets/' . $id);

        if ($response->successful()) {
            return redirect()->route('dashboard.web_curator.snippets.index')
                ->with('success', 'Snippet deleted successfully.');
        }

        return redirect()->route('dashboard.web_curator.snippets.index')
            ->with('error', $response->json('message') ?? 'Failed to delete snippet.');
    }

    protected function normalizeSnippet(array $snippet): array
    {
        $meta = collect($snippet['meta'] ?? []);
        $cssMeta = $meta->firstWhere('meta_key', 'css');
        $jsMeta = $meta->firstWhere('meta_key', 'js');

        return [
            'id' => (int) ($snippet['id'] ?? 0),
            'name' => (string) ($snippet['name'] ?? ''),
            'slug' => (string) ($snippet['slug'] ?? ''),
            'snippet_group' => (string) ($snippet['snippet_group'] ?? ''),
            'content' => (string) ($snippet['content'] ?? ''),
            'css' => (string) data_get($cssMeta, 'meta_value', ''),
            'js' => (string) data_get($jsMeta, 'meta_value', ''),
            'tags' => (string) ($snippet['tags'] ?? ''),
            'status' => (string) ($snippet['status'] ?? 'Draft'),
            'updated_at' => $snippet['updated_at'] ?? null,
            'published_at' => $snippet['published_at'] ?? null,
        ];
    }
}
