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

    'bunny' => [
        'storage_name' => env('BUNNY_STORAGE_NAME'),
        'api_key' => env('BUNNY_API_KEY'),
        'base_url' => env('BUNNY_ENDPOINT'),
        'cdn_url'      => env('STORAGE_URL'),
    ],

    'danbooru' => [
        'username' => env('DANBOORU_USERNAME'),
        'api_key' => env('DANBOORU_API_KEY'),
    ],

    'gelbooru' => [
        'user_id' => env('GELBOORU_USER_ID'),
        'api_key' => env('GELBOORU_API_KEY'),
        'base_url' => 'https://gelbooru.com/index.php',
    ],

    'zerochan' => [
        'z_id' => env('ZEROCHAN_Z_ID'),
        'z_hash' => env('ZEROCHAN_Z_HASH'),
        'z_theme' => env('ZEROCHAN_Z_THEME'),
        'phpsessid' => env('ZEROCHAN_PHPSESSID'),
        'xbotcheck' => env('ZEROCHAN_XBOTCHECK'),
    ],

];
