<?php

return [

    'send' => [
        'admin_email' => env('SEND_ADMIN_EMAIL', 'pakpalididier@gmail.com'),
    ],

    'kprimesms' => [
        'base_url' => env('KPRIME_SMS_BASE_URL', 'https://api.kprimesms.com/v1'),
        'token' => env('KPRIME_SMS_TOKEN'),
        'key' => env('KPRIME_SMS_KEY'),
        'sender' => env('KPRIME_SMS_SENDER'),
        'sender_id' => env('KPRIME_SMS_SENDER_ID'),
        'response_url' => env('KPRIME_SMS_RESPONSE_URL'),
        'callback_secret' => env('KPRIME_SMS_CALLBACK_SECRET'),
    ],

    'kprimepay' => [
        'base_url' => env('KPRIMEPAY_BASE_URL', 'https://api.kprimepay.com/v2'),
        'token' => env('KPRIMEPAY_TOKEN'),
        'ca_bundle' => env('KPRIMEPAY_CA_BUNDLE', env('CURL_CA_BUNDLE')),
        'mode' => (int) env('KPRIMEPAY_MODE', 2),
        'with_fees' => (int) env('KPRIMEPAY_WITH_FEES', 1),
        'sms_unit_price' => (int) env('KPRIMEPAY_SMS_UNIT_PRICE', 25),
        'sms_unit_cost' => (int) env('KPRIMEPAY_SMS_UNIT_COST', 15),
        'whatsapp_unit_price' => (int) env('KPRIMEPAY_WHATSAPP_UNIT_PRICE', 25),
        'whatsapp_unit_cost' => (int) env('KPRIMEPAY_WHATSAPP_UNIT_COST', 15),
    ],

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

];
