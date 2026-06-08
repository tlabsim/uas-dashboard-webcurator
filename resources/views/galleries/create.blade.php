@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="container-large space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Galleries', 'url' => route('dashboard.web_curator.galleries.index')],
            ['label' => 'Add New'],
        ]" />
        <h2 class="page-title">Create Gallery</h2>
        <p class="mt-1 text-sm text-gray-600">Select media from the library and organize it for publishing.</p>
    </div>

    @include('web_curator::galleries.partials.form', [
        'action' => route('dashboard.web_curator.galleries.store'),
        'method' => 'POST',
        'submitLabel' => 'Create Gallery',
    ])
</div>
@endsection
