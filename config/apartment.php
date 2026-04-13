<?php

return [
    'claude_model' => env('APARTMENT_CLAUDE_MODEL', 'claude-sonnet-4-5'),
    'api_token' => env('APARTMENT_API_TOKEN'),

    'paths' => [
        'inbox' => storage_path('app/apartment/inbox'),
        'knowledge' => storage_path('app/apartment/knowledge'),
    ],
];
