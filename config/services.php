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

    'pjbank' => [
        'webhook_secret' => env('PJBANK_WEBHOOK_SECRET'),
        'webhook_url'    => env('PJBANK_WEBHOOK_URL'),
    ],

    'act' => [
        'enabled'    => env('ACT_ENABLED', false),
        'serpro'     => ['url' => env('ACT_SERPRO_URL'),    'user' => env('ACT_SERPRO_USER'),    'password' => env('ACT_SERPRO_PASSWORD')],
        'bry'        => ['url' => env('ACT_BRY_URL'),       'user' => env('ACT_BRY_USER'),       'password' => env('ACT_BRY_PASSWORD')],
        'soluti'     => ['url' => env('ACT_SOLUTI_URL'),    'user' => env('ACT_SOLUTI_USER'),    'password' => env('ACT_SOLUTI_PASSWORD')],
        'certisign'  => ['url' => env('ACT_CERTISIGN_URL'), 'user' => env('ACT_CERTISIGN_USER'), 'password' => env('ACT_CERTISIGN_PASSWORD')],
        // Sandbox gratuito para testes (sem ICP-Brasil — não use em produção)
        'freetsa'    => ['url' => 'https://freetsa.org/tsr', 'user' => '', 'password' => ''],
    ],


    'meta_whatsapp' => [
        'enabled'       => env('META_WA_ENABLED', false),
        'phone_id'      => env('META_WA_PHONE_ID'),
        'access_token'  => env('META_WA_ACCESS_TOKEN'),
        'verify_token'  => env('META_WA_WEBHOOK_VERIFY_TOKEN'),
        'api_version'   => env('META_WA_API_VERSION', 'v19.0'),
    ],

];
