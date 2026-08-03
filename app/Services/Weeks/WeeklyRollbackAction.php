<?php

namespace App\Services\Weeks;

use App\Models\WeeklyPeriod;
use Illuminate\Support\Facades\DB;

class WeeklyRollbackAction
{
    /**
     * Reopen the most recently closed period and remove the empty period that
     * was created after it. Existing results in the new period are never lost.
     *
     * @return array{period: WeeklyPeriod|null, reason: string|null}
     */
    public function execute(int $expectedActivePeriodId, int $expectedPreviousPeriodId): array
    {
        return DB::transaction(function () use ($expectedActivePeriodId, $expectedPreviousPeriodId): array {
            $active = WeeklyPeriod::whereKey($expectedActivePeriodId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $active) {
                return ['period' => null, 'reason' => 'stale_periods'];
            }

            $previous = WeeklyPeriod::whereKey($expectedPreviousPeriodId)
                ->whereDate('start_date', '<', $active->start_date->toDateString())
                ->orderByDesc('start_date')
                ->lockForUpdate()
                ->first();

            if (! $previous || $previous->status !== 'closed') {
                return ['period' => null, 'reason' => 'stale_periods'];
            }

            $latestPreviousId = WeeklyPeriod::whereDate('start_date', '<', $active->start_date->toDateString())
                ->orderByDesc('start_date')
                ->value('id');

            if ((int) $latestPreviousId !== $previous->id) {
                return ['period' => null, 'reason' => 'stale_periods'];
            }

            if ($active->rideResults()->exists()
                || $active->botReports()->exists()
                || $active->torcoinBonuses()->exists()) {
                return ['period' => null, 'reason' => 'active_period_has_data'];
            }

            $active->delete();

            $previous->update([
                'status' => 'active',
                'report_sent_at' => null,
                'closed_at' => null,
            ]);

            return ['period' => $previous->fresh(), 'reason' => null];
        });
    }
}
