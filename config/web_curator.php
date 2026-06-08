<?php

return [
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
