@extends('dashboard.layouts.default')

@push('styles')
    @vite('resources/css/web-curator/styles.css')
@endpush

@section('content')
<div x-data="dashboardSidebar()" class="dashboard-shell">
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="dashboard-sidebar-backdrop lg:hidden"
        @click="closeSidebar()"
    ></div>

    <aside
        class="dashboard-sidebar"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        @include('web_curator::partials.sidebar')
    </aside>

    <div class="dashboard-main-shell">
        <main class="dashboard-page">
            @if ($errors->any())
                <div x-data="{ show: true }" x-show="show" class="alert-error mb-4" role="alert">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1">
                        <p class="font-semibold">Validation Errors</p>
                        <ul class="text-sm mt-1 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        </div>
                        <button @click="show = false" class="text-current/70 transition-colors hover:text-current">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <div class="dashboard-page-surface">
                <div class="mx-auto flex min-h-0 w-full max-w-7xl flex-1 flex-col">
                    @yield('dashboard-content')
                </div>
            </div>
        </main>
    </div>
</div>

@if (($webCuratorEditorConfig['primary'] ?? null) === 'tinymce')
    @push('scripts')
        <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    @endpush
@endif
@endsection
