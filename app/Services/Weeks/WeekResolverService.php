<?php

namespace App\Services\Weeks;

use App\Models\WeeklyPeriod;
use Illuminate\Support\Carbon;

class WeekResolverService
{
    /**
     * The single source of truth for "what week is it right now". Creates the
     * club's very first period on a fresh install if none exists yet.
     */
    public function activePeriod(): WeeklyPeriod
    {
        return WeeklyPeriod::where('status', 'active')->first()
            ?? $this->bootstrapFirstPeriod();
    }

    /**
     * Resolve the period that contains a given instant, purely by date range
     * (never by a bare week number) — used by the archive and admin lookups.
     */
    public function periodForDate(Carbon $date): ?WeeklyPeriod
    {
        $day = $date->copy()->timezone(config('velotor.timezone'))->toDateString();

        return WeeklyPeriod::whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>', $day)
            ->first();
    }

    public function previousPeriod(WeeklyPeriod $period): ?WeeklyPeriod
    {
        return WeeklyPeriod::whereDate('start_date', '<', $period->start_date->toDateString())
            ->orderByDesc('start_date')
            ->first();
    }

    public function nextPeriod(WeeklyPeriod $period): ?WeeklyPeriod
    {
        return WeeklyPeriod::whereDate('start_date', '>', $period->start_date->toDateString())
            ->orderBy('start_date')
            ->first();
    }

    /**
     * Pure calculation: Monday 00:00 (club timezone) through the following
     * Monday 00:00 (exclusive end). Aligned with the Monday-00:00 close job,
     * so a period's end_date is exactly the instant the scheduler fires.
     * No DB access.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function computeWindowForDate(Carbon $date): array
    {
        $start = $date->copy()
            ->timezone(config('velotor.timezone'))
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();

        return [$start, $start->copy()->addDays(7)];
    }

    /**
     * Given the period that was just closed, create the next one if it
     * doesn't already exist. Resets week_number to 1 and bumps year whenever
     * ISO year and ISO week number are derived from the new Monday. This
     * keeps year/week labels correct around New Year.
     */
    public function ensureNextPeriodExists(WeeklyPeriod $justClosed): WeeklyPeriod
    {
        $start = Carbon::parse($justClosed->end_date, config('velotor.timezone'))->startOfDay();
        $end = $start->copy()->addDays(7);

        $existing = WeeklyPeriod::whereDate('start_date', $start->toDateString())->first();

        if ($existing) {
            return $existing;
        }

        $year = (int) $start->isoWeekYear;
        $weekNumber = (int) $start->isoWeek;

        return WeeklyPeriod::create([
            'year' => $year,
            'week_number' => $weekNumber,
            'title' => "Тиждень {$weekNumber} / {$year}",
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'active',
            'total_distance' => 0,
        ]);
    }

    private function bootstrapFirstPeriod(): WeeklyPeriod
    {
        [$start, $end] = $this->computeWindowForDate(Carbon::now(config('velotor.timezone')));

        $existing = WeeklyPeriod::whereDate('start_date', $start->toDateString())->first();

        if ($existing) {
            return $existing;
        }

        $year = (int) $start->isoWeekYear;
        $weekNumber = (int) $start->isoWeek;

        return WeeklyPeriod::create([
            'year' => $year,
            'week_number' => $weekNumber,
            'title' => "Тиждень {$weekNumber} / {$year}",
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'active',
            'total_distance' => 0,
        ]);
    }
}
