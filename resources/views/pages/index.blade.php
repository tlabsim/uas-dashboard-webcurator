@extends('web_curator::layouts.default')

@php
    // Get current entity information from session
    $currentRoleId = session('ims_user.current_db_role_id', null);
    $allRoles = collect(session('ims_user.db_roles', []));
    $currentRole = $allRoles->firstWhere('assignment_id', $currentRoleId);
    $entityName = $currentRole['scope_entity_name'] ?? 'Unknown Entity';
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
        ['label' => 'Static Pages'],
    ]" />
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="page-title">Static Pages</h2>
            <p class="text-sm text-gray-600 mt-1">
                <span class="font-semibold text-[var(--accent)]">{{ $entityName }}</span>
                @if ($pages->total() > 0)
                    <span class="text-gray-400 mx-1">|</span>
                    <span class="font-semibold text-gray-800">{{ $pages->total() }}</span> 
                    <span class="text-gray-600">{{ Str::plural('page', $pages->total()) }}</span>
                    @if (request()->hasAny(['search', 'status', 'category']))
                        <span class="text-gray-500">(filtered)</span>
                    @endif
                @else
                    <span class="text-gray-400 mx-1">|</span>
                    <span class="text-gray-600">No pages</span>
                @endif
            </p>
        </div>
        <a href="{{ route('dashboard.web_curator.pages.create') }}" 
           class="btn-base btn-outline inline-flex items-center gap-2 self-start md:self-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Page
        </a>
    </div>
</div>

{{-- Filters --}}
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
          action="{{ route('dashboard.web_curator.pages.index') }}"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-end"
          x-show="open"
          x-collapse
          :class="isDesktop ? '' : 'mt-3'">
        <div class="md:col-span-1">
            <label class="label-base">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Title or slug..."
                   class="input-base w-full">
        </div>
        
        <div class="w-full md:w-auto">
            <label class="label-base">Status</label>
            <x-combo-box
                class="filter-custom-select"
                :options="[
                    ['value' => '', 'label' => 'All Statuses'],
                    ['value' => 'Published', 'label' => 'Published'],
                    ['value' => 'Draft', 'label' => 'Draft'],
                    ['value' => 'Withdrawn', 'label' => 'Withdrawn'],
                ]"
                :value="request('status', '')"
                name="status"
                placeholder="Select status"
            />
        </div>

        <div class="w-full md:w-auto">
            <label class="label-base">Category</label>
            <x-combo-box
                class="filter-custom-select"
                :options="collect([['value' => '', 'label' => 'All Categories']])
                    ->merge(collect($categories)->map(function ($cat) {
                        return [
                            'value' => (string) (is_array($cat) ? $cat['id'] : $cat->id),
                            'label' => is_array($cat) ? $cat['category_name'] : $cat->category_name,
                        ];
                    }))
                    ->values()
                    ->all()"
                :value="request('category', '')"
                name="category"
                placeholder="Select category"
            />
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="submit" class="btn btn-secondary w-full whitespace-nowrap">
                Filter
            </button>
            <a href="{{ route('dashboard.web_curator.pages.index') }}" class="btn btn-outline w-full whitespace-nowrap text-center">
                Clear
            </a>
        </div>
    </form>
</div>

{{-- Pages Table --}}
@if ($pages->isEmpty())
    <div class="card">
        <div class="p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            @if (request('status'))
                @php
                    $statusMessages = [
                        'Published' => 'No published pages yet',
                        'Draft' => 'No draft pages yet',
                        'Withdrawn' => 'No withdrawn pages'
                    ];
                    $statusDescriptions = [
                        'Published' => 'Publish a page to make it visible to visitors',
                        'Draft' => 'Create a page or change a published page to draft status',
                        'Withdrawn' => 'Withdraw a published page to remove it from public view'
                    ];
                @endphp
                <p class="text-lg font-medium">{{ $statusMessages[request('status')] ?? 'No pages found' }}</p>
                <p class="text-sm mt-1">{{ $statusDescriptions[request('status')] ?? 'Try adjusting your filters' }}</p>
            @elseif (request()->hasAny(['search', 'category']))
                <p class="text-lg font-medium">No pages match your filters</p>
                <p class="text-sm mt-1">Try adjusting your search criteria or clear filters</p>
            @else
                <p class="text-lg font-medium">No static pages found</p>
                <p class="text-sm mt-1">Create your first page to get started</p>
            @endif
        </div>
    </div>
