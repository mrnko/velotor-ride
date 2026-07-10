<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class RulesController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Rules', [
            'maxDistanceKm' => (int) Setting::get('max_distance_km', config('velotor.max_distance_km', 1000)),
            'duplicateWindowMinutes' => (int) Setting::get('duplicate_window_minutes', config('velotor.duplicate_window_minutes', 15)),
            'torcoinKmPerCoin' => (int) Setting::get('torcoin_km_per_coin', config('velotor.torcoin_km_per_coin', 100)),
            'timezone' => config('velotor.timezone'),
        ]);
    }
}
