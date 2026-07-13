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
        'announce_secret' => env('TELEGRAM_ANNOUNCE_SECRET'),
    ],

];
