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

    'paymongo' => [
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'payment_methods' => array_values(array_filter(array_map(
            static fn (string $method) => trim($method),
            explode(',', env('PAYMONGO_PAYMENT_METHODS', 'card,gcash,paymaya,grab_pay')),
        ))),
        'success_url' => env('PAYMONGO_SUCCESS_URL', rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/').'/payments?checkout=success'),
        'cancel_url' => env('PAYMONGO_CANCEL_URL', rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/').'/payments?checkout=cancelled'),
    ],

];
