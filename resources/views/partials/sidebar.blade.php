@php
    $navItems = [
        [
            'label' => 'Home',
            'route' => 'dashboard.web_curator.index',
            'active' => ['dashboard.web_curator.index'],
            'icon' => 'home',
        ],
        [
            'type' => 'label',
            'label' => 'Publishing',
        ],
        [
            'label' => 'Static Pages',
            'icon' => 'page',
            'active' => ['dashboard.web_curator.pages.*'],
            'children' => [
                ['label' => 'View Pages', 'route' => 'dashboard.web_curator.pages.index', 'active' => ['dashboard.web_curator.pages.index']],
                ['label' => 'Add New', 'route' => 'dashboard.web_curator.pages.create', 'active' => ['dashboard.web_curator.pages.create']],
            ],
        ],
        [
            'label' => 'Posts',
            'icon' => 'post',
            'active' => ['dashboard.web_curator.posts.*'],
            'children' => [
                ['label' => 'View Posts', 'route' => 'dashboard.web_curator.posts.index', 'active' => ['dashboard.web_curator.posts.index']],
                ['label' => 'Tagged Posts', 'route' => 'dashboard.web_curator.posts.tagged', 'active' => ['dashboard.web_curator.posts.tagged']],
                ['label' => 'Add New', 'route' => 'dashboard.web_curator.posts.create', 'active' => ['dashboard.web_curator.posts.create']],
            ],
        ],
        [
            'label' => 'Snippets',
            'icon' => 'snippet',
            'active' => ['dashboard.web_curator.snippets.*'],
            'children' => [
                ['label' => 'View Snippets', 'route' => 'dashboard.web_curator.snippets.index', 'active' => ['dashboard.web_curator.snippets.index']],
                ['label' => 'Add New', 'route' => 'dashboard.web_curator.snippets.create', 'active' => ['dashboard.web_curator.snippets.create']],
            ],
        ],
        [
            'label' => 'Media Library',
            'route' => 'dashboard.web_curator.media.index',
            'active' => ['dashboard.web_curator.media.*'],
            'icon' => 'media-library',
        ],
        [
            'type' => 'label',
            'label' => 'Site Structure',
        ],
        [
            'label' => 'Entity Profile',
            'route' => 'dashboard.web_curator.entity_profile.edit',
            'active' => ['dashboard.web_curator.entity_profile.*'],
            'icon' => 'entity-profile',
        ],
        [
            'label' => 'Website Appearance',
            'route' => 'dashboard.web_curator.website_appearance.edit',
            'active' => ['dashboard.web_curator.website_appearance.*'],
            'icon' => 'website-appearance',
        ],
        [
            'label' => 'Categories & Menus',
            'route' => 'dashboard.web_curator.menus.index',
            'active' => ['dashboard.web_curator.menus.*'],
            'icon' => 'menu',
        ],
        [
            'label' => 'Entity Settings',
            'route' => 'dashboard.web_curator.settings.index',
            'active' => ['dashboard.web_curator.settings.*'],
            'icon' => 'settings',
        ],
    ];
@endphp

@php
    $currentRoleId = session('ims_user.current_db_role_id', null);
    $allRoles = collect(session('ims_user.db_roles', []));
    $currentRole = $allRoles->firstWhere('assignment_id', $currentRoleId);
    $currentEntityName = $currentRole['scope_entity_name'] ?? null;
@endphp

<div class="dashboard-sidebar-panel">
    <div class="dashboard-sidebar-header" style="background: linear-gradient(to right, #abcdc015, #e7f0ee00);">
        <div class="dashboard-sidebar-header-titles">
            <h2 class="dashboard-sidebar-title">Web Curator</h2>
            @if($currentEntityName)
                <h5 class="dashboard-sidebar-subtitle">{{ $currentEntityName }}</h5>
            @endif
        </div>
        <button type="button" class="dashboard-sidebar-close" @click="closeSidebar()" aria-label="Close navigation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="dashboard-sidebar-body custom-scrollbar">
        <x-dashboard.sidebar-nav :items="$navItems" />
    </div>

    @include('dashboard.layouts.partials.sidebar-user-panel', [
        'class' => 'md:hidden',
        'profileUrl' => route('dashboard.profile.show'),
        'preferencesUrl' => route('dashboard.preferences.index'),
    ])
</div>
