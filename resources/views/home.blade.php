@extends('web_curator::layouts.default')

@section('title', 'Web Curator Dashboard')

@section('dashboard-content')
    <h2 class="text-2xl font-bold text-gray-700 mb-4">Welcome, {{ session('ims_user.name') ?? 'Web Curator' }}</h2>
    <p class="text-gray-600">This is your summary dashboard. Use the side menu to manage pages, posts, snippets, and more.</p>
@endsection
