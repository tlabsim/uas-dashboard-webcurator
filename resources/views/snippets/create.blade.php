@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Snippets', 'url' => route('dashboard.web_curator.snippets.index')],
            ['label' => 'Add New'],
        ]" />
        <h2 class="page-title">Create Snippet</h2>
        <p class="mt-1 text-sm text-gray-600">Create reusable HTML, CSS, and JavaScript blocks for your entity website.</p>
    </div>

    @include('web_curator::snippets._form', ['mode' => 'create'])
</div>
@endsection
