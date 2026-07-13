<?php

namespace App\Services\Torcoins;

class TorcoinCalculator
{
    /**
     * Torcoins scale linearly with distance (e.g. 10 km = 0.1 Torcoin) so a
     * ride always earns *something* — only a full 100 km yields exactly 1.
     */
    public static function fromDistance(float|string $distanceKm): float
    {
        $kmPerCoin = config('velotor.torcoin_km_per_coin', 100);

        return round(((float) $distanceKm) / $kmPerCoin, 2);
    }

    public static function kmToNextCoin(float|string $distanceKm): float
    {
        $kmPerCoin = config('velotor.torcoin_km_per_coin', 100);
        $distanceKm = (float) $distanceKm;

        $remainder = fmod($distanceKm, $kmPerCoin);

        return round($kmPerCoin - $remainder, 2);
    }

    public static function progressPercent(float|string $distanceKm): float
    {
        $kmPerCoin = config('velotor.torcoin_km_per_coin', 100);
        $distanceKm = (float) $distanceKm;

        $remainder = fmod($distanceKm, $kmPerCoin);

        return round(($remainder / $kmPerCoin) * 100, 1);
    }
}
