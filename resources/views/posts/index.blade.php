@extends('web_curator::layouts.default')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@moduleVite('web_curator', 'rendered-content')
@endpush

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
    $taggedEntityLookup = collect($entities ?? [])
        ->mapWithKeys(function ($entity) {
            $id = (int) data_get($entity, 'id', 0);

            if ($id <= 0) {
                return [];
            }

            return [
                $id => [
                    'display_name' => (string) (
                        data_get($entity, 'display_name')
                        ?: data_get($entity, 'entity_name')
                        ?: 'Unknown Entity'
                    ),
                    'entity_type' => (string) (data_get($entity, 'entity_type') ?: 'Entity'),
                ],
            ];
        })
        ->all();
@endphp

@section('dashboard-content')
<div x-data="{
    activeType: '{{ request('category_id', 'all') }}',
    
    filterByType(categoryId) {
        this.activeType = categoryId;
        const url = new URL(window.location.href);
        if (categoryId === 'all') {
            url.searchParams.delete('category_id');
        } else {
            url.searchParams.set('category_id', categoryId);
        }
        url.searchParams.delete('page'); // Reset to first page
        window.location.href = url.toString();
    }
}">

<div class="page-header">
    <x-dashboard.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
        ['label' => 'Posts'],
    ]" />
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="page-title">Posts</h2>
            <p class="text-sm text-gray-600 mt-1">
                <span class="font-semibold text-[var(--accent)]">{{ $entityName }}</span>
            @if ($posts->total() > 0)
                <span class="text-gray-400 mx-1">|</span>
                <span class="font-semibold text-gray-800">{{ $posts->total() }}</span> 
                <span class="text-gray-600">{{ Str::plural('post', $posts->total()) }}</span>
                @if (request()->hasAny(['search', 'status', 'category_id', 'date_from', 'date_to']))
                    <span class="text-gray-500">(filtered)</span>
                @endif
            @else
                <span class="text-gray-400 mx-1">|</span>
                <span class="text-gray-600">No posts</span>
            @endif
            </p>
        </div>
        <a href="{{ route('dashboard.web_curator.posts.create') }}" 
           class="btn-base btn-outline inline-flex items-center gap-2 self-start md:self-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Post
        </a>
    </div>
</div>

{{-- Quick Filter Pills - Categories --}}
<div class="flex flex-wrap gap-2 mb-4 border-t border-[var(--border-soft)] pt-4">
    <button @click="filterByType('all')"
            :class="activeType === 'all'
                ? 'text-[var(--accent-foreground)] border-transparent shadow-sm'
                : 'text-[var(--text)] border-[var(--border-soft)] hover:bg-[var(--surface)]'"
            :style="activeType === 'all'
                ? 'background: var(--accent);'
                : 'background: var(--surface-raised);'"
            class="px-5 py-2 rounded-full border transition font-medium text-sm">
        All Posts
    </button>
    @foreach ($categories as $category)
        @php
            $cat = is_array($category) ? (object)$category : $category;
            $catId = $cat->id ?? '';
            $catName = $cat->name ?? '';
        @endphp
        <button @click="filterByType('{{ $catId }}')"
                :class="activeType === '{{ $catId }}'
                    ? 'text-[var(--accent-foreground)] border-transparent shadow-sm'
                    : 'text-[var(--text)] border-[var(--border-soft)] hover:bg-[var(--surface)]'"
                :style="activeType === '{{ $catId }}'
                    ? 'background: var(--accent);'
                    : 'background: var(--surface-raised);'"
                class="px-5 py-2 rounded-full border transition font-medium text-sm">
            {{ $catName }}
        </button>
    @endforeach
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
          action="{{ route('dashboard.web_curator.posts.index') }}"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 items-end"
          x-show="open"
          x-collapse
          :class="isDesktop ? '' : 'mt-3'">
        {{-- Preserve category filter (from pills) --}}
        @if(request('category_id'))
            <input type="hidden" name="category_id" value="{{ request('category_id') }}">
        @endif
        
        {{-- Preserve sort parameters --}}
        @if(request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        @if(request('direction'))
            <input type="hidden" name="direction" value="{{ request('direction') }}">
        @endif
        
        <div class="md:col-span-1 lg:col-span-2">
            <label class="label-base">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Title or excerpt..."
                   class="input-base w-full">
        </div>
        
        <div class="w-full md:w-auto md:flex-1 lg:min-w-[150px]">
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
            <label class="label-base">Start Date</label>
            <input type="text" 
                   name="date_from" 
                   value="{{ request('date_from') }}" 
                   placeholder="From date"
                   x-data
                   x-init="flatpickr($el, {
                       dateFormat: 'Y-m-d',
                       allowInput: true,
                       maxDate: $el.form.date_to?.value || 'today'
                   })"
                   class="input-base w-full">
        </div>

        <div class="w-full md:w-auto">
            <label class="label-base">End Date</label>
            <input type="text" 
                   name="date_to" 
                   value="{{ request('date_to') }}" 
                   placeholder="To date"
                   x-data
                   x-init="flatpickr($el, {
                       dateFormat: 'Y-m-d',
                       allowInput: true,
                       minDate: $el.form.date_from?.value || null,
                       maxDate: 'today'
                   })"
                   class="input-base w-full">
        </div>

        <div class="grid grid-cols-2 gap-2 md:col-span-2 lg:col-span-1">
            <button type="submit" class="btn btn-secondary w-full whitespace-nowrap">
                Filter
            </button>
            <a href="{{ route('dashboard.web_curator.posts.index') }}" class="btn btn-outline w-full whitespace-nowrap text-center">
                Clear
            </a>
        </div>
    </form>
</div>

