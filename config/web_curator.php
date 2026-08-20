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

    'entity_web_base_url' => env('WEB_CURATOR_ENTITY_WEB_BASE_URL', 'http://entities.nstu.local'),

    'content_preview' => [
        'secret' => env('UAS_CONTENT_PREVIEW_SECRET'),
        'ttl_minutes' => (int) env('UAS_CONTENT_PREVIEW_TTL_MINUTES', 15),
    ],

    'website_templates' => [
        [
            'key' => 'department-classic',
            'label' => 'Department Classic',
            'description' => 'Content-first entity template with hero, updates, static pages, and faculty directory support.',
            'entity_types' => ['department', 'academic department', 'faculty', 'institute'],
        ],
    ],

    'website_font_options' => [
        'sans' => [
            ['key' => 'source-sans-3', 'label' => 'Source Sans 3', 'family' => "'Source Sans 3', ui-sans-serif, system-ui, sans-serif", 'bunny_family' => 'source-sans-3:400,500,600,700'],
            ['key' => 'inter', 'label' => 'Inter', 'family' => "'Inter', ui-sans-serif, system-ui, sans-serif", 'bunny_family' => 'inter:400,500,600,700'],
            ['key' => 'instrument-sans', 'label' => 'Instrument Sans', 'family' => "'Instrument Sans', ui-sans-serif, system-ui, sans-serif", 'bunny_family' => 'instrument-sans:400,500,600,700'],
            ['key' => 'nunito-sans', 'label' => 'Nunito Sans', 'family' => "'Nunito Sans', ui-sans-serif, system-ui, sans-serif", 'bunny_family' => 'nunito-sans:400,500,600,700'],
        ],
        'serif' => [
            ['key' => 'source-serif-4', 'label' => 'Source Serif 4', 'family' => "'Source Serif 4', ui-serif, Georgia, serif", 'bunny_family' => 'source-serif-4:400,500,600,700'],
            ['key' => 'merriweather', 'label' => 'Merriweather', 'family' => "'Merriweather', ui-serif, Georgia, serif", 'bunny_family' => 'merriweather:400,700'],
            ['key' => 'libre-baskerville', 'label' => 'Libre Baskerville', 'family' => "'Libre Baskerville', ui-serif, Georgia, serif", 'bunny_family' => 'libre-baskerville:400,700'],
            ['key' => 'bitter', 'label' => 'Bitter', 'family' => "'Bitter', ui-serif, Georgia, serif", 'bunny_family' => 'bitter:400,500,600,700'],
        ],
    ],

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
