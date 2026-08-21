<?php

use Illuminate\Support\Facades\Route;
use UasDashboard\WebCurator\Http\Controllers\DashboardController;
use UasDashboard\WebCurator\Http\Controllers\PageController;
use UasDashboard\WebCurator\Http\Controllers\PostController;
use UasDashboard\WebCurator\Http\Controllers\SnippetController;
use UasDashboard\WebCurator\Http\Controllers\MenuController;
use UasDashboard\WebCurator\Http\Controllers\ProfileController;
use UasDashboard\WebCurator\Http\Controllers\SettingController;
use UasDashboard\WebCurator\Http\Controllers\WebsiteAppearanceController;
use UasDashboard\WebCurator\Http\Controllers\MediaController;
use UasDashboard\WebCurator\Http\Controllers\GalleryController;
use UasDashboard\WebCurator\Http\Controllers\ProgramController;

Route::middleware([
        'web',
        'ims.logged_in_and_role_selected:' . implode(',', config('web_curator.role_names', ['Web Curator'])),
    ])
    ->prefix('web-curator')
    ->as('dashboard.web_curator.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // Static Pages
        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{id}/preview', [PageController::class, 'preview'])->name('pages.preview');
        Route::get('/pages/{id}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{id}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{id}', [PageController::class, 'destroy'])->name('pages.destroy');

        // Posts
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/tagged', [PostController::class, 'tagged'])->name('posts.tagged');
        Route::post('/posts/tagged/{tagId}/approve', [PostController::class, 'approveTag'])->name('posts.tagged.approve');
        Route::post('/posts/tagged/{tagId}/deny', [PostController::class, 'denyTag'])->name('posts.tagged.deny');
        Route::post('/posts/tagged/{tagId}/withdraw', [PostController::class, 'withdrawTag'])->name('posts.tagged.withdraw');
        Route::post('/posts/tagged/{tagId}/toggle-featured', [PostController::class, 'toggleTaggedFeatured'])->name('posts.tagged.toggle-featured');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{id}/preview', [PostController::class, 'preview'])->name('posts.preview');
        Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
        Route::put('/posts/{id}/tagged-entities', [PostController::class, 'updateTaggedEntities'])->name('posts.update-tagged-entities');
        Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');

        // Snippets
        Route::get('/snippets', [SnippetController::class, 'index'])->name('snippets.index');
        Route::get('/snippets/create', [SnippetController::class, 'create'])->name('snippets.create');
        Route::post('/snippets', [SnippetController::class, 'store'])->name('snippets.store');
        Route::get('/snippets/{id}/edit', [SnippetController::class, 'edit'])->name('snippets.edit');
        Route::put('/snippets/{id}', [SnippetController::class, 'update'])->name('snippets.update');
        Route::delete('/snippets/{id}', [SnippetController::class, 'destroy'])->name('snippets.destroy');

        // Media library
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::get('/media/library-items', [MediaController::class, 'libraryItems'])->name('media.library-items');
        Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
        Route::post('/media/folders', [MediaController::class, 'storeFolder'])->name('media.folders.store');
        Route::post('/media/folders/reorder', [MediaController::class, 'reorderFolders'])->name('media.folders.reorder');
        Route::put('/media/folders/{id}', [MediaController::class, 'updateFolder'])->name('media.folders.update');
        Route::delete('/media/folders/{id}', [MediaController::class, 'destroyFolder'])->name('media.folders.destroy');
        Route::put('/media/items/{id}', [MediaController::class, 'updateItem'])->name('media.items.update');
        Route::get('/media/items/{id}/download', [MediaController::class, 'downloadItem'])->name('media.items.download');
        Route::post('/media/items/{id}/move', [MediaController::class, 'moveItem'])->name('media.items.move');
        Route::delete('/media/items/{id}', [MediaController::class, 'destroyItem'])->name('media.items.destroy');
        Route::post('/media/galleries', [MediaController::class, 'storeGallery'])->name('media.galleries.store');
        Route::put('/media/galleries/{id}', [MediaController::class, 'updateGallery'])->name('media.galleries.update');
        Route::delete('/media/galleries/{id}', [MediaController::class, 'destroyGallery'])->name('media.galleries.destroy');
        Route::post('/media/galleries/{id}/add-items', [MediaController::class, 'addItemsToGallery'])->name('media.galleries.add-items');
        Route::post('/media/galleries/{id}/remove-items', [MediaController::class, 'removeItemsFromGallery'])->name('media.galleries.remove-items');

        // Galleries
        Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
        Route::get('/galleries/create', [GalleryController::class, 'create'])->name('galleries.create');
        Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store');
        Route::get('/galleries/{id}/edit', [GalleryController::class, 'edit'])->name('galleries.edit');
        Route::put('/galleries/{id}', [GalleryController::class, 'update'])->name('galleries.update');
        Route::delete('/galleries/{id}', [GalleryController::class, 'destroy'])->name('galleries.destroy');

        // Academic programs: canonical data from IMS, website presentation from Web API.
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::get('/programs/{programId}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
        Route::put('/programs/{programId}', [ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{programId}', [ProgramController::class, 'destroy'])->name('programs.destroy');

        // Categories & Menus
        Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
        Route::post('/menus', [MenuController::class, 'update'])->name('menus.update');

        // Entity Profile
        Route::get('/entity_profile', [ProfileController::class, 'edit'])->name('entity_profile.edit');
        Route::put('/entity_profile', [ProfileController::class, 'update'])->name('entity_profile.update');
        Route::post('/entity_profile/search-personnel', [ProfileController::class, 'searchPersonnel'])->name('entity_profile.search_personnel');
        Route::get('/entity_profile/personnel/{personnelId}/roles', [ProfileController::class, 'getPersonnelRoles'])->name('entity_profile.personnel_roles');

        // Website Appearance
        Route::get('/website-appearance', [WebsiteAppearanceController::class, 'edit'])->name('website_appearance.edit');
        Route::put('/website-appearance', [WebsiteAppearanceController::class, 'update'])->name('website_appearance.update');

        // Entity Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
        Route::post('/settings/update-single', [SettingController::class, 'updateSingle'])->name('settings.update-single');
        Route::delete('/settings/delete', [SettingController::class, 'destroy'])->name('settings.destroy');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
