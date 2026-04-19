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

    'interview_ai' => [
        'enabled' => env('INTERVIEW_AI_ENABLED', false),
        'base_url' => env('INTERVIEW_AI_BASE_URL', 'https://api.openai.com'),
        'chat_endpoint' => env('INTERVIEW_AI_CHAT_ENDPOINT', '/v1/chat/completions'),
        'model' => env('INTERVIEW_AI_MODEL', 'gpt-4.1-mini'),
        'api_key' => env('INTERVIEW_AI_API_KEY'),
        'timeout' => env('INTERVIEW_AI_TIMEOUT', 25),
        'retries' => env('INTERVIEW_AI_RETRIES', 2),
        'retry_sleep_ms' => env('INTERVIEW_AI_RETRY_SLEEP_MS', 350),
    ],

];
