@extends('web_curator::layouts.default')

@php
    $currentRoleId = session('ims_user.current_db_role_id', null);
    $allRoles = collect(session('ims_user.db_roles', []));
    $currentRole = $allRoles->firstWhere('assignment_id', $currentRoleId);
    $entityName = $currentRole['scope_entity_name'] ?? 'Unknown Entity';
    $deleteUrlTemplate = route('dashboard.web_curator.snippets.destroy', '__ID__');
    $currentSort = request('sort', 'updated_at');
    $currentDirection = request('direction', 'desc');
    $sortUrl = function (string $column) use ($currentSort, $currentDirection) {
        $nextDirection = $currentSort === $column && $currentDirection === 'asc' ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => 1]);
    };
    $sortIndicator = function (string $column) use ($currentSort, $currentDirection) {
        if ($currentSort !== $column) {
            return 'both';
        }
        return $currentDirection === 'asc' ? 'up' : 'down';
    };
@endphp

@section('dashboard-content')
<div class="page-header">
    <x-dashboard.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
        ['label' => 'Snippets'],
    ]" />
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="page-title">Snippets</h2>
            <p class="mt-1 text-sm text-gray-600">
                <span class="font-semibold text-[var(--accent)]">{{ $entityName }}</span>
                @if ($snippets->total() > 0)
                    <span class="mx-1 text-gray-400">|</span>
                    <span class="font-semibold text-gray-800">{{ $snippets->total() }}</span>
                    <span class="text-gray-600">{{ Str::plural('snippet', $snippets->total()) }}</span>
                    @if (request()->hasAny(['search', 'status', 'group']))
                        <span class="text-gray-500">(filtered)</span>
                    @endif
                @else
                    <span class="mx-1 text-gray-400">|</span>
                    <span class="text-gray-600">No snippets</span>
                @endif
            </p>
        </div>
        <a href="{{ route('dashboard.web_curator.snippets.create') }}" class="btn-base btn-outline inline-flex items-center gap-2 self-start md:self-auto">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Snippet
        </a>
    </div>
</div>

<div class="card p-3 mb-6"
     x-data="{
        isDesktop: window.innerWidth >= 1024,
        open: window.innerWidth >= 1024,
        sync() {
            this.isDesktop = window.innerWidth >= 1024;
            if (this.isDesktop) this.open = true;
        }
     }"
     x-init="sync(); window.addEventListener('resize', () => sync())">
    <div class="flex items-center justify-between gap-3 lg:hidden">
        <h3 class="text-sm font-semibold text-[var(--text-strong)]">Search &amp; Filters</h3>
        <button type="button"
                class="btn-icon h-9 w-9 shrink-0"
                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-label="Toggle filters">
            <svg class="h-4.5 w-4.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>

    <form method="GET"
          action="{{ route('dashboard.web_curator.snippets.index') }}"
          class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4 items-end"
          x-show="open"
          x-collapse
          :class="isDesktop ? '' : 'mt-3'">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, slug, group, tags..." class="input-base w-full">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <x-combo-box
                :options="[
                    ['value' => '', 'label' => 'All Statuses'],
                    ['value' => 'Published', 'label' => 'Published'],
                    ['value' => 'Draft', 'label' => 'Draft'],
                ]"
                :value="request('status', '')"
                name="status"
                placeholder="Select status"
            />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
            <x-combo-box
                :options="collect([['value' => '', 'label' => 'All Groups']])
                    ->merge(collect($groups ?? [])->map(fn($group) => ['value' => (string) $group, 'label' => (string) $group]))
                    ->values()
                    ->all()"
                :value="request('group', '')"
                name="group"
                placeholder="Select group"
            />
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="submit" class="btn btn-secondary w-full whitespace-nowrap">Filter</button>
            <a href="{{ route('dashboard.web_curator.snippets.index') }}" class="btn btn-outline w-full whitespace-nowrap text-center">Clear</a>
        </div>
    </form>
</div>

@if ($snippets->isEmpty())
    <div class="card">
        <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto mb-4 h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 18 22 12 16 6M8 6l-6 6 6 6"></path>
            </svg>
            <p class="text-lg font-medium">No snippets found</p>
            <p class="mt-1 text-sm">Create your first reusable snippet to get started.</p>
        </div>
    </div>
