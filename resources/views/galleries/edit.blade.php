@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="container-large space-y-6">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Galleries', 'url' => route('dashboard.web_curator.galleries.index')],
            ['label' => 'Edit'],
        ]" />
        <h2 class="page-title">Edit Gallery</h2>
        <p class="mt-1 text-sm text-gray-600">{{ data_get($gallery, 'title') }}</p>
    </div>

    @include('web_curator::galleries.partials.form', [
        'action' => route('dashboard.web_curator.galleries.update', data_get($gallery, 'id')),
        'method' => 'PUT',
        'submitLabel' => 'Save Changes',
    ])
</div>
@endsection
