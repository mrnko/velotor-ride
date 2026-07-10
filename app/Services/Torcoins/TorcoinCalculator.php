<?php

namespace App\Services\Torcoins;

class TorcoinCalculator
{
    public static function fromDistance(float|string $distanceKm): int
    {
        $kmPerCoin = config('velotor.torcoin_km_per_coin', 100);

        return (int) floor(((float) $distanceKm) / $kmPerCoin);
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