@else
    <div class="space-y-3 lg:hidden">
        @foreach ($snippets as $snippet)
            @php
                $tags = collect(explode(',', $snippet['tags'] ?? ''))
                    ->map(fn($tag) => trim($tag))
                    ->filter()
                    ->take(3);
                $isPublished = ($snippet['status'] ?? '') === 'Published';
            @endphp
            <div class="card p-3">
                <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            @if(!empty($snippet['snippet_group']))
                                <span class="inline-flex items-center rounded-full bg-[color-mix(in_srgb,var(--accent)_14%,var(--surface))] px-2.5 py-1 text-[11px] font-medium text-[var(--accent)]">
                                    {{ $snippet['snippet_group'] }}
                                </span>
                            @endif
                            @if($isPublished)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700">Published</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700">Draft</span>
                            @endif
                        </div>

                        <button type="button"
                                class="mt-2 line-clamp-2 text-left text-base font-semibold leading-6 text-[var(--text-strong)] transition-colors hover:text-[var(--accent)]"
                                onclick='previewSnippet(@json($snippet))'>
                            {{ $snippet['name'] }}
                        </button>

                        <div class="mt-2 space-y-1.5 text-sm">
                            <div class="break-all font-mono text-[13px] text-[var(--text-soft)]">
                                {{ $snippet['slug'] }}
                            </div>
                            <div class="text-[13px] text-[var(--text-soft)]">
                                Updated {{ !empty($snippet['updated_at']) ? \Carbon\Carbon::parse($snippet['updated_at'])->format('M j, Y') : '—' }}
                            </div>
                            <div class="text-[13px] text-[var(--text-soft)]">
                                Use as <code class="rounded bg-[var(--surface-raised)] px-1.5 py-0.5 text-[12px] text-[var(--text)]">&lt;snippet slug="{{ $snippet['slug'] }}" /&gt;</code>
                            </div>
                        </div>

                        @if($tags->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($tags as $tag)
                                    <span class="inline-flex items-center rounded-full border border-[var(--border-soft)] bg-[var(--surface)] px-2.5 py-1 text-[11px] font-medium text-[var(--text-soft)]">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                </div>

                <div class="mt-3 flex items-center justify-end gap-2 border-t border-[var(--border-soft)] pt-3">
                        <button type="button" class="btn-icon h-8 w-8" style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);" onclick='previewSnippet(@json($snippet))' title="View snippet" aria-label="View snippet">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                        </button>
                        <a href="{{ route('dashboard.web_curator.snippets.edit', $snippet['id']) }}" class="btn-icon h-8 w-8" style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);" title="Edit snippet" aria-label="Edit snippet">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                        </a>
                        <button type="button" class="btn-icon h-8 w-8" style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626;" onclick="openSnippetDeleteModal({{ $snippet['id'] }}, @js($snippet['name']))" title="Delete snippet" aria-label="Delete snippet">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                        </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="hidden lg:block table-container">
        <table class="table w-full table-fixed">
            <thead>
                <tr>
                    <th class="w-[38%]">
                        <a href="{{ $sortUrl('name') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Name</span>
                            <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'name' ? 'text-gray-700' : 'text-gray-400' }}">
                                @if($sortIndicator('name') === 'up')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                @elseif($sortIndicator('name') === 'down')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th class="w-[18%]">
                        <a href="{{ $sortUrl('slug') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Slug</span>
                            <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'slug' ? 'text-gray-700' : 'text-gray-400' }}">
                                @if($sortIndicator('slug') === 'up')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                @elseif($sortIndicator('slug') === 'down')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th class="w-[16%]">
                        <a href="{{ $sortUrl('snippet_group') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Group</span>
                            <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'snippet_group' ? 'text-gray-700' : 'text-gray-400' }}">
                                @if($sortIndicator('snippet_group') === 'up')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                @elseif($sortIndicator('snippet_group') === 'down')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th class="w-[12%]">
                        <a href="{{ $sortUrl('status') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Status</span>
                            <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'status' ? 'text-gray-700' : 'text-gray-400' }}">
                                @if($sortIndicator('status') === 'up')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                @elseif($sortIndicator('status') === 'down')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th class="w-[14%]">
                        <a href="{{ $sortUrl('updated_at') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Updated</span>
                            <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'updated_at' ? 'text-gray-700' : 'text-gray-400' }}">
                                @if($sortIndicator('updated_at') === 'up')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                @elseif($sortIndicator('updated_at') === 'down')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th class="w-36 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($snippets as $snippet)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="min-w-0 flex flex-col gap-2">
                                <button type="button"
                                        class="text-left text-base font-medium text-gray-900 transition-colors hover:text-[var(--accent)]"
                                        onclick='previewSnippet(@json($snippet))'>
                                    {{ $snippet['name'] }}
                                </button>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if(!empty($snippet['snippet_group']))
                                        <span class="inline-flex items-center rounded-full bg-[color-mix(in_srgb,var(--accent)_14%,var(--surface))] px-2.5 py-1 text-xs font-medium text-[var(--accent)]">
                                            {{ $snippet['snippet_group'] }}
                                        </span>
                                    @endif
                                    @foreach(collect(explode(',', $snippet['tags'] ?? ''))->map(fn($tag) => trim($tag))->filter()->take(3) as $tag)
                                        <span class="inline-flex items-center rounded-full border border-[var(--border-soft)] bg-[var(--surface)] px-2.5 py-1 text-xs font-medium text-[var(--text-soft)]">
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <code class="block truncate text-sm text-[var(--text-soft)]">{{ $snippet['slug'] }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-[var(--text)]">{{ $snippet['snippet_group'] ?: '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(($snippet['status'] ?? '') === 'Published')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Published</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-[var(--text-soft)]">
                            {{ !empty($snippet['updated_at']) ? \Carbon\Carbon::parse($snippet['updated_at'])->format('M j, Y') : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" class="btn-icon h-10 w-10" style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);" onclick='previewSnippet(@json($snippet))' title="View snippet" aria-label="View snippet">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                                </button>
                                <a href="{{ route('dashboard.web_curator.snippets.edit', $snippet['id']) }}" class="btn-icon h-10 w-10" style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);" title="Edit snippet" aria-label="Edit snippet">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                </a>
                                <button type="button" class="btn-icon h-10 w-10" style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626;" onclick="openSnippetDeleteModal({{ $snippet['id'] }}, @js($snippet['name']))" title="Delete snippet" aria-label="Delete snippet">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($snippets->hasPages())
    <div class="mt-6">
        {{ $snippets->links() }}
    </div>
@endif

<div id="snippetDeleteModal" class="hidden fixed inset-0 z-50 bg-[var(--overlay)] p-4">
    <div class="relative mx-auto mt-16 w-full max-w-md overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)] shadow-[var(--shadow-raised)]">
        <div class="border-b border-[var(--border-soft)] px-5 py-4">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,#dc2626_14%,var(--surface))] text-[#dc2626]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-[var(--text-strong)]">Delete Snippet</h3>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">
                        Are you sure you want to delete "<span id="deleteSnippetTitle" class="font-semibold text-[var(--text-strong)]"></span>"? This action cannot be undone.
                    </p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-5 py-4">
            <button type="button" onclick="closeSnippetDeleteModal()" class="btn btn-outline">Cancel</button>
            <form id="deleteSnippetForm" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" id="deleteSnippetSubmitBtn" class="btn bg-[#dc2626] text-white hover:bg-[#b91c1c] focus-visible:ring-[#fecaca]">
                    <span class="delete-label">Delete</span>
                    <span class="delete-busy hidden inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Deleting...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

<div id="snippetPreviewModal" class="hidden fixed inset-0 z-50 bg-[var(--overlay)] p-4 lg:p-8" x-data="{ activeTab: 'preview' }">
    <div class="relative mx-auto flex h-full max-h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] shadow-[var(--shadow-raised)] lg:max-h-[calc(100vh-4rem)]">
        <div class="flex items-center justify-between p-4 lg:p-5 border-b border-[var(--border-soft)]">
            <h3 class="text-xl font-semibold text-[var(--text-strong)]" id="snippetPreviewTitle">Snippet Preview</h3>
            <button onclick="closeSnippetPreviewModal()" class="text-[var(--text-soft)] hover:text-[var(--text-strong)] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="flex overflow-x-auto border-b border-[var(--border-soft)]">
            <button @click="activeTab = 'preview'" :class="activeTab === 'preview' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'" class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">Preview</button>
            <button @click="activeTab = 'html'" :class="activeTab === 'html' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'" class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">HTML</button>
            <button @click="activeTab = 'css'" :class="activeTab === 'css' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'" class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">CSS</button>
            <button @click="activeTab = 'js'" :class="activeTab === 'js' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'" class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">JS</button>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden">
            <div x-show="activeTab === 'preview'" class="h-full bg-white p-0">
                <iframe id="snippetPreviewFrame" class="h-full w-full border-0 bg-white"></iframe>
            </div>
            <div x-show="activeTab === 'html'" class="snippet-preview-srcdoc h-full"><div class="snippet-srcdoc" id="snippetPreviewHtml"></div></div>
            <div x-show="activeTab === 'css'" class="snippet-preview-srcdoc h-full"><div class="snippet-srcdoc" id="snippetPreviewCss"></div></div>
            <div x-show="activeTab === 'js'" class="snippet-preview-srcdoc h-full"><div class="snippet-srcdoc" id="snippetPreviewJs"></div></div>
        </div>

        <div class="flex items-center justify-end border-t border-[var(--border-soft)] px-4 py-3 lg:px-5 lg:py-3">
            <button onclick="closeSnippetPreviewModal()" class="btn btn-outline">Close</button>
        </div>
    </div>
</div>

<script>
function buildSnippetPreviewDoc(snippet) {
    const html = snippet.content || '';
    const css = snippet.css || '';
    const js = String(snippet.js || '').replace(/<\/script/gi, '<\\/script');

    return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; width: 100%; max-width: 100%; overflow-x: hidden; }
    body { padding: 16px; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #fff; color: #111827; }
    img, svg, video, iframe, canvas { max-width: 100%; height: auto; }
    ${css}
  </style>
</head>
<body>
${html}
<script>
try {
${js}
} catch (error) {
  console.error(error);
}
<\/script>
</body>
</html>`;
}

function previewSnippet(snippet) {
    document.getElementById('snippetPreviewTitle').textContent = snippet.name || 'Snippet Preview';
    document.getElementById('snippetPreviewHtml').textContent = snippet.content || '';
    document.getElementById('snippetPreviewCss').textContent = snippet.css || '';
    document.getElementById('snippetPreviewJs').textContent = snippet.js || '';
    document.getElementById('snippetPreviewFrame').srcdoc = buildSnippetPreviewDoc(snippet);
    document.getElementById('snippetPreviewModal').classList.remove('hidden');
}

function closeSnippetPreviewModal() {
    document.getElementById('snippetPreviewModal').classList.add('hidden');
    document.getElementById('snippetPreviewFrame').srcdoc = '';
}

function openSnippetDeleteModal(id, title) {
    document.getElementById('deleteSnippetTitle').textContent = title;
    document.getElementById('deleteSnippetForm').action = `{{ $deleteUrlTemplate }}`.replace('__ID__', String(id));
    document.getElementById('snippetDeleteModal').classList.remove('hidden');
}

function closeSnippetDeleteModal() {
    const modal = document.getElementById('snippetDeleteModal');
    const submitBtn = document.getElementById('deleteSnippetSubmitBtn');
    submitBtn.disabled = false;
    submitBtn.querySelector('.delete-label').classList.remove('hidden');
    submitBtn.querySelector('.delete-busy').classList.add('hidden');
    modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const deleteForm = document.getElementById('deleteSnippetForm');
    const submitBtn = document.getElementById('deleteSnippetSubmitBtn');

    if (deleteForm && submitBtn) {
        deleteForm.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.querySelector('.delete-label').classList.add('hidden');
            submitBtn.querySelector('.delete-busy').classList.remove('hidden');
        });
    }
});
</script>
@endsection
