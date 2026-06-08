@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="container-large space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Galleries'],
        ]" />
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="page-title">Galleries</h2>
                <p class="mt-1 text-sm text-gray-600">
                    <span class="font-semibold text-[var(--accent)]">{{ $context['entity_name'] }}</span>
                    <span class="mx-1 text-gray-400">|</span>
                    <span class="font-semibold text-gray-800">{{ number_format($stats['total']) }}</span>
                    <span class="text-gray-600">galleries</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.web_curator.media.index') }}" class="btn-base btn-outline inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="h-5 w-5"><path fill="currentColor" d="M35.32 13.74a1.71 1.71 0 0 0-1.45-.74h-22.7a2.59 2.59 0 0 0-2.25 1.52a1 1 0 0 0 0 .14L6 25V7h6.49l2.61 3.59a1 1 0 0 0 .81.41H32a2 2 0 0 0-2-2H16.42l-2.31-3.18A2 2 0 0 0 12.49 5H6a2 2 0 0 0-2 2v22.69A1.37 1.37 0 0 0 5.41 31h24.93a1 1 0 0 0 1-.72l4.19-15.1a1.64 1.64 0 0 0-.21-1.44M29.55 29H6.9l3.88-13.81a.66.66 0 0 1 .38-.24h22.33Z"/></svg>
                    Media Library
                </a>
                <a href="{{ route('dashboard.web_curator.galleries.create') }}" class="btn-base btn-outline inline-flex items-center gap-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add New Gallery
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <div class="card p-4">
            <div class="wc-stat-label">Total</div>
            <div class="wc-stat-value">{{ number_format($stats['total']) }}</div>
            <div class="wc-stat-note">All galleries</div>
        </div>
        <div class="card p-4">
            <div class="wc-stat-label">Published</div>
            <div class="wc-stat-value">{{ number_format($stats['published']) }}</div>
            <div class="wc-stat-note">Public</div>
        </div>
        <div class="card p-4">
            <div class="wc-stat-label">Draft</div>
            <div class="wc-stat-value">{{ number_format($stats['draft']) }}</div>
            <div class="wc-stat-note">Work in progress</div>
        </div>
        <div class="card p-4">
            <div class="wc-stat-label">Withdrawn</div>
            <div class="wc-stat-value">{{ number_format($stats['withdrawn']) }}</div>
            <div class="wc-stat-note">Hidden</div>
        </div>
        <div class="card p-4">
            <div class="wc-stat-label">Featured</div>
            <div class="wc-stat-value">{{ number_format($stats['featured']) }}</div>
            <div class="wc-stat-note">Highlighted</div>
        </div>
    </div>

    <div class="card p-3">
        <form method="GET" action="{{ route('dashboard.web_curator.galleries.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr),180px,180px,160px]">
            <div>
                <label class="label-base">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or excerpt..." class="input-base w-full">
            </div>
            <div>
                <label class="label-base">Status</label>
                <select name="status" class="select-base w-full">
                    <option value="">All statuses</option>
                    @foreach(['Published', 'Draft', 'Withdrawn'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-base">Featured</label>
                <select name="is_featured" class="select-base w-full">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_featured') === '1')>Featured only</option>
                    <option value="0" @selected(request('is_featured') === '0')>Non-featured</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button type="submit" class="btn btn-secondary w-full">Filter</button>
                <a href="{{ route('dashboard.web_curator.galleries.index') }}" class="btn btn-outline w-full text-center">Clear</a>
            </div>
        </form>
    </div>

    @if($galleries->isEmpty())
        <div class="card">
            <div class="p-10 text-center text-gray-500">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--surface)] text-[var(--text-soft)]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="h-8 w-8"><path fill="currentColor" d="M32 4H4a2 2 0 0 0-2 2v24a2 2 0 0 0 2 2h28a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2M4 30V6h28v24Z"/><path fill="currentColor" d="M8.92 14a3 3 0 1 0-3-3a3 3 0 0 0 3 3m0-4.6A1.6 1.6 0 1 1 7.33 11a1.6 1.6 0 0 1 1.59-1.59Z"/><path fill="currentColor" d="m22.78 15.37l-5.4 5.4l-4-4a1 1 0 0 0-1.41 0L5.92 22.9v2.83l6.79-6.79L16 22.18l-3.75 3.75H15l8.45-8.45L30 24v-2.82l-5.81-5.81a1 1 0 0 0-1.41 0"/></svg>
                </div>
                <p class="text-lg font-medium text-gray-700">No galleries found</p>
                <p class="mt-1 text-sm">Create a gallery and curate media from your library.</p>
            </div>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3" x-data="galleryIndexPage()">
            @foreach($galleries as $gallery)
                @php
                    $galleryId = data_get($gallery, 'id');
                    $status = data_get($gallery, 'gallery_status', 'Draft');
                    $statusClass = match($status) {
                        'Published' => 'badge-tint-green',
                        'Draft' => 'badge-tint-yellow',
                        'Withdrawn' => 'badge-tint-red',
                        default => 'badge-tint-gray',
                    };
                    $coverUrl = data_get($gallery, 'cover_media_item.full_url') ?: data_get($gallery, 'cover_media_item.public_url');
                @endphp
                <div class="card overflow-hidden">
                    <div class="wc-gallery-card-cover">
                        @if($coverUrl)
                            <img src="{{ $coverUrl }}" alt="{{ data_get($gallery, 'title') }}" class="h-full w-full object-cover">
                        @else
                            <div class="wc-media-card-placeholder !h-full">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="h-12 w-12"><path fill="currentColor" d="M32 4H4a2 2 0 0 0-2 2v24a2 2 0 0 0 2 2h28a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2M4 30V6h28v24Z"/><path fill="currentColor" d="M8.92 14a3 3 0 1 0-3-3a3 3 0 0 0 3 3m0-4.6A1.6 1.6 0 1 1 7.33 11a1.6 1.6 0 0 1 1.59-1.59Z"/><path fill="currentColor" d="m22.78 15.37l-5.4 5.4l-4-4a1 1 0 0 0-1.41 0L5.92 22.9v2.83l6.79-6.79L16 22.18l-3.75 3.75H15l8.45-8.45L30 24v-2.82l-5.81-5.81a1 1 0 0 0-1.41 0"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-[var(--text-strong)]">{{ data_get($gallery, 'title') }}</h3>
                                <p class="truncate text-xs text-gray-500">{{ data_get($gallery, 'slug') }}</p>
                            </div>
                            <span class="badge-tint {{ $statusClass }} text-[11px]">{{ $status }}</span>
                        </div>

                        @if(data_get($gallery, 'excerpt'))
                            <p class="text-sm text-gray-600" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                {{ data_get($gallery, 'excerpt') }}
                            </p>
                        @endif

                        <div class="grid grid-cols-3 gap-2 text-xs text-gray-500">
                            <div class="rounded-xl bg-[var(--surface)] px-3 py-2">
                                <span class="block font-medium text-[var(--text-strong)]">{{ number_format((int) data_get($gallery, 'items_count', 0)) }}</span>
                                <span>Items</span>
                            </div>
                            <div class="rounded-xl bg-[var(--surface)] px-3 py-2">
                                <span class="block font-medium text-[var(--text-strong)]">{{ data_get($gallery, 'is_featured') ? 'Yes' : 'No' }}</span>
                                <span>Featured</span>
                            </div>
                            <div class="rounded-xl bg-[var(--surface)] px-3 py-2">
                                <span class="block font-medium text-[var(--text-strong)]">{{ data_get($gallery, 'published_at') ? \Carbon\Carbon::parse(data_get($gallery, 'published_at'))->format('M d') : '—' }}</span>
                                <span>Publish</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                            <span>{{ data_get($gallery, 'author') ?: 'No author set' }}</span>
                            <span>{{ data_get($gallery, 'updated_at') ? \Carbon\Carbon::parse(data_get($gallery, 'updated_at'))->diffForHumans() : 'Updated recently' }}</span>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('dashboard.web_curator.galleries.edit', $galleryId) }}" class="btn btn-outline flex-1 text-center">Edit</a>
                            <form id="gallery-delete-{{ $galleryId }}" method="POST" action="{{ route('dashboard.web_curator.galleries.destroy', $galleryId) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline text-red-600" @click="confirmDelete('gallery-delete-{{ $galleryId }}')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            {{ $galleries->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function galleryIndexPage() {
        return {
            async confirmDelete(formId) {
                const form = document.getElementById(formId);
                if (!form) return;

                if (window.modalNotifier?.confirm) {
                    const confirmed = await window.modalNotifier.confirm('Delete this gallery?', {
                        confirmLabel: 'Delete',
                        confirmVariant: 'error',
                    });

                    if (confirmed) {
                        form.submit();
                    }

                    return;
                }

                if (window.confirm('Delete this gallery?')) {
                    form.submit();
                }
            }
        };
    }
</script>
@endpush