@else
    <div class="space-y-3 xl:hidden">
        @foreach ($pages as $page)
            @php
                $cat = $categories->first(function($c) use ($page) {
                    $cId = is_array($c) ? $c['id'] : $c->id;
                    return $cId == $page['page_category'];
                });
                $categoryName = $cat ? (is_array($cat) ? $cat['category_name'] : $cat->category_name) : 'Uncategorized';
                $menuLocation = '';

                if (!empty($page['is_menu']) && $page['is_menu']) {
                    $menuLabel = trim((string) ($page['menu_text'] ?? '')) ?: html_entity_decode((string) ($page['page_title'] ?? ''));
                    if (!empty($page['page_category']) && $cat) {
                        $categoryDisplayName = is_array($cat) ? $cat['category_name'] : $cat->category_name;
                        if (!empty($page['page_subcategory'])) {
                            $subcats = is_array($cat) ? ($cat['subcategories'] ?? []) : ($cat->subcategories ?? collect());
                            $subcat = collect($subcats)->first(function($s) use ($page) {
                                $sId = is_array($s) ? $s['id'] : $s->id;
                                return $sId == $page['page_subcategory'];
                            });
                            if ($subcat) {
                                $subcategoryDisplayName = is_array($subcat) ? $subcat['subcategory_name'] : $subcat->subcategory_name;
                                $menuLocation = $categoryDisplayName . ' > ' . $subcategoryDisplayName . ' > ' . $menuLabel;
                            } else {
                                $menuLocation = $categoryDisplayName . ' > ' . $menuLabel;
                            }
                        } else {
                            $menuLocation = $categoryDisplayName . ' > ' . $menuLabel;
                        }
                    } else {
                        $menuLocation = $menuLabel;
                    }
                }

                $statusBadgeClass = match($page['page_status']) {
                    'Published' => 'badge-tint-green',
                    'Draft' => 'badge-tint-yellow',
                    'Withdrawn' => 'badge-tint-red',
                    default => 'badge-tint-gray'
                };
            @endphp
            <div class="card p-3">
                <div class="flex gap-3">
                    <div class="shrink-0">
                        @if(!empty($page['featured_image_uri']))
                            <img src="{{ $page['featured_image_preview_uri'] ?? $page['featured_image_uri'] }}"
                                 alt="{!! html_entity_decode($page['page_title']) !!}"
                                 class="h-14 w-14 rounded-md border border-[var(--border-soft)] object-cover"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Crect fill=%27%23e5e7eb%27 width=%27100%27 height=%27100%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 fill=%27%239ca3af%27 font-size=%2714%27 text-anchor=%27middle%27 dy=%27.3em%27%3ENo Image%3C/text%3E%3C/svg%3E'">
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-md border border-[var(--border-soft)] bg-[var(--surface)]">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            @if(!empty($page['is_menu']) && $page['is_menu'])
                                <span class="inline-flex items-center rounded-lg bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-800">MENU</span>
                            @endif
                            <span class="inline-flex items-center rounded-lg bg-[var(--surface)] px-2 py-0.5 text-[11px] font-medium text-[var(--text-soft)] border border-[var(--border-soft)]">
                                {{ $categoryName }}
                            </span>
                            <span class="badge-tint {{ $statusBadgeClass }} text-[11px]">
                                {{ $page['page_status'] }}
                            </span>
                        </div>
                        <button type="button"
                                onclick="previewPage({{ $page['id'] }}, '{{ addslashes(html_entity_decode($page['page_title'])) }}')"
                                class="block w-full text-left text-sm font-semibold leading-5 text-gray-900 transition-colors hover:text-[var(--accent)]"
                                style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            {!! html_entity_decode($page['page_title']) !!}
                        </button>
                        @if(!empty($page['page_excerpt']))
                            <p class="mt-1 text-xs leading-5 text-gray-500"
                               style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                {{ strip_tags(html_entity_decode($page['page_excerpt'])) }}
                            </p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500">
                            <code class="max-w-full break-all rounded bg-[var(--surface)] px-1.5 py-0.5 text-[11px] text-[var(--text-soft)]">{{ $page['page_slug'] }}</code>
                            <span>{{ \Carbon\Carbon::parse($page['updated_at'])->format('M d, Y') }}</span>
                            @if($menuLocation !== '')
                                <span class="max-w-full break-all text-blue-600" title="{{ $menuLocation }}">{{ $menuLocation }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button type="button"
                            onclick="previewPage({{ $page['id'] }}, '{{ addslashes(html_entity_decode($page['page_title'])) }}')"
                            class="btn-icon h-9 w-9"
                            title="View page"
                            aria-label="View page"
                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #7c3aed;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                    </button>
                    <a href="{{ route('dashboard.web_curator.pages.edit', $page['id']) }}"
                       class="btn-icon h-9 w-9"
                       title="Edit page"
                       aria-label="Edit page"
                       style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                    </a>
                    <button type="button"
                            onclick="confirmDelete({{ $page['id'] }}, '{{ addslashes(html_entity_decode($page['page_title'])) }}')"
                            class="btn-icon h-9 w-9"
                            title="Delete page"
                            aria-label="Delete page"
                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="hidden xl:block table-container">
            <table class="table w-full table-fixed">
                <thead>
                    <tr>
                        <th class="w-24">Image</th>
                        <th class="w-[34%]">
                            <a href="{{ $sortUrl('page_title') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                <span>Title</span>
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'page_title' ? 'text-gray-700' : 'text-gray-400' }}">
                                    @if($sortIndicator('page_title') === 'up')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                    @elseif($sortIndicator('page_title') === 'down')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="w-[16%]">
                            <a href="{{ $sortUrl('page_slug') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                <span>Slug</span>
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'page_slug' ? 'text-gray-700' : 'text-gray-400' }}">
                                    @if($sortIndicator('page_slug') === 'up')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                    @elseif($sortIndicator('page_slug') === 'down')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="w-[14%]">
                            <a href="{{ $sortUrl('page_category') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                <span>Category</span>
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'page_category' ? 'text-gray-700' : 'text-gray-400' }}">
                                    @if($sortIndicator('page_category') === 'up')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                    @elseif($sortIndicator('page_category') === 'down')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="w-[12%]">
                            <a href="{{ $sortUrl('page_status') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                <span>Status</span>
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'page_status' ? 'text-gray-700' : 'text-gray-400' }}">
                                    @if($sortIndicator('page_status') === 'up')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                    @elseif($sortIndicator('page_status') === 'down')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="w-[140px]">
                            <a href="{{ $sortUrl('updated_at') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                <span>Last Updated</span>
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
                    @foreach ($pages as $page)
                        <tr>
                            <td class="!pl-6 !pr-2 !py-4 min-w-24">
                                @if(!empty($page['featured_image_uri']))
                                    <img src="{{ $page['featured_image_preview_uri'] ?? $page['featured_image_uri'] }}" 
                                         alt="{!! html_entity_decode($page['page_title']) !!}"
                                         class="w-16 h-16  aspect-[1/1] object-cover rounded-md border border-gray-200"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Crect fill=%27%23e5e7eb%27 width=%27100%27 height=%27100%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 fill=%27%239ca3af%27 font-size=%2714%27 text-anchor=%27middle%27 dy=%27.3em%27%3ENo Image%3C/text%3E%3C/svg%3E'">
                                @else
                                    <div class="w-16 h-16 shrink-0 bg-gray-100 rounded-md border border-gray-200 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="max-w-0 px-6 py-4">
                                <div class="min-w-0 flex flex-col gap-1">
                                    <button type="button"
                                            onclick="previewPage({{ $page['id'] }}, '{{ addslashes(html_entity_decode($page['page_title'])) }}')"
                                            class="truncate text-left text-base font-medium text-gray-900 transition-colors hover:text-[var(--accent)] focus:outline-none focus:text-[var(--accent)]"
                                            title="Preview {{ html_entity_decode($page['page_title']) }}">
                                        {!! html_entity_decode($page['page_title']) !!}
                                    </button>
                                    
                                    {{-- Menu Badge and Location --}}
                                    @if(!empty($page['is_menu']) && $page['is_menu'])
                                        @php
                                            $menuLocation = '';
                                            
                                            if (!empty($page['page_category'])) {
                                                $cat = $categories->first(function($c) use ($page) {
                                                    $cId = is_array($c) ? $c['id'] : $c->id;
                                                    return $cId == $page['page_category'];
                                                });
                                                
                                                if ($cat) {
                                                    $menuLabel = trim((string) ($page['menu_text'] ?? '')) ?: html_entity_decode((string) ($page['page_title'] ?? ''));
                                                    $categoryDisplayName = is_array($cat) ? $cat['category_name'] : $cat->category_name;
                                                    
                                                    if (!empty($page['page_subcategory'])) {
                                                        $subcats = is_array($cat) ? ($cat['subcategories'] ?? []) : ($cat->subcategories ?? collect());
                                                        $subcat = collect($subcats)->first(function($s) use ($page) {
                                                            $sId = is_array($s) ? $s['id'] : $s->id;
                                                            return $sId == $page['page_subcategory'];
                                                        });
                                                        
                                                        if ($subcat) {
                                                            $subcategoryDisplayName = is_array($subcat) ? $subcat['subcategory_name'] : $subcat->subcategory_name;
                                                            $menuLocation = $categoryDisplayName . ' > ' . $subcategoryDisplayName . ' > ' . $menuLabel;
                                                        } else {
                                                            $menuLocation = $categoryDisplayName . ' > ' . $menuLabel;
                                                        }
                                                    } else {
                                                        $menuLocation = $categoryDisplayName . ' > ' . $menuLabel;
                                                    }
                                                }
                                            } else {
                                                $menuLocation = trim((string) ($page['menu_text'] ?? '')) ?: html_entity_decode((string) ($page['page_title'] ?? ''));
                                            }
                                        @endphp
                                        <div class="flex min-w-0 items-center gap-2 flex-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                MENU
                                            </span>
                                            <span class="block min-w-0 flex-1 overflow-hidden text-ellipsis whitespace-nowrap text-xs text-blue-600" title="{{ $menuLocation }}">{{ $menuLocation }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($page['page_excerpt']))
                                        <div class="text-xs text-gray-500 mt-1 break-words overflow-hidden text-ellipsis">{{ Str::limit(strip_tags($page['page_excerpt']), 60) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="max-w-0 px-6 py-4">
                                <code class="block max-w-full overflow-hidden text-ellipsis whitespace-nowrap rounded bg-gray-100 px-2 py-1 text-xs text-gray-600" title="{{ $page['page_slug'] }}">{{ $page['page_slug'] }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @php
                                    $cat = $categories->first(function($c) use ($page) {
                                        $cId = is_array($c) ? $c['id'] : $c->id;
                                        return $cId == $page['page_category'];
                                    });
                                    $categoryName = $cat ? (is_array($cat) ? $cat['category_name'] : $cat->category_name) : '-';
                                @endphp
                                {{ $categoryName }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusBadgeClass = match($page['page_status']) {
                                        'Published' => 'badge-tint-green',
                                        'Draft' => 'badge-tint-yellow',
                                        'Withdrawn' => 'badge-tint-red',
                                        default => 'badge-tint-gray'
                                    };
                                @endphp
                                <span class="badge-tint {{ $statusBadgeClass }} text-sm">
                                    {{ $page['page_status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500" style="min-width: 140px;">
                                {{ \Carbon\Carbon::parse($page['updated_at'])->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            onclick="previewPage({{ $page['id'] }}, '{{ addslashes(html_entity_decode($page['page_title'])) }}')"
                                            class="btn-icon"
                                            title="View page"
                                            aria-label="View page"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #7c3aed; outline: none; -webkit-appearance: none; appearance: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                                    </button>
                                    <a href="{{ route('dashboard.web_curator.pages.edit', $page['id']) }}"
                                       class="btn-icon"
                                       title="Edit page"
                                       aria-label="Edit page"
                                       style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent); outline: none; -webkit-appearance: none; appearance: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                    </a>
                                    <button type="button"
                                            onclick="confirmDelete({{ $page['id'] }}, '{{ addslashes(html_entity_decode($page['page_title'])) }}')"
                                            class="btn-icon"
                                            title="Delete page"
                                            aria-label="Delete page"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626; outline: none; -webkit-appearance: none; appearance: none;">
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

{{-- Pagination --}}
@if ($pages->hasPages())
    <div class="mt-6">
        {{ $pages->links() }}
    </div>
@endif

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Page</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete "<span id="deletePageTitle" class="font-semibold"></span>"? This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            id="deleteSubmitBtn"
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <span class="delete-label">Delete</span>
                        <span class="delete-busy hidden inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Deleting...
                        </span>
                    </button>
                </form>
                <button onclick="closeDeleteModal()" 
                        class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div id="previewModal" class="hidden fixed inset-0 z-50 bg-[var(--overlay)] p-4 lg:p-8" x-data="{ activeTab: 'preview' }">
    <div class="relative mx-auto flex h-full max-h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] shadow-[var(--shadow-raised)] lg:max-h-[calc(100vh-4rem)]">
        <div class="flex items-center justify-between p-4 lg:p-5 border-b border-[var(--border-soft)]">
            <h3 class="text-xl font-semibold text-[var(--text-strong)]" id="previewPageTitle">Page Preview</h3>
            <div class="flex items-center gap-2">
                <a id="previewPageOpenLink"
                   href="#"
                   target="_blank"
                   rel="noopener"
                   class="btn-base btn-secondary h-8 gap-2 px-3 text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"><path stroke-linejoin="round" d="M21 3h-6m6 0l-9 9m9-9v6"/><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></g></svg>
                    Preview
                </a>
                <button onclick="closePreviewModal()" class="text-[var(--text-soft)] hover:text-[var(--text-strong)] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Tabs --}}
        <div class="flex overflow-x-auto border-b border-[var(--border-soft)]">
            <button @click="activeTab = 'preview'" 
                    :class="activeTab === 'preview' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Preview
            </button>
            <button @click="activeTab = 'raw'" 
                    :class="activeTab === 'raw' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Raw HTML
            </button>
            <button @click="activeTab = 'custom'" 
                    :class="activeTab === 'custom' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Custom CSS/JS
            </button>
        </div>
        
        {{-- Tab Content --}}
        <div class="flex-1 min-h-0 overflow-y-auto p-4 lg:p-5">
            {{-- Preview Tab --}}
            <div x-show="activeTab === 'preview'" class="h-full min-h-[24rem] rounded-xl border border-[var(--border-soft)] bg-white overflow-hidden">
                <iframe id="previewIframe" class="w-full h-full border-0"></iframe>
            </div>
            
            {{-- Raw HTML Tab --}}
            <div x-show="activeTab === 'raw'" class="h-full min-h-[24rem] overflow-y-auto rounded-xl border border-[var(--border-soft)] bg-[var(--surface)]">
                <pre id="rawHtmlContent" class="p-4 text-sm whitespace-pre-wrap font-mono text-[var(--text)]"></pre>
            </div>
            
            {{-- Custom CSS/JS Tab --}}
            <div x-show="activeTab === 'custom'" class="h-full min-h-[24rem] overflow-y-auto rounded-xl border border-[var(--border-soft)] bg-[var(--surface)]">
                <div class="p-4">
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold mb-2 flex items-center text-[var(--text)]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Custom CSS
                        </h4>
                        <pre id="customCssContent" class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-3 text-sm font-mono whitespace-pre-wrap text-[var(--text)]"></pre>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold mb-2 flex items-center text-[var(--text)]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            Custom JavaScript
                        </h4>
                        <pre id="customJsContent" class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-3 text-sm font-mono whitespace-pre-wrap text-[var(--text)]"></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end px-4 py-3 lg:px-5 lg:py-3 border-t border-[var(--border-soft)]">
            <button onclick="closePreviewModal()" 
                    class="btn btn-outline">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Store pages data for preview
const pagesData = @json($pages->items());

// Helper function to decode HTML entities
function decodeHtml(html) {
    const txt = document.createElement('textarea');
    txt.innerHTML = html;
    return txt.value;
}

function confirmDelete(pageId, pageTitle) {
    const decodedTitle = decodeHtml(pageTitle);
    document.getElementById('deletePageTitle').textContent = decodedTitle;
    document.getElementById('deleteForm').action = "{{ route('dashboard.web_curator.pages.destroy', ':id') }}".replace(':id', pageId);
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    const deleteForm = document.getElementById('deleteForm');
    const deleteSubmitBtn = document.getElementById('deleteSubmitBtn');
    if (deleteForm) {
        deleteForm.dataset.submitting = 'false';
    }
    if (deleteSubmitBtn) {
        deleteSubmitBtn.disabled = false;
        deleteSubmitBtn.querySelector('.delete-label')?.classList.remove('hidden');
        deleteSubmitBtn.querySelector('.delete-busy')?.classList.add('hidden');
    }
}

const getRenderedContentPreviewCss = () => {
    const bg = '#ffffff';

    return `
    html, body { margin: 0; padding: 0; width: 100%; max-width: 100%; overflow-x: hidden; }
    body { font-family: system-ui, -apple-system, sans-serif; padding: 0 20px; line-height: 1.75; background: ${bg}; color: #0f172a; box-sizing: border-box; }
    *, *::before, *::after { box-sizing: border-box; }
    body > * { max-width: 100%; }
    body > :first-child { margin-top: 0 !important; }
    body > :last-child { margin-bottom: 0 !important; }
    img, video, iframe, svg, canvas { max-width: 100%; height: auto; }
    iframe { width: 100%; }
    .wc-rendered-content { max-width: 100%; }
`;
};

@php($renderedContentAssets = app(\App\Support\ModuleAssets::class)->urls('web_curator', 'rendered-content'))
const renderedContentCssUrl = @js($renderedContentAssets['css'][0] ?? '');
const renderedContentJsUrl = @js($renderedContentAssets['js'][0] ?? '');

function previewPage(pageId, pageTitle) {
    const page = pagesData.find(p => p.id === pageId);
    if (!page) {
        alert('Page data not found');
        return;
    }

    const renderedContent = page.page_content || '';
    
    const decodedTitle = decodeHtml(pageTitle);
    
    // Set title
    document.getElementById('previewPageTitle').textContent = decodedTitle;
    const previewPageOpenLink = document.getElementById('previewPageOpenLink');
    if (previewPageOpenLink) {
        previewPageOpenLink.href = @json(route('dashboard.web_curator.pages.preview', ':id')).replace(':id', pageId);
    }
    
    // Build full HTML with custom CSS/JS
    let fullHtml = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${decodedTitle}</title>
            <link rel="stylesheet" href="${renderedContentCssUrl}">
            <style>
                ${getRenderedContentPreviewCss()}
                ${page.custom_css || ''}
            </style>
        </head>
        <body>
            <div class="wc-rendered-content">
                ${renderedContent}
            </div>
            <script src="${renderedContentJsUrl}"><\/script>
            <script>window.addEventListener('load',function(){window.WebCuratorRenderedContent?.mount?.(document.querySelector('.wc-rendered-content'));});<\/script>
            <script>${page.custom_js || ''}<\/script>
        </body>
        </html>
    `;
    
    // Set iframe content
    const iframe = document.getElementById('previewIframe');
    iframe.srcdoc = fullHtml;
    
    // Set raw HTML content
    document.getElementById('rawHtmlContent').textContent = page.page_content;
    
    // Set custom CSS/JS content
    document.getElementById('customCssContent').textContent = page.custom_css || '/* No custom CSS */';
    document.getElementById('customJsContent').textContent = page.custom_js || '// No custom JavaScript';
    
    // Show modal
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

document.getElementById('deleteForm')?.addEventListener('submit', function () {
    if (this.dataset.submitting === 'true') {
        return;
    }

    this.dataset.submitting = 'true';
    const deleteSubmitBtn = document.getElementById('deleteSubmitBtn');
    if (deleteSubmitBtn) {
        deleteSubmitBtn.disabled = true;
        deleteSubmitBtn.querySelector('.delete-label')?.classList.add('hidden');
        deleteSubmitBtn.querySelector('.delete-busy')?.classList.remove('hidden');
    }
});
</script>
@endpush

@push('styles')
<style>
/* Responsive filter grid */
@media (max-width: 767px) {
    .grid.grid-cols-1 {
        gap: 0.75rem;
    }
}
</style>
@endpush

@endsection
