<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Club timezone
    |--------------------------------------------------------------------------
    |
    | All weekly period boundaries, the Monday close job, and displayed dates
    | are computed against this timezone regardless of the server timezone.
    |
    */

    'timezone' => env('VELOTOR_TIMEZONE', 'Europe/Kyiv'),

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | Public-facing club identity, canonical URLs and outbound links. Shared
    | with the frontend via HandleInertiaRequests and used across SEO tags,
    | the footer and Schema.org markup.
    |
    */

    'brand' => [
        'name' => 'ВелоТОР',
        'full_name' => 'Велоклуб «ВелоТОР»',
        'tagline' => 'Результати велозаїздів',
        'description' => 'Велоклуб «ВелоТОР» — дружня спільнота велосипедистів. Надсилай кілометраж у Telegram, а бот рахує тижневі й річні рейтинги та нараховує Torcoins.',
        'telegram_url' => env('VELOTOR_TELEGRAM_URL', 'https://t.me/veloTor'),
        'shop_url' => env('VELOTOR_SHOP_URL', 'https://velotor.com.ua'),
        'site_url' => env('VELOTOR_SITE_URL', 'https://ride.velotor.com.ua'),
        'developer_name' => 'Oleksii Myronenko',
        'developer_url' => 'https://mrnko.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Torcoins
    |--------------------------------------------------------------------------
    */

    'torcoin_km_per_coin' => 100,

    'weekly_first_result_bonus' => 0.1,

    /*
    |--------------------------------------------------------------------------
    | Result submission validation defaults
    |--------------------------------------------------------------------------
    |
    | These are seeded into the `settings` table on first migrate/seed and are
    | editable afterwards from the admin panel. The values below are only the
    | fallback used before the settings table has been seeded.
    |
    */

    'max_distance_km' => 1000,

    'duplicate_window_minutes' => 15,

    'duplicate_distance_delta_km' => 0.5,

];
