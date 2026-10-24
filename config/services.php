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

    // Headless Chrome, driven by spatie/browsershot, renders certificates to
    // PDF for the bulk "Download as PDFs" action. Leave the binaries unset to
    // let Browsershot find node/npm on the PATH and use Puppeteer's own Chrome.
    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        // Chrome refuses to start as root or inside many containers without it.
        'no_sandbox' => (bool) env('BROWSERSHOT_NO_SANDBOX', true),
        'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 90),
        // Certificates per bulk download. Raise it together with the web
        // server's request timeout (nginx fastcgi_read_timeout).
        'max_batch' => (int) env('BROWSERSHOT_MAX_BATCH', 15),
    ],

];
