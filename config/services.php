<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'admin_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_ADMIN_IDS', ''))))),
        'announce_secret' => env('TELEGRAM_ANNOUNCE_SECRET'),
    ],

];
