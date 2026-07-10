<?php

namespace App\Services\Stats;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\Setting;

class DuplicateGuard
{
    /**
     * True if the participant's most recent submission is within the
     * configured time window and close enough in distance to be almost
     * certainly the same ride sent twice, rather than a genuinely new one.
     */
    public function isDuplicate(Participant $participant, float $distanceKm): bool
    {
        $windowMinutes = (int) Setting::get('duplicate_window_minutes', config('velotor.duplicate_window_minutes', 15));
        $deltaKm = (float) Setting::get('duplicate_distance_delta_km', config('velotor.duplicate_distance_delta_km', 0.5));

        $recent = RideResult::where('participant_id', $participant->id)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->orderByDesc('created_at')
            ->first();

        if (! $recent) {
            return false;
        }

        return abs((float) $recent->distance_km - $distanceKm) <= $deltaKm;
    }
}
