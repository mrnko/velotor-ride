<?php

namespace App\Services\Torcoins;

use App\Models\Participant;
use App\Models\TorcoinBonus;
use Illuminate\Support\Collection;

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

    public static function balanceForParticipant(Participant|int $participant, float|string $distanceKm, ?iterable $periodIds = null): float
    {
        $participantId = $participant instanceof Participant ? $participant->id : $participant;
        $query = TorcoinBonus::where('participant_id', $participantId);

        if ($periodIds !== null) {
            $query->whereIn('weekly_period_id', $periodIds);
        }

        return round(self::fromDistance($distanceKm) + (float) $query->sum('amount'), 2);
    }

    /** @return Collection<int, float> */
    public static function bonusesByParticipant(?iterable $periodIds = null): Collection
    {
        $query = TorcoinBonus::query();

        if ($periodIds !== null) {
            $query->whereIn('weekly_period_id', $periodIds);
        }

        return $query->selectRaw('participant_id, SUM(amount) as total')
            ->groupBy('participant_id')
            ->pluck('total', 'participant_id')
            ->map(fn ($amount) => (float) $amount);
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
