@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="h-full">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Posts', 'url' => route('dashboard.web_curator.posts.index')],
            ['label' => 'Tagged Posts'],
        ]" />
        <h2 class="page-title">Tagged Posts</h2>
        <p class="text-sm text-gray-600 mt-1">
            Review posts that tagged your entity. Approved posts can also appear in your public feed.
        </p>
    </div>

    @php
        $currentStatus = request('status');
        $statusTabs = [
            'All' => null,
            'Pending' => 'Pending',
            'Approved' => 'Approved',
            'Denied' => 'Denied',
            'Withdrawn' => 'Withdrawn',
        ];
        $entityOptions = collect([
            ['value' => '', 'label' => 'All originating entities'],
        ])->merge(
            collect($entities ?? [])->map(function ($entity) {
                $entity = is_array($entity) ? (object) $entity : $entity;
                return [
                    'value' => (string) ($entity->id ?? ''),
                    'label' => (string) ($entity->display_name ?? $entity->entity_name ?? 'Unknown Entity'),
                ];
            })
        )->values()->all();
        $dateRangeOptions = [
            ['value' => '1m', 'label' => 'Last 1 Month'],
            ['value' => '3m', 'label' => 'Last 3 Months'],
            ['value' => '1y', 'label' => 'Last Year'],
            ['value' => 'all', 'label' => 'All Time'],
        ];
        $taggedPostsData = method_exists($taggedPosts, 'items')
            ? $taggedPosts->items()
            : collect($taggedPosts)->values()->all();
    @endphp

    <div class="card mb-6">
        <div class="card-header">
            <!-- <div>
                <h3 class="card-title">Filter Tagged Posts</h3>
                <p class="text-sm text-gray-600 mt-1">Previously denied or withdrawn posts remain available here for later approval.</p>
            </div> -->

            <form method="GET" action="{{ route('dashboard.web_curator.posts.tagged') }}" class="grid w-full gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(15rem,18rem)_minmax(12rem,14rem)_auto]">
                @if($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Search title, category, author, owner entity, or tags"
                       class="input-base">
                <x-custom-select
                    :options="$entityOptions"
                    :value="(string) ($sourceEntityId ?? 0 ?: '')"
                    name="source_entity_id"
                    placeholder="All originating entities"
                    size="base"
                />
                <x-custom-select
                    :options="$dateRangeOptions"
                    :value="(string) ($dateRange ?? '3m')"
                    name="date_range"
                    placeholder="Select date range"
                    size="base"
                />
                <div class="flex gap-2">
                    <button type="submit" class="btn-base btn-primary">Search</button>
                    @if(($search ?? '') !== '' || !empty($sourceEntityId) || (($dateRange ?? '3m') !== '3m'))
                        <a href="{{ route('dashboard.web_curator.posts.tagged', array_filter(['status' => $currentStatus])) }}"
                           class="btn-base btn-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($statusTabs as $label => $value)
                @php
                    $isActive = $currentStatus === $value || ($label === 'All' && !$currentStatus);
                    $count = $tagCounts[$label] ?? 0;
                    $query = array_filter([
                        'status' => $value,
                        'search' => ($search ?? '') !== '' ? $search : null,
                        'source_entity_id' => !empty($sourceEntityId) ? $sourceEntityId : null,
                        'date_range' => ($dateRange ?? '3m') !== '3m' ? ($dateRange ?? '3m') : null,
                    ]);
                @endphp
                <a href="{{ route('dashboard.web_curator.posts.tagged', $query) }}"
                   class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition {{ $isActive ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}">
                    <span>{{ $label }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @if($taggedPosts->isEmpty())
        <div class="card p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Tagged Posts Found</h3>
            <p class="text-gray-600">
                There are no tagged posts{{ $currentStatus ? ' with status "' . $currentStatus . '"' : '' }}{{ ($search ?? '') !== '' ? ' matching the current search' : '' }}.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($taggedPosts as $taggedPost)
                @php
                    $post = $taggedPost['post'] ?? [];
                    $ownerEntityName = data_get($post, 'entity.cached_data.full_name')
                        ?? data_get($post, 'entity.cached_data.name')
                        ?? data_get($post, 'entity.cached_data.short_name')
                        ?? 'Unknown Entity';
                    $categoryName = data_get($post, 'post_category.name') ?? 'Uncategorized';
                    $featuredImage = $post['featured_image_uri'] ?? null;
                    $publishedAt = $post['published_at'] ?? null;
                    $status = $taggedPost['status'] ?? 'Pending';
                    $statusClass = match ($status) {
                        'Approved' => 'badge-green',
                        'Denied' => 'badge-red',
                        'Withdrawn' => 'badge-gray',
                        default => 'badge-yellow',
                    };
                    $postTags = collect(explode(',', (string) ($post['tags'] ?? '')))
                        ->map(fn ($tag) => trim($tag))
                        ->filter()
                        ->values();
                    $isEffectivelyFeatured = filter_var(
                        $taggedPost['effective_is_featured'] ?? $taggedPost['is_featured_override'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    );
                @endphp

                <div class="card p-0 overflow-hidden">
                    <div class="p-4 lg:p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <span class="badge {{ $statusClass }}">{{ $status }}</span>
                                    <span class="badge badge-gray">{{ $categoryName }}</span>
                                    <span class="badge badge-gray">{{ $post['post_status'] ?? 'Draft' }}</span>
                                    @if($isEffectivelyFeatured)
                                        <span class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-emerald-600">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
                                                <path d="M238.76 51.73A8 8 0 0 0 232 48H40a8 8 0 0 0-5.66 13.66L76.69 104l-42.35 42.34A8 8 0 0 0 40 160h133.62l-28.84 60.56a8 8 0 1 0 14.44 6.88l80-168a8 8 0 0 0-.46-7.71M181.23 144H59.31l34.35-34.34a8 8 0 0 0 0-11.32L59.31 64h160Z"/>
                                            </svg>
                                            Featured For You
                                        </span>
                                    @endif
                                </div>

                                <button type="button"
                                        onclick="openTaggedPostPreview({{ (int) ($taggedPost['id'] ?? 0) }})"
                                        class="text-left text-lg font-semibold leading-snug text-slate-900 transition-colors hover:text-[var(--accent)] focus:outline-none focus:text-[var(--accent)]">
                                    {!! html_entity_decode($post['post_title'] ?? 'Untitled Post') !!}
                                </button>

                                <div class="mt-2 grid gap-x-4 gap-y-1 text-sm text-slate-600 md:grid-cols-2">
                                    <p><span class="font-medium text-slate-800">Source entity:</span> {{ $ownerEntityName }}</p>
                                    <p><span class="font-medium text-slate-800">Author:</span> {{ $post['author'] ?? 'Not specified' }}</p>
                                    <p><span class="font-medium text-slate-800">Tagged on:</span> {{ \Carbon\Carbon::parse($taggedPost['created_at'])->format('d M, Y') }}</p>
                                    <p>
                                        <span class="font-medium text-slate-800">Published:</span>
                                        {{ $publishedAt ? \Carbon\Carbon::parse($publishedAt)->format('d M, Y') : 'Not published yet' }}
                                    </p>
                                </div>

                                @if(!empty($post['post_excerpt']))
                                    <p class="mt-3 line-clamp-2 text-xs leading-6 text-slate-500">
                                        {!! nl2br(e(html_entity_decode($post['post_excerpt']))) !!}
                                    </p>
                                @endif

                                @if($postTags->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach($postTags as $postTag)
                                            <span class="badge badge-gray">{{ $postTag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($featuredImage)
                                <div class="hidden xl:block xl:w-36 xl:flex-shrink-0">
                                    <img src="{{ $featuredImage }}"
                                         alt="{{ $post['post_title'] ?? 'Post image' }}"
                                         class="h-24 w-full rounded-xl border border-slate-200 object-cover shadow-sm">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-slate-200 px-4 py-3 lg:px-5 lg:py-3">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div class="text-xs text-slate-600">
                                @if(!empty($taggedPost['approved_by']))
                                    <span class="font-medium text-slate-800">Last approved by:</span> {{ $taggedPost['approved_by'] }}
                                @elseif($status === 'Denied')
                                    This tagged post is currently denied. You can approve it later to include it in your feed.
                                @elseif($status === 'Withdrawn')
                                    Approval was withdrawn earlier. You can approve it again when needed.
                                @else
                                    Approval status will control whether this post appears in your entity's post feed.
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                        onclick="openTaggedPostPreview({{ (int) ($taggedPost['id'] ?? 0) }})"
                                        class="btn-base btn-outline gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/>
                                        <path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z" stroke-width="1.75"/>
                                    </svg>
                                    View
                                </button>
                                @if($status === 'Approved')
                                    <form method="POST" action="{{ route('dashboard.web_curator.posts.tagged.toggle-featured', $taggedPost['id']) }}">
                                        @csrf
                                        <button type="submit" class="btn-base {{ $isEffectivelyFeatured ? 'btn-secondary' : 'btn-outline' }} gap-2">
                                            <svg class="h-4 w-4" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
                                                <path d="M238.76 51.73A8 8 0 0 0 232 48H40a8 8 0 0 0-5.66 13.66L76.69 104l-42.35 42.34A8 8 0 0 0 40 160h133.62l-28.84 60.56a8 8 0 1 0 14.44 6.88l80-168a8 8 0 0 0-.46-7.71M181.23 144H59.31l34.35-34.34a8 8 0 0 0 0-11.32L59.31 64h160Z"/>
                                            </svg>
                                            {{ $isEffectivelyFeatured ? 'Unfeature' : 'Feature' }}
                                        </button>
                                    </form>
                                @endif
                                @if(in_array($status, ['Pending', 'Denied', 'Withdrawn'], true))
                                    <form method="POST" action="{{ route('dashboard.web_curator.posts.tagged.approve', $taggedPost['id']) }}">
                                        @csrf
                                        <button type="submit" class="btn-base btn-success gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $status === 'Pending' ? 'Approve' : 'Approve Again' }}
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($status, ['Pending', 'Approved'], true))
                                    <form method="POST"
                                          action="{{ route('dashboard.web_curator.posts.tagged.deny', $taggedPost['id']) }}"
                                          onsubmit="return confirm('Deny this tagged post for your entity?');">
                                        @csrf
                                        <button type="submit" class="btn-base btn-danger gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Deny
                                        </button>
                                    </form>
                                @endif

                                @if($status === 'Approved')
                                    <form method="POST"
                                          action="{{ route('dashboard.web_curator.posts.tagged.withdraw', $taggedPost['id']) }}"
                                          onsubmit="return confirm('Withdraw approval for this tagged post?');">
                                        @csrf
                                        <button type="submit" class="btn-base btn-secondary gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                            Withdraw Approval
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(method_exists($taggedPosts, 'links'))
            <div class="mt-6">
                {{ $taggedPosts->links() }}
            </div>
        @endif
    @endif
</div>

<div id="taggedPostPreviewModal" class="hidden fixed inset-0 z-50 bg-[var(--overlay)] p-4 lg:p-8" x-data="{ activeTab: 'preview' }">
    <div class="mx-auto flex h-full w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--border-soft)] px-4 py-4 lg:px-5 lg:py-5">
            <div class="min-w-0">
                <h3 id="taggedPostPreviewTitle" class="truncate text-lg font-semibold text-[var(--text-strong)]">Post Preview</h3>
                <p id="taggedPostPreviewMeta" class="mt-1 text-sm text-[var(--text-soft)]"></p>
            </div>
            <button type="button" onclick="closeTaggedPostPreview()" class="btn-icon" aria-label="Close preview">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex flex-wrap gap-1 border-b border-[var(--border-soft)] px-3 pt-2 lg:px-4">
            <button @click="activeTab = 'preview'"
                    :class="activeTab === 'preview' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="px-4 py-3 text-sm font-medium transition-colors">
                Preview
            </button>
            <button @click="activeTab = 'details'"
                    :class="activeTab === 'details' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="px-4 py-3 text-sm font-medium transition-colors">
                Details
            </button>
            <button @click="activeTab = 'raw'"
                    :class="activeTab === 'raw' ? 'border-b-2 border-[var(--accent)] text-[var(--accent)]' : 'text-[var(--text-soft)] hover:text-[var(--text-strong)]'"
                    class="px-4 py-3 text-sm font-medium transition-colors">
                Raw HTML
            </button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto p-4 lg:p-5">
            <div x-show="activeTab === 'preview'" class="h-full min-h-[24rem] overflow-hidden rounded-xl border border-[var(--border-soft)] bg-white">
                <iframe id="taggedPostPreviewIframe" class="h-full w-full border-0"></iframe>
            </div>

            <div x-show="activeTab === 'details'" class="h-full min-h-[24rem] overflow-y-auto rounded-xl border border-[var(--border-soft)] bg-[var(--surface)]">
                <div id="taggedPostPreviewDetails" class="p-4 text-sm text-[var(--text)]"></div>
            </div>

            <div x-show="activeTab === 'raw'" class="h-full min-h-[24rem] overflow-y-auto rounded-xl border border-[var(--border-soft)] bg-[var(--surface)]">
                <pre id="taggedPostPreviewRaw" class="p-4 text-sm whitespace-pre-wrap font-mono text-[var(--text)]"></pre>
            </div>
        </div>

        <div class="flex justify-end border-t border-[var(--border-soft)] px-4 py-3 lg:px-5 lg:py-3">
            <button type="button" onclick="closeTaggedPostPreview()" class="btn btn-outline">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const taggedPostsData = @json($taggedPostsData);

function decodeTaggedPostHtml(value) {
    const txt = document.createElement('textarea');
    txt.innerHTML = value || '';
    return txt.value;
}

function getTaggedPostPreviewCss() {
    return `
    html, body { margin: 0; padding: 0; width: 100%; max-width: 100%; overflow-x: hidden; }
    body { font-family: system-ui, -apple-system, sans-serif; padding: 0 20px; line-height: 1.75; background: #ffffff; color: #0f172a; box-sizing: border-box; }
    *, *::before, *::after { box-sizing: border-box; }
    body > * { max-width: 100%; }
    body > :first-child { margin-top: 0 !important; }
    body > :last-child { margin-bottom: 0 !important; }
    img, video, iframe, svg, canvas { max-width: 100%; height: auto; }
    iframe { width: 100%; }
    .wc-rendered-content { max-width: 100%; }
    `;
}

@php($renderedContentAssets = app(\App\Support\ModuleAssets::class)->urls('web_curator', 'rendered-content'))
const renderedContentCssUrl = @js($renderedContentAssets['css'][0] ?? '');
const renderedContentJsUrl = @js($renderedContentAssets['js'][0] ?? '');

function buildTaggedPostPreviewDocument(post) {
    const content = decodeTaggedPostHtml(post.post_content || '<p>No content available.</p>');
    return `<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" href="${renderedContentCssUrl}">
            <style>${getTaggedPostPreviewCss()}</style>
        </head>
        <body><div class="wc-rendered-content">${content}</div><script src="${renderedContentJsUrl}"><\/script><script>window.addEventListener('load',function(){window.WebCuratorRenderedContent?.mount?.(document.querySelector('.wc-rendered-content'));});<\/script></body>
    </html>`;
}

function openTaggedPostPreview(tagId) {
    const taggedPost = taggedPostsData.find(item => Number(item.id) === Number(tagId));
    if (!taggedPost) {
        window.showToast?.('Tagged post preview data not found.', 'error');
        return;
    }

    const post = taggedPost.post || {};
    const title = decodeTaggedPostHtml(post.post_title || 'Untitled Post');
    const category = post.post_category?.name || 'Uncategorized';
    const sourceEntity = post.entity?.cached_data?.full_name || post.entity?.cached_data?.name || post.entity?.cached_data?.short_name || 'Unknown Entity';
    const excerpt = decodeTaggedPostHtml(post.post_excerpt || '');
    const published = post.published_at
        ? new Date(post.published_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
        : 'Not published yet';
    const tags = (post.tags || '')
        .split(',')
        .map(tag => tag.trim())
        .filter(Boolean);

    document.getElementById('taggedPostPreviewTitle').textContent = title;
    document.getElementById('taggedPostPreviewMeta').textContent = `${category} • ${post.author || 'Unknown author'} • ${published}`;
    document.getElementById('taggedPostPreviewRaw').textContent = decodeTaggedPostHtml(post.post_content || '');

    const detailsHtml = `
        <div class="grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Source Entity</div>
                <div class="mt-1 text-sm font-medium text-[var(--text-strong)]">${sourceEntity}</div>
            </div>
            <div class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Tagged Status</div>
                <div class="mt-1 text-sm font-medium text-[var(--text-strong)]">${taggedPost.status || 'Pending'}</div>
            </div>
            <div class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Post Status</div>
                <div class="mt-1 text-sm font-medium text-[var(--text-strong)]">${post.post_status || 'Draft'}</div>
            </div>
            <div class="rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Published</div>
                <div class="mt-1 text-sm font-medium text-[var(--text-strong)]">${published}</div>
            </div>
        </div>
        ${excerpt ? `
            <div class="mt-4 rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Excerpt</div>
                <p class="mt-2 text-sm leading-6 text-[var(--text)]">${excerpt.replace(/\n/g, '<br>')}</p>
            </div>` : ''}
        <div class="mt-4 rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-[var(--text-soft)]">Tags</div>
            <div class="mt-3 flex flex-wrap gap-2">
                ${tags.length
                    ? tags.map(tag => `<span class="badge badge-gray">${tag}</span>`).join('')
                    : '<span class="text-sm text-[var(--text-soft)]">No tags</span>'}
            </div>
        </div>
    `;
    document.getElementById('taggedPostPreviewDetails').innerHTML = detailsHtml;

    const iframe = document.getElementById('taggedPostPreviewIframe');
    iframe.srcdoc = buildTaggedPostPreviewDocument(post);

    document.getElementById('taggedPostPreviewModal').classList.remove('hidden');
}

function closeTaggedPostPreview() {
    document.getElementById('taggedPostPreviewModal').classList.add('hidden');
}
</script>
@endpush
