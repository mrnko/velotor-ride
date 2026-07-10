<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('telegram_chat_id', config('services.telegram.chat_id', ''));
        Setting::set('telegram_invite_url', '');
        Setting::set('timezone', config('velotor.timezone'));
        Setting::set('torcoin_km_per_coin', config('velotor.torcoin_km_per_coin', 100), 'integer');
        Setting::set('max_distance_km', config('velotor.max_distance_km', 1000), 'integer');
        Setting::set('duplicate_window_minutes', config('velotor.duplicate_window_minutes', 15), 'integer');
        Setting::set('duplicate_distance_delta_km', config('velotor.duplicate_distance_delta_km', 0.5), 'float');
    }
}
