<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IMS Roles
    |--------------------------------------------------------------------------
    |
    | A dashboard module may serve multiple IMS roles. Each listed role will
    | redirect into this dashboard, but a single role must belong to only one
    | dashboard module across the host.
    |
    */
    'role_names' => [
        'Web Curator',
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Mode
    |--------------------------------------------------------------------------
    |
    | 'vite' — Use Vite dev server (development, auto-recompile)
    | 'published' — Use pre-built assets published to host's public/ dir
    |
    | In production, run `php artisan vendor:publish --tag=webcurator-assets`
    | then set this to 'published'.
    |
    */
    'asset_mode' => env('WEB_CURATOR_ASSET_MODE', 'vite'),

    'entity_web_base_url' => env('WEB_CURATOR_ENTITY_WEB_BASE_URL', 'web.nstu.ac.bd'),

    'editors' => [
        /*
        |--------------------------------------------------------------------------
        | Primary Content Editor
        |--------------------------------------------------------------------------
        |
        | Supported values: "tinymce", "tiptap"
        |
        */
        'primary' => env('WEB_CURATOR_PRIMARY_EDITOR', 'tiptap'),

        /*
        |--------------------------------------------------------------------------
        | Visual Content Editor
        |--------------------------------------------------------------------------
        |
        | Supported values: "grapesjs", "none"
        |
        */
        'visual' => env('WEB_CURATOR_VISUAL_EDITOR', 'grapesjs'),
    ],
];
