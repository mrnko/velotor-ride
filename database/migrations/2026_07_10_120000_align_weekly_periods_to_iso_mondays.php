<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weekly_periods')) {
            return;
        }

        $timezone = config('velotor.timezone', 'Europe/Kyiv');

        DB::transaction(function () use ($timezone) {
            $periods = DB::table('weekly_periods')->orderBy('start_date')->get();
            $sundayPeriods = $periods->filter(
                fn ($period) => Carbon::parse($period->start_date, $timezone)->isSunday()
            );

            if ($sundayPeriods->isEmpty()) {
                return;
            }

            // Move old keys out of the normal year range first to avoid
            // transient unique(year, week_number) collisions.
            foreach ($sundayPeriods as $period) {
                DB::table('weekly_periods')->where('id', $period->id)->update([
                    'year' => 10000 + (int) $period->year,
                ]);
            }

            foreach ($sundayPeriods as $period) {
                $start = Carbon::parse($period->start_date, $timezone)->addDay()->startOfDay();
                $end = Carbon::parse($period->end_date, $timezone)->addDay()->startOfDay();
                $year = (int) $start->isoWeekYear;
                $weekNumber = (int) $start->isoWeek;

                DB::table('weekly_periods')->where('id', $period->id)->update([
                    'year' => $year,
                    'week_number' => $weekNumber,
                    'title' => "Тиждень {$weekNumber} / {$year}",
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ]);
            }

            $ranges = DB::table('weekly_periods')->orderBy('start_date')->get()->map(fn ($period) => [
                'id' => $period->id,
                'start' => Carbon::parse($period->start_date, $timezone)->startOfDay(),
                'end' => Carbon::parse($period->end_date, $timezone)->startOfDay(),
            ]);

            if (Schema::hasTable('ride_results')) {
                DB::table('ride_results')->select(['id', 'weekly_period_id', 'created_at'])->orderBy('id')->chunkById(500, function ($rides) use ($ranges, $timezone) {
                    foreach ($rides as $ride) {
                        $riddenAt = Carbon::parse($ride->created_at, $timezone);
                        $target = $ranges->first(fn (array $range) => $riddenAt->gte($range['start']) && $riddenAt->lt($range['end']));

                        if ($target && (int) $ride->weekly_period_id !== (int) $target['id']) {
                            DB::table('ride_results')->where('id', $ride->id)->update(['weekly_period_id' => $target['id']]);
                        }
                    }
                });

                foreach ($ranges as $range) {
                    $total = (float) DB::table('ride_results')->where('weekly_period_id', $range['id'])->sum('distance_km');
                    DB::table('weekly_periods')->where('id', $range['id'])->update(['total_distance' => $total]);
                }
            }
        });
    }

    public function down(): void
    {
        // Existing ride history must not be silently moved back to legacy
        // Sunday windows. Restoring a database backup is the safe rollback.
    }
};
