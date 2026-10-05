<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'api_gateway' => [
        'url' => env('API_GATEWAY_URL', 'http://api-gateway:3000'),
        'key' => env('INTERNAL_API_KEY'),
        'timeout' => (int) env('API_GATEWAY_TIMEOUT', 5),
    ],

    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'issuer' => env('JWT_ISSUER', 'ecommerce-platform'),
        'cookie' => 'access_token',
        'secure_cookie' => (bool) env('JWT_SECURE_COOKIE', false),
    ],

];