{{-- Posts Table --}}
@if ($posts->isEmpty())
    <div class="card">
        <div class="p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
            </svg>
            @if (request('status'))
                <p class="text-lg font-medium">No {{ strtolower(request('status')) }} posts found</p>
                <p class="text-sm mt-1">Create a new post or change a post's status</p>
            @elseif (request()->hasAny(['search', 'category_id', 'date_from', 'date_to']))
                <p class="text-lg font-medium">No posts match your filters</p>
                <p class="text-sm mt-1">Try adjusting your search criteria or clear filters</p>
            @else
                <p class="text-lg font-medium">No posts found</p>
                <p class="text-sm mt-1">Create your first post to get started</p>
            @endif
        </div>
@else
        <div class="space-y-3 lg:hidden">
            @foreach ($posts as $post)
                @php
                    $categoryName = '';
                    if (isset($post['category'])) {
                        if (is_array($post['category'])) {
                            $categoryName = $post['category']['name'] ?? '';
                        } elseif (is_object($post['category'])) {
                            $categoryName = $post['category']->name ?? '';
                        } else {
                            $categoryName = $post['category'];
                        }
                    }
                    $authorName = trim((string) html_entity_decode($post['author'] ?? ''));
                    $statusConfig = match($post['post_status']) {
                        'Published' => ['bg' => 'bg-green-100/90', 'text' => 'text-green-700'],
                        'Draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
                        'Withdrawn' => ['bg' => 'bg-red-100/90', 'text' => 'text-red-700'],
                        default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700']
                    };
                    $updatedAt = $post['updated_at'] ?? null;
                    $createdAt = $post['created_at'] ?? null;
                    if ($updatedAt && $createdAt && $updatedAt !== $createdAt) {
                        $displayDate = \Carbon\Carbon::parse($updatedAt);
                        $dateLabel = 'Modified';
                    } elseif ($createdAt) {
                        $displayDate = \Carbon\Carbon::parse($createdAt);
                        $dateLabel = 'Created';
                    } else {
                        $displayDate = null;
                        $dateLabel = '';
                    }
                @endphp
                <div class="card p-3">
                    <div class="flex gap-4">
                        <div class="shrink-0">
                            @if(!empty($post['featured_image_uri']))
                                <img src="{{ $post['featured_image_preview_uri'] ?? $post['featured_image_uri'] }}"
                                     alt="{{ $post['post_title'] ?? 'Post' }}"
                                     class="h-14 w-14 shrink-0 rounded-md border border-[var(--border-soft)] object-cover"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Crect fill=%27%23e5e7eb%27 width=%27100%27 height=%27100%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 fill=%27%239ca3af%27 font-size=%2714%27 text-anchor=%27middle%27 dy=%27.3em%27%3ENo Image%3C/text%3E%3C/svg%3E'">
                            @else
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-md border border-[var(--border-soft)] bg-[var(--surface)]">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            @php
                                $mobileStatusBadgeClass = match($post['post_status']) {
                                    'Published' => 'badge-tint-green',
                                    'Draft' => 'badge-tint-yellow',
                                    'Withdrawn' => 'badge-tint-red',
                                    default => 'badge-tint-gray',
                                };
                            @endphp
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                @if($categoryName)
                                    <span class="badge-tint badge-tint-blue text-[11px]">
                                        {!! html_entity_decode($categoryName) !!}
                                    </span>
                                @endif
                                <span class="badge-tint {{ $mobileStatusBadgeClass }} text-[11px]">
                                    {{ $post['post_status'] }}
                                </span>
                                @if(!empty($post['is_featured']))
                                    <span class="badge-tint badge-tint-green text-[11px]">
                                        <svg viewBox="0 0 256 256" class="h-3.5 w-3.5 shrink-0" fill="currentColor" aria-hidden="true"><path d="M238.76 51.73A8 8 0 0 0 232 48H40a8 8 0 0 0-5.66 13.66L76.69 104l-42.35 42.34A8 8 0 0 0 40 160h133.62l-28.84 60.56a8 8 0 1 0 14.44 6.88l80-168a8 8 0 0 0-.46-7.71M181.23 144H59.31l34.35-34.34a8 8 0 0 0 0-11.32L59.31 64h160Z"/></svg>
                                        <span>Featured</span>
                                    </span>
                                @endif
                                @if($authorName !== '')
                                    <span class="inline-flex items-center gap-1 text-[11px] text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-3.5 w-3.5 shrink-0"><path fill="currentColor" d="M10.56 11.87a3.75 3.75 0 1 1 3.75-3.75a3.76 3.76 0 0 1-3.75 3.75m0-6a2.25 2.25 0 1 0 2.25 2.25a2.25 2.25 0 0 0-2.25-2.25m-7 13a.75.75 0 0 1-.75-.75c0-4.75 5.43-4.75 7.75-4.75c.72 0 1.36 0 1.94.07a.75.75 0 0 1 .69.8a.76.76 0 0 1-.81.69c-.54 0-1.14-.06-1.82-.06c-5.18 0-6.25 1.3-6.25 3.25a.74.74 0 0 1-.75.75m9.11.76a.75.75 0 0 1-.53-.22a.72.72 0 0 1-.22-.59l.16-1.92a.75.75 0 0 1 .21-.47l5.52-5.52a2.06 2.06 0 0 1 2.8 0a2 2 0 0 1 .58 1.44a1.86 1.86 0 0 1-.53 1.33l-5.52 5.52a.74.74 0 0 1-.46.22l-1.94.18Zm.88-2.34l-.06.76l.78-.07l5.33-5.33a.4.4 0 0 0 .09-.27a.6.6 0 0 0-.14-.38a.57.57 0 0 0-.68 0Z"/></svg>
                                        <span>{{ $authorName }}</span>
                                    </span>
                                @endif
                            </div>
                            <button type="button"
                                    onclick="quickView({{ $post['id'] }})"
                                    class="block w-full text-left text-sm font-semibold leading-5 text-gray-900 transition-colors hover:text-[var(--accent)]"
                                    style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                {!! html_entity_decode($post['post_title'] ?? 'Untitled') !!}
                            </button>
                            @if(!empty($post['post_excerpt']))
                                <p class="mt-1 text-xs leading-5 text-gray-500"
                                   style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    {!! strip_tags(html_entity_decode($post['post_excerpt'])) !!}
                                </p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500">
                                <span>{{ $post['published_at'] ? \Carbon\Carbon::parse($post['published_at'])->format('M d, Y') : 'Unpublished' }}</span>
                                @if($displayDate)
                                    <span>{{ $dateLabel }}: {{ $displayDate->format('M d, Y') }}</span>
                                @endif
                            </div>
                            @if(!empty($post['tags']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach(array_slice(explode(',', $post['tags']), 0, 3) as $tag)
                                        <span class="px-2 inline-flex text-[11px] leading-5 rounded-lg bg-[var(--surface)] text-[var(--text-soft)] border border-[var(--border-soft)]">
                                            {!! html_entity_decode(trim($tag)) !!}
                                        </span>
                                    @endforeach
                                    @if(count(explode(',', $post['tags'])) > 3)
                                        <span class="px-2 inline-flex text-[11px] leading-5 rounded-lg bg-[var(--surface)] text-[var(--text-soft)] border border-[var(--border-soft)]">
                                            +{{ count(explode(',', $post['tags'])) - 3 }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-end gap-2">
                        <button type="button"
                                onclick="quickView({{ $post['id'] }})"
                                class="btn-icon h-9 w-9"
                                title="View post"
                                aria-label="View post"
                                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #7c3aed;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                        </button>
                        <a href="{{ route('dashboard.web_curator.posts.edit', $post['id']) }}"
                           class="btn-icon h-9 w-9"
                           title="Edit post"
                           aria-label="Edit post"
                           style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4.5 w-4.5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                        </a>
                        <button type="button"
                                onclick="confirmDelete({{ $post['id'] }}, '{{ addslashes($post['post_title']) }}')"
                                class="btn-icon h-9 w-9"
                                title="Delete post"
                                aria-label="Delete post"
                                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626;">
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
                        <th class="w-24">Image</th>
                        <th class="w-[45%]">
                            <a href="{{ $sortUrl('post_title') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                <span>Title</span>
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'post_title' ? 'text-gray-700' : 'text-gray-400' }}">
                                    @if($sortIndicator('post_title') === 'up')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                    @elseif($sortIndicator('post_title') === 'down')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="w-[15%]">
                            <a href="{{ $sortUrl('published_at') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                Status
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center {{ $currentSort === 'published_at' ? 'text-gray-700' : 'text-gray-400' }}">
                                    @if($sortIndicator('published_at') === 'up')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 141.248V928a32 32 0 1 0 64 0V218.56l242.688 242.688A32 32 0 1 0 736 416L438.592 118.656A32 32 0 0 0 384 141.248"/></svg>
                                    @elseif($sortIndicator('published_at') === 'down')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M576 96v709.568L333.312 562.816A32 32 0 1 0 288 608l297.408 297.344A32 32 0 0 0 640 882.688V96a32 32 0 0 0-64 0"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" class="h-3.5 w-3.5" fill="currentColor"><path d="M384 96a32 32 0 0 1 64 0v786.752a32 32 0 0 1-54.592 22.656L95.936 608a32 32 0 0 1 0-45.312h.128a32 32 0 0 1 45.184 0L384 805.632zm192 45.248a32 32 0 0 1 54.592-22.592L928.064 416a32 32 0 0 1 0 45.312h-.128a32 32 0 0 1-45.184 0L640 218.496V928a32 32 0 1 1-64 0z"/></svg>
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="w-[15%]">
                            <a href="{{ $sortUrl('updated_at') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                Last Modified
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
                        <th class="w-40 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr>
                            <td class="!pl-6 !pr-2 !py-4 min-w-24">
                                @if(!empty($post['featured_image_uri']))
                                    <img src="{{ $post['featured_image_preview_uri'] ?? $post['featured_image_uri'] }}" 
                                         alt="{{ $post['post_title'] ?? 'Post' }}"
                                         class="w-16 h-16 shrink-0 object-cover rounded-md border border-[var(--border-soft)]"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Crect fill=%27%23e5e7eb%27 width=%27100%27 height=%27100%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 fill=%27%239ca3af%27 font-size=%2714%27 text-anchor=%27middle%27 dy=%27.3em%27%3ENo Image%3C/text%3E%3C/svg%3E'">
                                @else
                                    <div class="w-16 h-16 shrink-0 rounded-md border border-[var(--border-soft)] bg-[var(--surface)] flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="max-w-0 px-6 py-4">
                                @php
                                    $categoryName = '';
                                    if (isset($post['category'])) {
                                        if (is_array($post['category'])) {
                                            $categoryName = $post['category']['name'] ?? '';
                                        } elseif (is_object($post['category'])) {
                                            $categoryName = $post['category']->name ?? '';
                                        } else {
                                            $categoryName = $post['category'];
                                        }
                                    }
                                @endphp
                                @php
                                    $authorName = trim((string) html_entity_decode($post['author'] ?? ''));
                                @endphp
                                @php
                                    $desktopStatusBadgeClass = match($post['post_status']) {
                                        'Published' => 'badge-tint-green',
                                        'Draft' => 'badge-tint-yellow',
                                        'Withdrawn' => 'badge-tint-red',
                                        default => 'badge-tint-gray',
                                    };
                                @endphp
                                <div class="mb-1 flex flex-wrap items-center gap-2">
                                    @if($categoryName)
                                        <span class="badge-tint badge-tint-blue">
                                            {!! html_entity_decode($categoryName) !!}
                                        </span>
                                    @endif
                                    @if(!empty($post['is_featured']))
                                        <span class="badge-tint badge-tint-green">
                                            <svg viewBox="0 0 256 256" class="h-3.5 w-3.5 shrink-0" fill="currentColor" aria-hidden="true"><path d="M238.76 51.73A8 8 0 0 0 232 48H40a8 8 0 0 0-5.66 13.66L76.69 104l-42.35 42.34A8 8 0 0 0 40 160h133.62l-28.84 60.56a8 8 0 1 0 14.44 6.88l80-168a8 8 0 0 0-.46-7.71M181.23 144H59.31l34.35-34.34a8 8 0 0 0 0-11.32L59.31 64h160Z"/></svg>
                                            <span>Featured</span>
                                        </span>
                                    @endif
                                    @if($authorName !== '')
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-3.5 w-3.5 shrink-0"><path fill="currentColor" d="M10.56 11.87a3.75 3.75 0 1 1 3.75-3.75a3.76 3.76 0 0 1-3.75 3.75m0-6a2.25 2.25 0 1 0 2.25 2.25a2.25 2.25 0 0 0-2.25-2.25m-7 13a.75.75 0 0 1-.75-.75c0-4.75 5.43-4.75 7.75-4.75c.72 0 1.36 0 1.94.07a.75.75 0 0 1 .69.8a.76.76 0 0 1-.81.69c-.54 0-1.14-.06-1.82-.06c-5.18 0-6.25 1.3-6.25 3.25a.74.74 0 0 1-.75.75m9.11.76a.75.75 0 0 1-.53-.22a.72.72 0 0 1-.22-.59l.16-1.92a.75.75 0 0 1 .21-.47l5.52-5.52a2.06 2.06 0 0 1 2.8 0a2 2 0 0 1 .58 1.44a1.86 1.86 0 0 1-.53 1.33l-5.52 5.52a.74.74 0 0 1-.46.22l-1.94.18Zm.88-2.34l-.06.76l.78-.07l5.33-5.33a.4.4 0 0 0 .09-.27a.6.6 0 0 0-.14-.38a.57.57 0 0 0-.68 0Z"/></svg>
                                            <span>{{ $authorName }}</span>
                                        </span>
                                    @endif
                                </div>
                                <button type="button"
                                        onclick="quickView({{ $post['id'] }})"
                                        class="block w-full whitespace-normal break-words text-left text-sm font-semibold leading-6 text-gray-900 transition-colors hover:text-[var(--accent)] focus:outline-none focus:text-[var(--accent)]"
                                        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;"
                                        title="View {{ html_entity_decode($post['post_title'] ?? 'Untitled') }}">
                                    {!! html_entity_decode($post['post_title'] ?? 'Untitled') !!}
                                </button>
                                @if(!empty($post['post_excerpt']))
                                    <div class="mt-1 whitespace-normal break-words text-xs leading-5 text-gray-500"
                                         style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                                        {!! strip_tags(html_entity_decode($post['post_excerpt'])) !!}
                                    </div>
                                @endif
                                @if(!empty($post['tags']))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach(array_slice(explode(',', $post['tags']), 0, 3) as $tag)
                                            <span class="px-2 inline-flex text-xs leading-5 rounded-lg bg-[var(--surface)] text-[var(--text-soft)] border border-[var(--border-soft)]">
                                                {!! html_entity_decode(trim($tag)) !!}
                                            </span>
                                        @endforeach
                                        @if(count(explode(',', $post['tags'])) > 3)
                                            <span class="px-2 inline-flex text-xs leading-5 rounded-lg bg-[var(--surface)] text-[var(--text-soft)] border border-[var(--border-soft)]">
                                                +{{ count(explode(',', $post['tags'])) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2">
                                    <span class="badge-tint {{ $desktopStatusBadgeClass }} w-fit text-sm">
                                        {{ $post['post_status'] }}
                                    </span>
                                    <div class="text-sm text-gray-500">
                                        {{ $post['published_at'] ? \Carbon\Carbon::parse($post['published_at'])->format('M d, Y') : '-' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @php
                                    $updatedAt = $post['updated_at'] ?? null;
                                    $createdAt = $post['created_at'] ?? null;
                                    
                                    // Show updated if different from created, otherwise show created
                                    if ($updatedAt && $createdAt && $updatedAt !== $createdAt) {
                                        $displayDate = \Carbon\Carbon::parse($updatedAt);
                                        $label = 'Modified';
                                    } elseif ($createdAt) {
                                        $displayDate = \Carbon\Carbon::parse($createdAt);
                                        $label = 'Created';
                                    } else {
                                        $displayDate = null;
                                        $label = '';
                                    }
                                @endphp
                                @if($displayDate)
                                    <span class="text-xs text-gray-400">{{ $label }}:</span><br>
                                    {{ $displayDate->format('M d, Y') }}
                                    <br><span class="text-xs text-gray-400">{{ $displayDate->format('g:i A') }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            onclick="quickView({{ $post['id'] }})" 
                                            class="btn-icon"
                                            title="View post"
                                            aria-label="View post"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #7c3aed; outline: none; -webkit-appearance: none; appearance: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                                    </button>
                                    <a href="{{ route('dashboard.web_curator.posts.edit', $post['id']) }}" 
                                       class="btn-icon"
                                       title="Edit post"
                                       aria-label="Edit post"
                                       style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent); outline: none; -webkit-appearance: none; appearance: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                    </a>
                                    <button type="button"
                                            onclick="confirmDelete({{ $post['id'] }}, '{{ addslashes($post['post_title']) }}')" 
                                            class="btn-icon"
                                            title="Delete post"
                                            aria-label="Delete post"
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
@if ($posts->hasPages())
    <div class="mt-6">
        {{ $posts->appends(request()->except('page'))->links() }}
    </div>
@endif

</div>

{{-- Quick View Modal --}}
<div id="quickViewModal" class="hidden fixed inset-0 z-50 bg-[var(--overlay)] p-4 lg:p-8" x-data="{ activeTab: 'content' }">
    <div class="relative mx-auto flex h-full max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] shadow-[var(--shadow-raised)] lg:max-h-[calc(100vh-4rem)]">
        <div class="flex items-center justify-between p-4 lg:p-5 border-b border-[var(--border-soft)]">
            <h3 class="text-xl font-semibold text-[var(--text-strong)]" id="quickViewTitle">Post Details</h3>
            <div class="flex items-center gap-2">
                <a id="quickViewPreviewLink"
                   href="#"
                   target="_blank"
                   rel="noopener"
                   class="btn-base btn-secondary h-8 gap-2 px-3 text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"><path stroke-linejoin="round" d="M21 3h-6m6 0l-9 9m9-9v6"/><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></g></svg>
                    Preview
                </a>
                <button onclick="closeQuickView()" class="text-[var(--text-soft)] hover:text-[var(--text-strong)] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Tabs --}}
        <div class="flex overflow-x-auto border-b border-[var(--border-soft)]">
            <button @click="activeTab = 'content'" 
                    :class="activeTab === 'content' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Content
            </button>
            <button @click="activeTab = 'meta'" 
                    :class="activeTab === 'meta' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Metadata
            </button>
            <button @click="activeTab = 'entities'" 
                    :class="activeTab === 'entities' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Tagged Entities
            </button>
            <button @click="activeTab = 'details'" 
                    :class="activeTab === 'details' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors lg:px-6 lg:py-3">
                Details
            </button>
        </div>
        
        {{-- Tab Content --}}
        <div class="flex-1 min-h-0 overflow-y-auto p-4 lg:p-5">
            {{-- Content Tab --}}
            <div x-show="activeTab === 'content'">
                <div class="mb-4" id="quickViewImage"></div>
                <div class="post-content-preview wc-rendered-content" id="quickViewContent"></div>
            </div>
            
            {{-- Metadata Tab --}}
            <div x-show="activeTab === 'meta'" style="display: none;">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2" id="quickViewMeta"></div>
            </div>
            
            {{-- Tagged Entities Tab --}}
            <div x-show="activeTab === 'entities'" style="display: none;">
                <div id="quickViewEntities"></div>
            </div>
            
            {{-- Details Tab --}}
            <div x-show="activeTab === 'details'" style="display: none;">
                <div class="space-y-4" id="quickViewDetails"></div>
            </div>
        </div>
        
        <div class="flex items-center justify-between gap-2 border-t border-[var(--border-soft)] px-4 py-3 lg:px-5 lg:py-3">
            <div>
                {{-- Publish button (only shown for Draft posts) --}}
                <button id="quickViewPublishBtn" 
                        onclick="publishPost()" 
                        class="btn btn-primary hidden items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Publish Post
                </button>
            </div>
            <div class="flex gap-2">
                <a id="quickViewEditLink" href="#" class="btn btn-primary">
                    Edit Post
                </a>
                <button onclick="closeQuickView()" class="btn btn-outline">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Post</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete "<span id="deletePostTitle" class="font-semibold"></span>"? This action cannot be undone.
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

<script>
// Store posts data for quick view
const postsData = @json($posts->items());
const taggedEntityLookup = @json($taggedEntityLookup);

// Get access token from PHP session
const ACCESS_TOKEN = '{{ session("ims_access_token") }}';
const API_BASE_URL = '{{ config("web-api.api_base_url") }}';
const API_WEB_BASE_URL = API_BASE_URL.replace(/\/api\/?$/, '').replace(/\/$/, '');

function quickView(postId) {
    const post = postsData.find(p => p.id === postId);
    if (!post) {
        alert('Post data not found');
        return;
    }

    // Store current post ID for later use (publish, etc.)
    window.currentViewingPostId = postId;
    window.currentViewingPost = post;
    const quickViewPreviewLink = document.getElementById('quickViewPreviewLink');
    if (quickViewPreviewLink) {
        quickViewPreviewLink.href = @json(route('dashboard.web_curator.posts.preview', ':id')).replace(':id', postId);
    }
    
    // Helper function to decode HTML entities
    function decodeHtml(html) {
        const txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    }

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const normalizeAttachmentUrl = (value) => {
        let url = String(value || '').trim();
        if (!url) {
            return '';
        }

        const escapedOrigin = API_WEB_BASE_URL.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        url = url.replace(new RegExp(`^${escapedOrigin}(?=https?:?\\/?\\/?)`, 'i'), '');

        const absoluteMatch = url.match(/^(https?):?(\/\/)?(.*)$/i);
        if (absoluteMatch) {
            url = `${absoluteMatch[1].toLowerCase()}://${absoluteMatch[3].replace(/^\/+/, '')}`;
        }

        const duplicatedOriginMatch = url.match(/^(https?:\/\/[^/]+)(https?:\/\/.+)$/i);
        if (duplicatedOriginMatch) {
            url = duplicatedOriginMatch[2];
        }

        return url;
    };

    const formatAttachmentUrl = (attachment) => {
        const directUrl = normalizeAttachmentUrl(
            attachment?.url || attachment?.full_url || attachment?.attachment_url || ''
        );
        if (directUrl) {
            return directUrl;
        }

        const uri = normalizeAttachmentUrl(attachment?.attachment_uri || '');
        if (!uri) {
            return '';
        }

        if (/^https?:\/\//i.test(uri)) {
            return uri;
        }

        return `${API_WEB_BASE_URL}/storage/${uri.replace(/^\/+/, '')}`;
    };

    const formatAttachmentSize = (attachment) => {
        if (attachment?.formatted_size) {
            return attachment.formatted_size;
        }

        const size = Number(attachment?.file_size || 0);
        if (!size) {
            return '';
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        let value = size;
        let unit = 0;

        while (value >= 1024 && unit < units.length - 1) {
            value /= 1024;
            unit += 1;
        }

        return `${Math.round(value * 100) / 100} ${units[unit]}`;
    };

    const renderAttachmentsSummary = (attachments) => {
        if (!attachments.length) {
            return '';
        }

        const itemsMarkup = attachments.map((attachment) => {
            const title = escapeHtml(
                attachment?.attachment_title ||
                attachment?.file_name ||
                attachment?.original_name ||
                'Untitled attachment'
            );
            const type = escapeHtml(attachment?.attachment_type || 'file');
            const size = formatAttachmentSize(attachment);
            const description = String(attachment?.description || '').trim();
            const url = formatAttachmentUrl(attachment);

            return `
                <div class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-[var(--text-strong)]">${title}</div>
                            <div class="mt-1 text-xs text-[var(--text-soft)]">${type}${size ? ` · ${escapeHtml(size)}` : ''}</div>
                            ${description ? `<div class="mt-1 text-xs text-[var(--text)]">${escapeHtml(description)}</div>` : ''}
                        </div>
                        ${url ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="shrink-0 text-xs font-semibold text-[var(--accent)] hover:underline">Open</a>` : ''}
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="md:col-span-2 space-y-3">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Attachments</div>
                <div class="grid gap-3">${itemsMarkup}</div>
            </div>
        `;
    };
    
    // Set title (decode HTML entities)
    const decodedTitle = decodeHtml(post.post_title || 'Untitled');
    document.getElementById('quickViewTitle').textContent = decodedTitle;
    
    // Set edit link
    document.getElementById('quickViewEditLink').href = `{{ route('dashboard.web_curator.posts.index') }}/${postId}/edit`;
    
    // Show/Hide Publish button based on post status
    const publishBtn = document.getElementById('quickViewPublishBtn');
    if (post.post_status === 'Draft') {
        publishBtn.classList.remove('hidden');
    } else {
        publishBtn.classList.add('hidden');
    }
    
    // Set image (use decoded title in alt text)
    const imageHtml = post.featured_image_uri 
        ? `<img src="${post.featured_image_preview_uri || post.featured_image_uri}" alt="${decodedTitle}" class="w-full h-64 object-cover rounded-lg mb-4">`
        : '';
    document.getElementById('quickViewImage').innerHTML = imageHtml;
    
    // Set content
    const excerpt = post.post_excerpt ? `<div class="text-gray-600 italic mb-4">${post.post_excerpt}</div>` : '';
    const quickViewContent = document.getElementById('quickViewContent');
    quickViewContent.innerHTML = excerpt + (post.post_content || '<p class="text-gray-500">No content available</p>');
    window.WebCuratorRenderedContent?.mount?.(quickViewContent);
    
    // Set metadata - Handle multiple metadata formats
    console.log('Post data:', post);
    let metaData = {};
    
    // Check for organized_metadata first (added by API)
    if (post.organized_metadata) {
        const organized = post.organized_metadata;
        if (organized.required && typeof organized.required === 'object') {
            Object.assign(metaData, organized.required);
        }
        if (organized.extra && typeof organized.extra === 'object') {
            Object.assign(metaData, organized.extra);
        }
    }
    
    // Check for metadata relation (array of {meta_key, meta_value} objects)
    if (post.metadata && Array.isArray(post.metadata) && post.metadata.length > 0) {
        post.metadata.forEach(item => {
            if (item.meta_key && item.meta_value) {
                metaData[item.meta_key] = item.meta_value;
            }
        });
    }
    
    // Fallback to direct meta field
    if (Object.keys(metaData).length === 0 && post.meta && typeof post.meta === 'object') {
        metaData = post.meta;
    }
    
    console.log('Extracted metadata:', metaData);
    let metaHtml = '';
    
    if (Object.keys(metaData).length > 0) {
        Object.entries(metaData).forEach(([key, value]) => {
            // Skip empty values
            if (value === null || value === undefined || value === '') return;
            
            const displayKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            const displayValue = typeof value === 'object' ? JSON.stringify(value) : value;
            metaHtml += `
                <div class="border-b pb-2">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">${displayKey}</div>
                    <div class="text-sm text-gray-900 mt-1">${displayValue}</div>
                </div>
            `;
        });
    }
    
    if (!metaHtml) {
        metaHtml = '<p class="text-gray-500 col-span-2">No metadata available</p>';
    }

    const attachments = Array.isArray(post.attachments) ? post.attachments : [];
    metaHtml += renderAttachmentsSummary(attachments);
    
    document.getElementById('quickViewMeta').innerHTML = metaHtml;
    
    // Set tagged entities
    const entities = post.tagged_entities || [];
    let entitiesHtml = '';
    
    if (entities.length > 0) {
        entitiesHtml = '<div class="space-y-3">';
        entities.forEach(entity => {
            const entityId = typeof entity === 'object'
                ? (
                    entity.entity_id ||
                    entity.tagged_entity_id ||
                    entity.taggable_entity_id ||
                    entity.related_entity_id ||
                    entity.entity?.id ||
                    entity.entity?.entity_id ||
                    entity.pivot?.entity_id ||
                    entity.id ||
                    0
                )
                : 0;
            const entityLookup = entityId ? taggedEntityLookup[String(entityId)] || taggedEntityLookup[entityId] : null;
            const entityName = typeof entity === 'object'
                ? (
                    entity.name ||
                    entity.entity_name ||
                    entity.full_name ||
                    entity.short_name ||
                    entity.display_name ||
                    entity.entity?.cached_data?.full_name ||
                    entity.entity?.cached_data?.name ||
                    entity.entity?.cached_data?.short_name ||
                    entity.entity?.cached_data?.display_name ||
                    entity.entity?.display_name ||
                    entity.entity?.name ||
                    entity.entity?.entity_name ||
                    entityLookup?.display_name ||
                    'Unknown'
                )
                : entity;
            const entityType = typeof entity === 'object'
                ? (entity.entity_type || entity.entity?.type_name || entityLookup?.entity_type || 'Tagged Entity')
                : 'Tagged Entity';
            const entityStatus = entity.pivot?.status || entity.status || 'N/A';
            const statusBadge = entityStatus === 'active' || entityStatus === 'Active' 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800';
            
            entitiesHtml += `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">${entityName}</div>
                            <div class="text-xs text-gray-500">${entityType}</div>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${statusBadge}">${entityStatus}</span>
                </div>
            `;
        });
        entitiesHtml += '</div>';
    } else {
        entitiesHtml = '<p class="text-gray-500 text-center py-8">No tagged entities</p>';
    }
    
    document.getElementById('quickViewEntities').innerHTML = entitiesHtml;
    
    // Set details
    const category = typeof post.category === 'object' ? (post.category?.name || '-') : (post.category || '-');
    const attachmentDetails = attachments.length
        ? `
            <div class="py-2 border-b">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-medium text-gray-700">Attachments:</span>
                    <span class="text-gray-900">${attachments.length}</span>
                </div>
                <div class="mt-3 space-y-2">
                    ${attachments.map((attachment) => {
                        const title = escapeHtml(
                            attachment?.attachment_title ||
                            attachment?.file_name ||
                            attachment?.original_name ||
                            'Untitled attachment'
                        );
                        const type = escapeHtml(attachment?.attachment_type || 'file');
                        const size = formatAttachmentSize(attachment);
                        const url = formatAttachmentUrl(attachment);

                        return `
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-[var(--border-soft)] bg-[var(--surface-raised)] px-3 py-2">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-gray-900">${title}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">${type}${size ? ` · ${escapeHtml(size)}` : ''}</div>
                                </div>
                                ${url ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="shrink-0 text-xs font-semibold text-[var(--accent)] hover:underline">Open</a>` : ''}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `
        : '';

    const detailsHtml = `
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Status:</span>
            <span class="px-2 py-1 text-xs rounded-full ${post.post_status === 'Published' ? 'bg-green-100 text-green-800' : post.post_status === 'Draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800'}">${post.post_status}</span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Category:</span>
            <span class="text-gray-900">${category}</span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Author:</span>
            <span class="text-gray-900">${post.author || '-'}</span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Published:</span>
            <span class="text-gray-900">${post.published_at ? new Date(post.published_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'}</span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Created:</span>
            <span class="text-gray-900">${post.created_at ? new Date(post.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'}</span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Last Modified:</span>
            <span class="text-gray-900">${post.updated_at ? new Date(post.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'}</span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-700">Featured:</span>
            <span class="text-gray-900">${post.is_featured ? 'Yes' : 'No'}</span>
        </div>
        <div class="flex justify-between py-2">
            <span class="font-medium text-gray-700">Tags:</span>
            <span class="text-gray-900">${post.tags || '-'}</span>
        </div>
        ${attachmentDetails}
    `;
    document.getElementById('quickViewDetails').innerHTML = detailsHtml;
    
    // Show modal
    document.getElementById('quickViewModal').classList.remove('hidden');
}

function closeQuickView() {
    document.getElementById('quickViewModal').classList.add('hidden');
}

async function publishPost() {
    if (!window.currentViewingPostId || !window.currentViewingPost) {
        alert('No post selected');
        return;
    }
    
    const post = window.currentViewingPost;
    const postTitle = post.post_title || 'this post';
    
    // Ask for confirmation
    if (!confirm(`Are you sure you want to publish "${postTitle}"?\n\nThis will make the post visible to the public.`)) {
        return;
    }
    
    // Disable the publish button to prevent double-clicks
    const publishBtn = document.getElementById('quickViewPublishBtn');
    const originalHtml = publishBtn.innerHTML;
    publishBtn.disabled = true;
    publishBtn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Publishing...';
    
    try {
        // Check for access token
        if (!ACCESS_TOKEN) {
            throw new Error('Authentication token not found. Please refresh the page and try again.');
        }
        
        const endpoint = `${API_BASE_URL}/posts/${window.currentViewingPostId}/update-status`;
        
        console.log('Publishing post:', {
            postId: window.currentViewingPostId,
            endpoint: endpoint,
            hasToken: !!ACCESS_TOKEN
        });
        
        // Make simple API request to update post status
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + ACCESS_TOKEN
            },
            body: JSON.stringify({
                post_status: 'Published'
            })
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorData = await response.json();
            console.error('API Error:', errorData);
            throw new Error(errorData.message || 'Failed to publish post');
        }
        
        const result = await response.json();
        console.log('Success:', result);
        
        // Success - update the UI
        alert('Post published successfully!');
        
        // Reload the page to reflect changes in the table
        window.location.reload();
        
    } catch (error) {
        console.error('Error publishing post:', error);
        alert('Failed to publish post: ' + error.message);
        
        // Re-enable the button
        publishBtn.disabled = false;
        publishBtn.innerHTML = originalHtml;
    }
}

function confirmDelete(id, title) {
    document.getElementById('deletePostTitle').textContent = title;
    document.getElementById('deleteForm').action = `{{ route('dashboard.web_curator.posts.index') }}/${id}`;
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

// Debug sorting
window.addEventListener('DOMContentLoaded', function() {
    console.log('Posts index page loaded');
    console.log('Current sort:', '{{ request('sort', 'updated_at') }}', '{{ request('direction', 'desc') }}');
});
</script>

<style>
/* Responsive filter grid */
@media (max-width: 767px) {
    .grid.grid-cols-1 {
        gap: 0.75rem;
    }
}

/* Post Content Preview Styling */
.post-content-preview {
    line-height: 1.75;
    color: #374151;
    font-size: 0.9375rem;
}

.post-content-preview h1 {
    font-size: 2.25em;
    font-weight: 800;
    line-height: 1.2;
    margin-top: 0.5em;
    margin-bottom: 0.5em;
    color: #111827;
}

.post-content-preview h2 {
    font-size: 1.875em;
    font-weight: 700;
    line-height: 1.3;
    margin-top: 1em;
    margin-bottom: 0.5em;
    color: #111827;
}

.post-content-preview h3 {
    font-size: 1.5em;
    font-weight: 600;
    line-height: 1.4;
    margin-top: 1em;
    margin-bottom: 0.5em;
    color: #1f2937;
}

.post-content-preview h4 {
    font-size: 1.25em;
    font-weight: 600;
    line-height: 1.5;
    margin-top: 0.75em;
    margin-bottom: 0.5em;
    color: #1f2937;
}

.post-content-preview h5 {
    font-size: 1.125em;
    font-weight: 600;
    line-height: 1.5;
    margin-top: 0.75em;
    margin-bottom: 0.5em;
    color: #374151;
}

.post-content-preview h6 {
    font-size: 1em;
    font-weight: 600;
    line-height: 1.5;
    margin-top: 0.75em;
    margin-bottom: 0.5em;
    color: #374151;
}

.post-content-preview p {
    margin-top: 1em;
    margin-bottom: 1em;
    line-height: 1.75;
}

.post-content-preview a {
    color: #2563eb;
    text-decoration: underline;
    font-weight: 500;
}

.post-content-preview a:hover {
    color: #1d4ed8;
}

.post-content-preview strong {
    font-weight: 700;
    color: #111827;
}

.post-content-preview em {
    font-style: italic;
}

.post-content-preview img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5em 0;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.post-content-preview ul,
.post-content-preview ol {
    margin-top: 1em;
    margin-bottom: 1em;
    padding-left: 1.5em;
}

.post-content-preview ul {
    list-style-type: disc;
}

.post-content-preview ol {
    list-style-type: decimal;
}

.post-content-preview li {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
    padding-left: 0.25em;
}

.post-content-preview ul ul,
.post-content-preview ol ul {
    list-style-type: circle;
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.post-content-preview ul ul ul,
.post-content-preview ol ul ul {
    list-style-type: square;
}

.post-content-preview blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1em;
    margin: 1.5em 0;
    font-style: italic;
    color: #6b7280;
}

.post-content-preview code {
    background-color: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: 'Courier New', Courier, monospace;
    color: #dc2626;
}

.post-content-preview pre {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1em;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1.5em 0;
}

.post-content-preview pre code {
    background-color: transparent;
    padding: 0;
    color: inherit;
    font-size: 0.875em;
}

.post-content-preview table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5em 0;
    font-size: 0.875em;
}

.post-content-preview th {
    background-color: #f3f4f6;
    font-weight: 600;
    text-align: left;
    padding: 0.75em;
    border-bottom: 2px solid #e5e7eb;
}

.post-content-preview td {
    padding: 0.75em;
    border-bottom: 1px solid #e5e7eb;
}

.post-content-preview hr {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 2em 0;
}

/* Button Styles in Post Content */
.post-content-preview .gjs-button,
.post-content-preview .button,
.post-content-preview a.button,
.post-content-preview button.primary {
    display: inline-block;
    padding: 0.625rem 1.25rem;
    font-size: 0.9375rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
    cursor: pointer;
    border: none;
    line-height: 1.5;
}

.post-content-preview .gjs-button:hover,
.post-content-preview .button:hover,
.post-content-preview a.button:hover,
.post-content-preview button.primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Primary Button */
.post-content-preview .btn-primary,
.post-content-preview button.primary {
    background-color: #2563eb;
    color: #ffffff;
}

.post-content-preview .btn-primary:hover,
.post-content-preview button.primary:hover {
    background-color: #1d4ed8;
}

/* Secondary Button */
.post-content-preview .btn-secondary,
.post-content-preview button.secondary {
    background-color: #6b7280;
    color: #ffffff;
}

.post-content-preview .btn-secondary:hover,
.post-content-preview button.secondary:hover {
    background-color: #4b5563;
}

/* Success Button */
.post-content-preview .btn-success,
.post-content-preview button.success {
    background-color: #10b981;
    color: #ffffff;
}

.post-content-preview .btn-success:hover,
.post-content-preview button.success:hover {
    background-color: #059669;
}

/* Outline Button */
.post-content-preview .btn-outline,
.post-content-preview button.outline {
    background-color: transparent;
    color: #2563eb;
    border: 2px solid #2563eb;
}

.post-content-preview .btn-outline:hover,
.post-content-preview button.outline:hover {
    background-color: #2563eb;
    color: #ffffff;
}

/* Additional Typography */
.post-content-preview mark {
    background-color: #fef3c7;
    color: #92400e;
    padding: 0.125rem 0.25rem;
    border-radius: 0.125rem;
}

.post-content-preview kbd {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: 'Courier New', Courier, monospace;
    border: 1px solid #374151;
}

.post-content-preview abbr {
    text-decoration: underline dotted;
    cursor: help;
}

.post-content-preview figure {
    margin: 1.5em 0;
}

.post-content-preview figcaption {
    margin-top: 0.5em;
    font-size: 0.875em;
    color: #6b7280;
    text-align: center;
    font-style: italic;
}

.post-content-preview dl {
    margin: 1em 0;
}

.post-content-preview dt {
    font-weight: 600;
    color: #111827;
    margin-top: 0.5em;
}

.post-content-preview dd {
    margin-left: 1.5em;
    margin-top: 0.25em;
    color: #374151;
}
</style>

<!-- Flatpickr JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

@endsection
