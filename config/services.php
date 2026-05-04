<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK'),
        'secret'      => env('N8N_SECRET'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'group_monitoring_id' => env('TELEGRAM_GROUP_MONITORING_ID', null),
    ],

    // ─── Image Compression (OCR Pre-processing) ──────────────────
    'compression' => [
        'max_size'        => env('COMPRESSION_MAX_SIZE', 1048576), // 1MB dalam bytes
        'initial_quality' => env('COMPRESSION_INITIAL_QUALITY', 85),
        'min_quality'     => env('COMPRESSION_MIN_QUALITY', 75),   // min 75% untuk jaga akurasi OCR
        'enabled'         => env('COMPRESSION_ENABLED', true),
    ],

    'upload' => [
        'max_size_kb'        => env('UPLOAD_MAX_SIZE', 5120),       // 5MB dalam KB
        'allowed_mimes'      => ['image/jpeg', 'image/jpg', 'image/png'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png'],
    ],

];
