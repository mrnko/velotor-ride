<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Club timezone
    |--------------------------------------------------------------------------
    |
    | All weekly period boundaries, the Sunday close job, and displayed dates
    | are computed against this timezone regardless of the server timezone.
    |
    */

    'timezone' => env('VELOTOR_TIMEZONE', 'Europe/Kyiv'),

    /*
    |--------------------------------------------------------------------------
    | Torcoins
    |--------------------------------------------------------------------------
    */

    'torcoin_km_per_coin' => 100,

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
