<?php

return [
    'pages' => [
        'ensure_pages_exist' => env('APP_ENV', 'production') === 'local',
        'paths' => [
            resource_path('js/Pages'),
        ],
        'extensions' => [
            'vue',
        ],
    ],
];
