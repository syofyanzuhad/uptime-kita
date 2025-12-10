<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Except Routes
    |--------------------------------------------------------------------------
    |
    | Routes to exclude from Ziggy's output. These are typically development
    | and admin tools that shouldn't be exposed to the frontend.
    |
    */
    'except' => [
        'debugbar.*',
        'horizon.*',
        'telescope',
        'telescope.*',
        'log-viewer.*',
        'ignition.*',
        'sanctum.*',
        'livewire.*',
        'storage.*',
    ],
];
