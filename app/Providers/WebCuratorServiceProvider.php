<?php

namespace UasDashboard\WebCurator\Providers;

use App\Modules\DashboardModule;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WebCuratorServiceProvider extends ServiceProvider implements DashboardModule
{
    private const PRIMARY_EDITORS = ['tinymce', 'tiptap'];
    private const VISUAL_EDITORS = ['grapesjs', 'none'];
    // ─── DashboardModule contract ───────────────────────────

    public function id(): string
    {
        return 'web_curator';
    }

    public function name(): string
    {
        return 'Web Curator';
    }

    public function roleNames(): array
    {
        return config('web_curator.role_names', ['Web Curator']);
    }

    public function routePrefix(): string
    {
        return 'web-curator';
    }

    public function routeNamePrefix(): string
    {
        return 'dashboard.web_curator';
    }

    public function routesFile(): string
    {
        return __DIR__ . '/../../routes/web.php';
    }

    public function assetConfigFile(): ?string
    {
        return __DIR__ . '/../../config/module_assets.php';
    }

    public function middleware(): array
    {
        return ['web', 'ims.logged_in_and_role_selected:' . implode(',', $this->roleNames())];
    }

    public function navigation(): array
    {
        return [
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
                'label' => 'Categories & Menus',
                'route' => 'dashboard.web_curator.menus.index',
                'active' => ['dashboard.web_curator.menus.*'],
                'icon' => 'menu',
            ],
            [
                'label' => 'Entity Profile',
                'route' => 'dashboard.web_curator.entity_profile.edit',
                'active' => ['dashboard.web_curator.entity_profile.*'],
                'icon' => 'entity-profile',
            ],
            [
                'label' => 'Entity Settings',
                'route' => 'dashboard.web_curator.settings.index',
                'active' => ['dashboard.web_curator.settings.*'],
                'icon' => 'settings',
            ],
        ];
    }

    public function usesMobileSidebar(): bool
    {
        return true;
    }

    // ─── Service provider ──────────────────────────────────

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/web_curator.php', 'web_curator'
        );

        // Register this module with the host's registry
        $this->app->make(ModuleRegistry::class)->register($this);
    }

    public function boot(): void
    {
        // Register Blade views — modules reference them as web_curator::pages.index
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'web_curator');

        // Register Blade components
        $this->loadViewComponentsAs('web-curator', [
            View\Components\CategoryBox::class,
            View\Components\SubcategoryBox::class,
        ]);

        // Publish pre-built assets to host's public/vendor/webcurator/
        // Run: php artisan vendor:publish --tag=webcurator-assets
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/webcurator'),
        ], 'webcurator-assets');

        // Share editor configuration with web_curator views
        $primaryEditor = strtolower((string) config('web_curator.editors.primary', 'tiptap'));
        $visualEditor = strtolower((string) config('web_curator.editors.visual', 'grapesjs'));

        View::share('webCuratorEditorConfig', [
            'primary' => in_array($primaryEditor, self::PRIMARY_EDITORS, true) ? $primaryEditor : 'tiptap',
            'visual' => in_array($visualEditor, self::VISUAL_EDITORS, true) ? $visualEditor : 'grapesjs',
        ]);
    }
}
