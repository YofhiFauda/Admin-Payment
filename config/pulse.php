<?php

use Laravel\Pulse\Http\Middleware\Authorize;

return [

    /*
    |--------------------------------------------------------------------------
    | Pulse Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Pulse will be accessible from. If the
    | setting is null, Pulse will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('PULSE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Pulse will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('PULSE_PATH', 'pulse'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Pulse route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Enabled
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable Pulse. This is useful when you
    | want to disable Pulse in certain environments or during maintenance.
    |
    */

    'enabled' => env('PULSE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Pulse Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the storage driver that will be
    | used to store Pulse's data. In addition, you may set any custom
    | options as needed by the particular driver you choose.
    |
    */

    'storage' => [
        'driver' => env('PULSE_STORAGE_DRIVER', 'database'),

        'database' => [
            // 'pulse' = dedicated connection di config/database.php
            // Fallback ke DB utama HANYA jika benar-benar tidak ada env apapun
            'connection' => env('PULSE_DB_CONNECTION', 'pulse'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Ingest Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the ingest driver that will be
    | used to capture entries from your application. You may also set
    | any custom options as needed by the particular driver you choose.
    |
    */

    'ingest' => [
        // 'redis' = data ditulis ke Redis dulu (non-blocking), lalu pulse:work flush ke DB
        // 'storage' = tulis langsung ke DB per request (blocking, tidak cocok production)
        'driver' => env('PULSE_INGEST_DRIVER', 'redis'),

        // Buffer: berapa entri ditampung di memori sebelum dikirim ke Redis
        // 1000 cukup untuk VPS 4GB dengan 9 container — hindari OOM
        'buffer' => env('PULSE_INGEST_BUFFER', 1000),

        'trim' => [
            'lottery' => [1, 1000],
            'keep' => '7 days',
        ],

        'redis' => [
            // 'pulse' = Redis DB index 2 (terpisah dari cache/default)
            // Lihat config/database.php section redis.pulse
            'connection' => env('PULSE_REDIS_CONNECTION', 'pulse'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Recorders
    |--------------------------------------------------------------------------
    |
    | The following array lists the "recorders" that will be registered with
    | Pulse. Recorders gather application event data from requests and tasks
    | to pass to your ingest driver. You may customize this list as needed.
    |
    */

    'recorders' => [
        \Laravel\Pulse\Recorders\CacheInteractions::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_CACHE_INTERACTIONS_SAMPLE_RATE', 1),
            'ignore' => [
                '#^illuminate:pulse:#',
            ],
        ],

        \Laravel\Pulse\Recorders\Exceptions::class => [
            'enabled' => env('PULSE_EXCEPTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_EXCEPTIONS_SAMPLE_RATE', 1),
            'location' => env('PULSE_EXCEPTIONS_LOCATION', true),
            'ignore' => [
                // \Illuminate\Auth\AuthenticationException::class,
                // \Illuminate\Http\Exceptions\HttpResponseException::class,
                // \Illuminate\Validation\ValidationException::class,
            ],
        ],

        \Laravel\Pulse\Recorders\Queues::class => [
            'enabled' => env('PULSE_QUEUES_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1),
            'ignore' => [
                // 'my-queue',
            ],
        ],

        \Laravel\Pulse\Recorders\Servers::class => [
            'enabled' => env('PULSE_SERVERS_ENABLED', true),
            'directories' => [
                '/' => env('PULSE_SERVER_DIRECTORY', base_path()),
            ],
        ],

        \Laravel\Pulse\Recorders\SlowJobs::class => [
            'enabled' => env('PULSE_SLOW_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_JOBS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_JOBS_THRESHOLD', 1000),
            'ignore' => [
                // 'my-job',
            ],
        ],

        \Laravel\Pulse\Recorders\SlowOutgoingRequests::class => [
            'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
            'ignore' => [
                // '#^http://127\.0\.0\.1:13714#', // Inertia SSR...
            ],
        ],

        \Laravel\Pulse\Recorders\SlowQueries::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_QUERIES_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_QUERIES_THRESHOLD', 1000),
            'location' => env('PULSE_SLOW_QUERIES_LOCATION', true),
            'ignore' => [
                // '#^insert into `pulse_#',
            ],
        ],

        \Laravel\Pulse\Recorders\SlowRequests::class => [
            'enabled' => env('PULSE_SLOW_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_REQUESTS_THRESHOLD', 1000),
            'ignore' => [
                '#^/pulse$#',
                '#^/log-viewer#',
            ],
        ],

        \Laravel\Pulse\Recorders\UserJobs::class => [
            'enabled' => env('PULSE_USER_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_JOBS_SAMPLE_RATE', 1),
            'ignore' => [
                // 'my-job',
            ],
        ],

        \Laravel\Pulse\Recorders\UserRequests::class => [
            'enabled' => env('PULSE_USER_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_REQUESTS_SAMPLE_RATE', 1),
            'ignore' => [
                '#^/pulse$#',
                '#^/log-viewer#',
            ],
        ],
    ],

];
