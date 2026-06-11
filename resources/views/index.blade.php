@extends('web_curator::layouts.default')

@section('title', 'Dashboard')


@php
    $user = session('ims_user', null);
    $display_name = $user['display_name'] ?? 'Curator';
    $entityWebBaseUrl = rtrim((string) config('web_curator.entity_web_base_url', ''), '/');
    $entitySlug = $entityInfo['slug'] ?? null;
    $entityWebsiteUrl = $entitySlug
        ? ($entityWebBaseUrl !== '' ? $entityWebBaseUrl . '/' . ltrim($entitySlug, '/') : '/' . ltrim($entitySlug, '/'))
        : null;
@endphp

@section('dashboard-content')
<div class="container-large" x-data="dashboardData()">
    <!-- Header with Entity Info -->
    <div class="flex flex-col items-start mb-6">
        <div class="mt-4">
            <p class="text-base text-gray-600">Welcome back,
                <span class="font-semibold text-gray-800">{{ $display_name }}</span>
            </p>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">
                @if($entityInfo)
                    {{ $entityInfo['entity_name'] ?? $entityInfo['name'] ?? $entityInfo['full_name'] ?? 'Unknown Entity' }}
                @else
                    Unknown Entity
                @endif
            </h2>
            <!-- <p class="text-sm text-gray-600 mt-1">
                @if($entityInfo && (isset($entityInfo['entity_type']) || isset($entityInfo['entity_category'])))
                    {{ isset($entityInfo['entity_type']) && $entityInfo['entity_type'] ? ucfirst($entityInfo['entity_type']) : '' }}
                    {{ isset($entityInfo['entity_category']) && $entityInfo['entity_category'] ? '• ' . $entityInfo['entity_category'] : '' }}
                @else
                    <span class="text-gray-400">Entity information not available</span>
                @endif
            </p> -->
        </div>        
    </div>

    <!-- Key Metrics - Compact Cards -->
    <div class="mt-4 grid grid-cols-2 gap-4 mb-6 md:grid-cols-4 xl:grid-cols-4">
        <!-- Total Posts -->
        <div class="card">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">Total Posts</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_posts'] }}</p>
                        <p class="text-xs text-green-600 mt-1">
                            <span class="font-medium">{{ $statistics['published_posts'] }}</span> published
                        </p>
                    </div>
                    <div class="rounded-lg p-2" style="background: color-mix(in srgb, rgb(59 130 246) 12%, var(--surface));">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pages -->
        <div class="card">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">Pages</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_pages'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Content pages</p>
                    </div>
                    <div class="rounded-lg p-2" style="background: color-mix(in srgb, rgb(34 197 94) 12%, var(--surface));">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="card">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">Categories</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_categories'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Content groups</p>
                    </div>
                    <div class="rounded-lg p-2" style="background: color-mix(in srgb, rgb(168 85 247) 12%, var(--surface));">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Snippets -->
        <div class="card">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">Snippets</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_snippets'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Reusable blocks</p>
                    </div>
                    <div class="rounded-lg p-2" style="background: color-mix(in srgb, rgb(245 158 11) 12%, var(--surface));">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="card">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">Media</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_media'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <span class="font-medium">{{ $statistics['image_media'] }}</span> images
                        </p>
                    </div>
                    <div class="rounded-lg p-2" style="background: color-mix(in srgb, rgb(14 165 233) 12%, var(--surface));">
                        <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">Galleries</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_galleries'] }}</p>
                        <p class="text-xs text-green-600 mt-1">
                            <span class="font-medium">{{ $statistics['published_galleries'] }}</span> published
                        </p>
                    </div>
                    <div class="rounded-lg p-2" style="background: color-mix(in srgb, rgb(16 185 129) 12%, var(--surface));">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Recent Posts - Takes 2 columns -->
        <div class="lg:col-span-2">
            <div class="card h-full">
                <div class="card-header flex items-center justify-between">
                    <h4 class="card-title">Recent Posts</h4>
                    <a href="{{ route('dashboard.web_curator.posts.index') }}" class="btn btn-outline btn-sm flex items-center gap-1.5">
                        <span>View all</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                            <path d="M17.073 12.5H5.5q-.213 0-.357-.143T5 12t.143-.357t.357-.143h11.573l-3.735-3.734q-.146-.147-.152-.345t.152-.363q.166-.166.357-.168t.357.162l4.383 4.383q.13.13.183.267t.053.298t-.053.298t-.183.268l-4.383 4.382q-.146.146-.347.153t-.367-.159q-.16-.165-.162-.354t.162-.354z"/>
                        </svg>
                    </a>
                </div>
                <div>
                    @forelse($recentPosts as $post)
                    <div class="p-4 transition-colors !last:border-b-0" style="border-bottom: 1px solid color-mix(in srgb, var(--border-soft) 56%, transparent);">
                        <div class="flex gap-4">
                            @if(!empty($post['featured_image_uri']))
                            <div class="flex-shrink-0">
                                <img src="{{ $post['featured_image_uri'] }}" alt="{{ $post['post_title'] ?? 'Post' }}" 
                                     class="w-16 h-16 object-cover rounded">
                            </div>
                            @else
                            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded" style="background: var(--surface-muted);">
                                <!-- <svg class="w-8 h-8" style="color: var(--text-soft);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg> -->
                                <svg class="h-8 w-8" viewBox="0 0 40 32" fill="rgba(100, 115, 142, 0.4)"><g><path d="M10.459 15.294c2.757 0 5-2.243 5-5s-2.243-5-5-5-5 2.243-5 5 2.243 5 5 5m0-9c2.206 0 4 1.794 4 4s-1.794 4-4 4-4-1.794-4-4 1.794-4 4-4"/><path d="M40 30.5v-29c0-.827-.673-1.5-1.5-1.5h-37C.673 0 0 .673 0 1.5v29c0 .827.673 1.5 1.5 1.5h37c.827 0 1.5-.673 1.5-1.5m-39 0v-29a.5.5 0 0 1 .5-.5h37a.5.5 0 0 1 .5.5v29a.5.5 0 0 1-.5.5h-37a.5.5 0 0 1-.5-.5"/><path d="M27.73 11.086a1.375 1.375 0 0 0-1.938.003L14.646 22.235a.37.37 0 0 1-.519.008l-2.583-2.429a1.37 1.37 0 0 0-1.912.03l-6.986 6.99a.5.5 0 0 0 .708.708l6.986-6.989a.373.373 0 0 1 .52-.008l2.583 2.428a1.365 1.365 0 0 0 1.911-.029l11.145-11.146a.37.37 0 0 1 .526-.001l9.622 9.566a.5.5 0 1 0 .705-.709z"/></g></svg>
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <h6 class="truncate text-sm font-semibold" style="color: var(--text-strong);">
                                        {{ $post['post_title'] ?? 'Untitled Post' }}
                                    </h6>
                                    <span class="flex-shrink-0 rounded-md px-2 py-0.5 text-xs {{ ($post['post_status'] ?? 'Draft') === 'Published' ? 'badge-tint badge-tint-green' : 'badge-tint badge-tint-gray' }}">
                                        {{ $post['post_status'] ?? 'Draft' }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-3 text-xs" style="color: var(--text-soft);">
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
                                    @if($categoryName)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        {{ $categoryName }}
                                    </span>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ isset($post['updated_at']) ? \Carbon\Carbon::parse($post['updated_at'])->diffForHumans() : 'Recently' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 flex items-start gap-2">
                                <a href="{{ route('dashboard.web_curator.posts.edit', $post['id'] ?? '#') }}" 
                                   class="text-gray-400 hover:text-blue-600 transition-colors"
                                   title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <p class="text-sm">No posts yet</p>
                        <a href="{{ route('dashboard.web_curator.posts.create') }}" class="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-flex items-center gap-1">
                            Create your first post
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor">
                                <path d="M17.073 12.5H5.5q-.213 0-.357-.143T5 12t.143-.357t.357-.143h11.573l-3.735-3.734q-.146-.147-.152-.345t.152-.363q.166-.166.357-.168t.357.162l4.383 4.383q.13.13.183.267t.053.298t-.053.298t-.183.268l-4.383 4.382q-.146.146-.347.153t-.367-.159q-.16-.165-.162-.354t.162-.354z"/>
                            </svg>
                        </a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Posts by Category Chart -->
        <div class="lg:col-span-1">
            <div class="card h-full">
                <div class="card-header">
                    <h4 class="card-title">Posts by Category</h4>
                </div>
                <div class="p-4">
                    @if(count($postsByCategory) > 0)
                    <div class="space-y-3">
                        @php
                            $total = array_sum($postsByCategory);
                            $categoryMap = collect($categories)->keyBy('id');
                            $sortedCategories = collect($postsByCategory)->sortDesc()->take(6);
                        @endphp
                        @foreach($sortedCategories as $categoryId => $count)
                            @php
                                $category = $categoryMap->get($categoryId);
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-amber-500', 'bg-pink-500', 'bg-indigo-500'];
                                $color = $colors[$loop->index % count($colors)];
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-700 truncate flex-1">{{ $category['name'] ?? 'Uncategorized' }}</span>
                                    <span class="text-gray-600 font-medium ml-2">{{ $count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="{{ $color }} h-2 rounded-full transition-all duration-300" 
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <p class="text-sm">No category data</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Content Status -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Quick Actions</h4>
            </div>
            <div class="">
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('dashboard.web_curator.posts.create') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(59 130 246) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(59 130 246) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">New Post</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.pages.create') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(34 197 94) 9%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(34 197 94) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">New Page</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.menus.index') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(168 85 247) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(168 85 247) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">Categories</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.menus.index') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(99 102 241) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(99 102 241) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">Menus</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.settings.index') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(100 116 139) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(100 116 139) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">Settings</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.media.index') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(14 165 233) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(14 165 233) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v1H3V7zm0 3h18v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">Media Library</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.media.index', ['tab' => 'galleries']) }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(16 185 129) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(16 185 129) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">Media</span>
                    </a>

                    <a href="{{ route('dashboard.web_curator.entity_profile.edit') }}"
                       class="flex items-center gap-3 rounded-xl p-3 transition-all group"
                       style="background: color-mix(in srgb, rgb(245 158 11) 10%, var(--surface)); color: var(--text);">
                        <div class="rounded-lg p-2 transition-colors"
                             style="background: color-mix(in srgb, rgb(245 158 11) 14%, var(--surface));">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium" style="color: var(--text-strong);">Profile</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Content Status Overview -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Content Status</h4>
            </div>
            <div class="p-4">
                <div class="space-y-4">
                    <!-- Published vs Draft Posts -->
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium" style="color: var(--text-strong);">Post Status</span>
                            <span style="color: var(--text-soft);">{{ $statistics['total_posts'] }} total</span>
                        </div>
                        <div class="flex gap-2 mb-2">
                            @php
                                $publishedPercentage = $statistics['total_posts'] > 0 
                                    ? round(($statistics['published_posts'] / $statistics['total_posts']) * 100, 1) 
                                    : 0;
                                $draftPercentage = $statistics['total_posts'] > 0 ? (100 - $publishedPercentage) : 0;
                            @endphp
                            <div class="h-2 flex-1 rounded-full" style="background: color-mix(in srgb, var(--border-soft) 28%, var(--surface-muted));">
                                <div class="bg-green-500 h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ $publishedPercentage }}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span style="color: var(--text-soft);">Published: {{ $statistics['published_posts'] }} ({{ $publishedPercentage }}%)</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full" style="background: color-mix(in srgb, var(--border-soft) 55%, var(--surface-muted));"></span>
                                <span style="color: var(--text-soft);">Draft: {{ $statistics['draft_posts'] }} ({{ $draftPercentage }}%)</span>
                            </span>
                        </div>
                    </div>

                    <!-- <div class="pt-4" style="border-top: 1px solid color-mix(in srgb, var(--border-soft) 56%, transparent);">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium" style="color: var(--text-strong);">Gallery Status</span>
                            <span style="color: var(--text-soft);">{{ $statistics['total_galleries'] }} total</span>
                        </div>
                        @php
                            $publishedGalleryPercentage = $statistics['total_galleries'] > 0
                                ? round(($statistics['published_galleries'] / $statistics['total_galleries']) * 100, 1)
                                : 0;
                            $draftGalleryPercentage = $statistics['total_galleries'] > 0
                                ? round(($statistics['draft_galleries'] / $statistics['total_galleries']) * 100, 1)
                                : 0;
                        @endphp
                        <div class="mb-2 flex gap-2">
                            <div class="h-2 flex-1 overflow-hidden rounded-full" style="background: color-mix(in srgb, var(--border-soft) 28%, var(--surface-muted));">
                                <div class="h-2 rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $publishedGalleryPercentage }}%"></div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span style="color: var(--text-soft);">Published: {{ $statistics['published_galleries'] }} ({{ $publishedGalleryPercentage }}%)</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                <span style="color: var(--text-soft);">Draft: {{ $statistics['draft_galleries'] }} ({{ $draftGalleryPercentage }}%)</span>
                            </span>
                        </div>
                    </div> -->

                    <div class="pt-4" style="border-top: 1px solid color-mix(in srgb, var(--border-soft) 56%, transparent);">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg p-3 text-center" style="background: color-mix(in srgb, rgb(59 130 246) 10%, var(--surface));">
                                <p class="text-2xl font-bold text-blue-700">{{ $statistics['total_pages'] }}</p>
                                <p class="mt-1 text-xs text-blue-600">Total Pages</p>
                            </div>
                            <div class="rounded-lg p-3 text-center" style="background: color-mix(in srgb, rgb(168 85 247) 10%, var(--surface));">
                                <p class="text-2xl font-bold text-purple-700">{{ $statistics['total_categories'] }}</p>
                                <p class="mt-1 text-xs text-purple-600">Categories</p>
                            </div>
                            <div class="rounded-lg p-3 text-center" style="background: color-mix(in srgb, rgb(14 165 233) 10%, var(--surface));">
                                <p class="text-2xl font-bold text-sky-700">{{ $statistics['total_media'] }}</p>
                                <p class="mt-1 text-xs text-sky-600">Media</p>
                            </div>
                            <div class="rounded-lg p-3 text-center" style="background: color-mix(in srgb, rgb(16 185 129) 10%, var(--surface));">
                                <p class="text-2xl font-bold text-emerald-700">{{ $statistics['total_galleries'] }}</p>
                                <p class="mt-1 text-xs text-emerald-600">Galleries</p>
                            </div>
                        </div>
                    </div>

                    @if($entityWebsiteUrl)
                    <div class="pt-4" style="border-top: 1px solid color-mix(in srgb, var(--border-soft) 56%, transparent);">
                        <p class="mb-2 text-xs uppercase tracking-wider" style="color: var(--text-soft);">Website URL</p>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 rounded-xl border px-3 py-2 text-xs"
                                  style="border-color: var(--border); background: var(--surface-raised); color: var(--text-strong);">
                                {{ $entityWebsiteUrl }}
                            </code>
                            <button @click="copyToClipboard('{{ addslashes($entityWebsiteUrl) }}')" 
                                    class="btn-icon h-9 w-9"
                                    style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text-soft);"
                                    title="Copy URL"
                                    aria-label="Copy URL">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <a href="{{ $entityWebsiteUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn-icon h-9 w-9"
                               style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);"
                               title="Visit website"
                               aria-label="Visit website">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-4 w-4"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"><path stroke-linejoin="round" d="M21 3h-6m6 0l-9 9m9-9v6"/><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></g></svg>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- <div class="card">
            <div class="card-header flex items-center justify-between">
                <h4 class="card-title">Recent Galleries</h4>
                <a href="{{ route('dashboard.web_curator.media.index', ['tab' => 'galleries']) }}" class="btn btn-outline btn-sm">View all</a>
            </div>
            <div>
                @forelse($recentGalleries as $gallery)
                    @php
                        $coverUrl = data_get($gallery, 'cover_media_item.full_url') ?: data_get($gallery, 'cover_media_item.public_url');
                        $status = data_get($gallery, 'gallery_status', 'Draft');
                        $statusClass = match($status) {
                            'Published' => 'badge-tint-green',
                            'Draft' => 'badge-tint-yellow',
                            'Withdrawn' => 'badge-tint-red',
                            default => 'badge-tint-gray',
                        };
                    @endphp
                    <div class="p-4 transition-colors !last:border-b-0" style="border-bottom: 1px solid color-mix(in srgb, var(--border-soft) 56%, transparent);">
                        <div class="flex gap-4">
                            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl" style="background: var(--surface-muted);">
                                @if($coverUrl)
                                    <img src="{{ $coverUrl }}" alt="{{ data_get($gallery, 'title') }}" class="h-full w-full object-cover">
                                @else
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <h6 class="truncate text-sm font-semibold" style="color: var(--text-strong);">
                                        {{ data_get($gallery, 'title', 'Untitled Gallery') }}
                                    </h6>
                                    <span class="flex-shrink-0 rounded-md px-2 py-0.5 text-xs badge-tint {{ $statusClass }}">{{ $status }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-3 text-xs" style="color: var(--text-soft);">
                                    <span>{{ number_format((int) data_get($gallery, 'items_count', 0)) }} item(s)</span>
                                    <span>{{ data_get($gallery, 'updated_at') ? \Carbon\Carbon::parse(data_get($gallery, 'updated_at'))->diffForHumans() : 'Recently' }}</span>
                                </div>
                                @if(data_get($gallery, 'excerpt'))
                                    <p class="mt-2 text-sm text-gray-600" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ data_get($gallery, 'excerpt') }}
                                    </p>
                                @endif
                                <div class="mt-3">
                                    <a href="{{ route('dashboard.web_curator.media.index', ['tab' => 'galleries', 'gallery_id' => data_get($gallery, 'id')]) }}"
                                       class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:text-emerald-800">
                                        Edit gallery
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                                            <path d="M17.073 12.5H5.5q-.213 0-.357-.143T5 12t.143-.357t.357-.143h11.573l-3.735-3.734q-.146-.147-.152-.345t.152-.363q.166-.166.357-.168t.357.162l4.383 4.383q.13.13.183.267t.053.298t-.053.298t-.183.268l-4.383 4.382q-.146.146-.347.153t-.367-.159q-.16-.165-.162-.354t.162-.354z"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">No galleries yet</p>
                        <a href="{{ route('dashboard.web_curator.media.index', ['tab' => 'galleries']) }}" class="mt-2 inline-flex items-center gap-1 text-sm text-emerald-700 hover:text-emerald-800">
                            Open media
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor">
                                <path d="M17.073 12.5H5.5q-.213 0-.357-.143T5 12t.143-.357t.357-.143h11.573l-3.735-3.734q-.146-.147-.152-.345t.152-.363q.166-.166.357-.168t.357.162l4.383 4.383q.13.13.183.267t.053.298t-.053.298t-.183.268l-4.383 4.382q-.146.146-.347.153t-.367-.159q-.16-.165-.162-.354t.162-.354z"/>
                            </svg>
                        </a>
                    </div>
                @endforelse
            </div>
        </div> -->
    </div>
</div>

<script>
function dashboardData() {
    return {
        copyToClipboard(text) {
            const onSuccess = () => {
                if (window.toastNotifier?.show) {
                    window.toastNotifier.show({
                        message: 'Website URL copied to clipboard.',
                        type: 'success',
                        duration: 2400,
                    });
                    return;
                }

                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-lg z-50';
                notification.style.background = '#16a34a';
                notification.textContent = 'Website URL copied to clipboard.';
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 2200);
            };

            const onError = () => {
                if (window.toastNotifier?.show) {
                    window.toastNotifier.show({
                        message: 'Failed to copy website URL.',
                        type: 'error',
                        duration: 2800,
                    });
                    return;
                }
                window.alert('Failed to copy website URL.');
            };

            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(text).then(onSuccess).catch(onError);
                return;
            }

            try {
                const helper = document.createElement('textarea');
                helper.value = text;
                helper.setAttribute('readonly', '');
                helper.style.position = 'absolute';
                helper.style.left = '-9999px';
                document.body.appendChild(helper);
                helper.select();
                const copied = document.execCommand('copy');
                document.body.removeChild(helper);
                copied ? onSuccess() : onError();
            } catch (error) {
                onError();
            }
        }
    }
}
</script>
@endsection
