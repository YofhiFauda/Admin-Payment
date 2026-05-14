<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Log Viewer will be accessible from.
    |
    */

    'route_path' => env('LOG_VIEWER_PATH', 'log-viewer'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Log Viewer route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware.
    |
    */

    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | This callback will be used to authorize users to access Log Viewer.
    | If the callback returns true, the user will be authorized.
    |
    | NOTE: Use string reference instead of closure for config:cache compatibility
    |
    */

    'authorize' => [\App\Http\Middleware\AuthorizeLogViewer::class, 'authorize'],

    /*
    |--------------------------------------------------------------------------
    | Back to System URL
    |--------------------------------------------------------------------------
    |
    | This is the URL that will be used for the "Back to System" link.
    |
    */

    'back_to_system_url' => env('LOG_VIEWER_BACK_URL', '/dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Max Log Size
    |--------------------------------------------------------------------------
    |
    | The maximum log file size (in bytes) that can be displayed.
    | Default: 100MB
    |
    */

    'max_log_size_to_display' => env('LOG_VIEWER_MAX_SIZE', 104857600),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone to use when displaying log timestamps.
    |
    */

    'timezone' => env('LOG_VIEWER_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Include Files Pattern
    |--------------------------------------------------------------------------
    |
    | Only files matching these patterns will be shown in Log Viewer.
    |
    */

    'include_files' => [
        'laravel*.log',
        'error*.log',
        'ocr*.log',
        'queue*.log',
        'security*.log',
        'audit*.log',
        'performance*.log',
        'ai-autofill*.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Files Pattern
    |--------------------------------------------------------------------------
    |
    | Files matching these patterns will be excluded from Log Viewer.
    |
    */

    'exclude_files' => [
        'horizon*.log',
        'pulse*.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shorter Stack Trace Filters
    |--------------------------------------------------------------------------
    |
    | These patterns will be used to filter out stack trace lines.
    |
    */

    'shorter_stack_trace_filters' => [
        '/vendor/laravel/',
        '/vendor/symfony/',
    ],

];
