<?php

return [
    'asset_mode_config' => 'web_curator.asset_mode',
    'published_base' => 'vendor/webcurator',
    'bundles' => [
        'app' => [
            'css' => [
                'resources/css/styles.css',
                'resources/css/editor-shell.css',
                'resources/css/rendered-content.css',
            ],
            'js' => [
                'resources/js/rendered-content.js',
                'resources/js/editor-shell-app.js',
                'resources/js/snippet-workspace.js',
                'resources/js/media-workspace.js',
            ],
        ],
        'rendered-content' => [
            'css' => [
                'resources/css/rendered-content.css',
            ],
            'js' => [
                'resources/js/rendered-content.js',
            ],
        ],
    ],
];
