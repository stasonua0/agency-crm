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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tochka' => [
        'sandbox' => env('TOCHKA_SANDBOX', true),
        'use_stub' => env('TOCHKA_USE_STUB', true),
        'base_url' => env('TOCHKA_BASE_URL', 'https://enter.tochka.com/sandbox/v2'),
        'token' => env('TOCHKA_TOKEN', env('TOCHKA_SANDBOX', true) ? 'sandbox.jwt.token' : null),
        'client_id' => env('TOCHKA_CLIENT_ID'),
        'customer_code' => env('TOCHKA_CUSTOMER_CODE'),
        'webhook_public_url' => env('TOCHKA_WEBHOOK_PUBLIC_URL'),
        'webhook_public_key_url' => env('TOCHKA_WEBHOOK_PUBLIC_KEY_URL', 'https://enter.tochka.com/.well-known/jwks.json'),
        'timeout' => env('TOCHKA_TIMEOUT', 15),
    ],

    'dadata' => [
        'sandbox' => env('DADATA_SANDBOX', true),
        'base_url' => env('DADATA_BASE_URL', 'https://suggestions.dadata.ru'),
        'token' => env('DADATA_TOKEN'),
        'secret' => env('DADATA_SECRET'),
        'timeout' => env('DADATA_TIMEOUT', 15),
    ],

];
