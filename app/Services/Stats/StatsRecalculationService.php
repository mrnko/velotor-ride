<?php

namespace App\Services\Stats;

use App\Models\RideResult;
use App\Models\WeeklyPeriod;

class StatsRecalculationService
{
    /**
     * Recompute every period's total_distance fresh from ride_results.
     * Used by the admin "recalculate stats" action after edits/deletes.
     */
    public function recalculateAll(): int
    {
        $count = 0;

        WeeklyPeriod::query()->each(function (WeeklyPeriod $period) use (&$count) {
            $this->recalculatePeriod($period);
            $count++;
        });

        return $count;
    }

    public function recalculatePeriod(WeeklyPeriod $period): WeeklyPeriod
    {
        $total = (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km');

        $period->update(['total_distance' => $total]);

        return $period->fresh();
    }
}
