<?php
// config/horizon.php
// Laravel Horizon — Queue monitoring & auto-scaling

use Illuminate\Support\Str;

return [
    'domain'  => env('HORIZON_DOMAIN'),
    'path'    => 'horizon',
    'use'     => 'default',
    'prefix'  => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'),
    'middleware' => ['web', 'auth'],

    /*
    |─────────────────────────────────────────────────────────
    | Queue Waits Alert
    | Beri peringatan jika antrian numpuk > 30 job
    |─────────────────────────────────────────────────────────
    */
    'waits' => [
        'redis:ocr_high'      => 30,
        'redis:ocr_normal'    => 60,
        'redis:ocr_low'       => 300,
        'redis:callbacks'     => 15,
        'redis:notifications' => 30,
    ],

    /*
    |─────────────────────────────────────────────────────────
    | Worker Supervisors — Auto-scaling untuk 500+ user
    |─────────────────────────────────────────────────────────
    */
    'environments' => [
        'production' => [

            // ─── OCR High Priority ────────────────────────────
            'supervisor-ocr-high' => [
                'connection'  => 'redis',
                'queue'       => ['ocr_high'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 5,       // Max 5 workers untuk high priority
                'balanceCooldown' => 3,
                'tries'        => 3,
                'timeout'      => 120,
                'memory'       => 256,
            ],

            // ─── OCR Normal Priority ─────────────────────────
            'supervisor-ocr-normal' => [
                'connection'  => 'redis',
                'queue'       => ['ocr_normal'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 2,
                'maxProcesses' => 10,      // Max 10 workers untuk volume tinggi
                'balanceCooldown' => 5,
                'tries'        => 3,
                'timeout'      => 120,
                'memory'       => 256,
            ],

            // ─── OCR Low Priority / Retry ─────────────────────
            'supervisor-ocr-low' => [
                'connection'  => 'redis',
                'queue'       => ['ocr_low'],
                'balance'     => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries'        => 3,
                'timeout'      => 120,
                'memory'       => 256,
            ],

            // ─── Callbacks & Notifications ────────────────────
            'supervisor-fast' => [
                'connection'  => 'redis',
                'queue'       => ['callbacks', 'notifications'],
                'balance'     => 'auto',
                'minProcesses' => 2,
                'maxProcesses' => 8,
                'tries'        => 5,
                'timeout'      => 30,
                'memory'       => 128,
            ],
        ],

        'local' => [
            'supervisor-local' => [
                'connection'   => 'redis',
                'queue'        => ['ocr_high', 'ocr_normal', 'ocr_low', 'callbacks', 'notifications', 'default'],
                'balance'      => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries'        => 1,
                'timeout'      => 60,
            ],
        ],
    ],
];