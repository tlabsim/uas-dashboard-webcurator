@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Programs'],
        ]" />
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="page-title">Programs</h2>
                <p class="mt-1 text-sm text-gray-600">Manage how IMS academic programs appear on the website.</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                {{ $programs->count() }} {{ \Illuminate\Support\Str::plural('program', $programs->count()) }} from IMS
            </span>
        </div>
    </div>

    @if(!$isAcademic)
        <div class="alert-warning">Program publishing is available only for academic entities.</div>
    @elseif($programs->isEmpty())
        <div class="card py-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <x-icon name="academic-program" class="h-7 w-7" />
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">No active programs found</h3>
            <p class="mt-1 text-sm text-gray-500">Programs must first be assigned to this academic unit in IMS.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-[var(--border-soft)] bg-[var(--surface-raised)]">
            <div class="hidden grid-cols-[minmax(0,1fr)_9rem_8rem_7rem] gap-4 border-b border-[var(--border-soft)] bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 md:grid">
                <span>Program</span><span>Level</span><span>Website</span><span class="text-right">Action</span>
            </div>
            @foreach($programs as $program)
                @php
                    $profile = data_get($program, 'website_profile');
                    $status = data_get($profile, 'status');
                    $title = data_get($program, 'full_program_title', data_get($program, 'program_title', 'Untitled program'));
                @endphp
                <div class="grid gap-4 border-b border-[var(--border-soft)] px-5 py-4 last:border-b-0 md:grid-cols-[minmax(0,1fr)_9rem_8rem_7rem] md:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate font-semibold text-gray-900">{{ $title }}</h3>
                            @if(data_get($profile, 'is_featured'))
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Featured</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ data_get($program, 'program_code') ?: 'No program code' }}</p>
                    </div>
                    <div class="text-sm text-gray-700">{{ data_get($program, 'level.name', 'Not specified') }}</div>
                    <div>
                        @if($status === 'Published')
                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Published</span>
                        @elseif($status === 'Draft')
                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Draft</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Default</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        @if(data_get($program, 'preview_url'))
                            <a href="{{ data_get($program, 'preview_url') }}" target="_blank" rel="noopener" class="btn-base btn-outline h-8 px-2.5 text-xs" aria-label="Visit program page">Visit</a>
                        @endif
                        <a href="{{ route('dashboard.web_curator.programs.edit', data_get($program, 'id')) }}" class="btn-base btn-primary h-8 px-3 text-xs">Manage</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
