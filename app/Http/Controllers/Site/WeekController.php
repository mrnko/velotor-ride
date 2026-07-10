<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeekController extends Controller
{
    public function current(WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        $period = $resolver->activePeriod();

        return $this->renderWeek($period, $resolver, $leaderboard);
    }

    public function archive(Request $request, ?int $year = null): Response
    {
        $availableYears = WeeklyPeriod::query()
            ->selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');

        $year ??= $availableYears->first() ?? now()->year;

        $periods = WeeklyPeriod::where('year', $year)
            ->orderByDesc('week_number')
            ->get();

        $participantCounts = RideResult::whereIn('weekly_period_id', $periods->pluck('id'))
            ->selectRaw('weekly_period_id, COUNT(DISTINCT participant_id) as participants_count')
            ->groupBy('weekly_period_id')
            ->pluck('participants_count', 'weekly_period_id');

        return Inertia::render('Week/Archive', [
            'year' => (int) $year,
            'availableYears' => $availableYears,
            'weeks' => $periods->map(fn (WeeklyPeriod $p) => [
                'year' => $p->year,
                'week_number' => $p->week_number,
                'title' => $p->title,
                'status' => $p->status,
                'start_date' => $p->start_date->toDateString(),
                'end_date' => $p->end_date->copy()->subDay()->toDateString(),
                'total_distance' => (float) $p->total_distance,
                'participants_count' => (int) ($participantCounts[$p->id] ?? 0),
            ]),
        ]);
    }

    public function show(int $year, int $weekNumber, WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        $period = WeeklyPeriod::where('year', $year)->where('week_number', $weekNumber)->firstOrFail();

        return $this->renderWeek($period, $resolver, $leaderboard);
    }

    private function renderWeek(WeeklyPeriod $period, WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        $rows = $leaderboard->forPeriod($period);
        $previous = $resolver->previousPeriod($period);
        $next = $resolver->nextPeriod($period);

        return Inertia::render('Week/Show', [
            'period' => [
                'year' => $period->year,
                'week_number' => $period->week_number,
                'title' => $period->title,
                'status' => $period->status,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->copy()->subDay()->toDateString(),
                'total_distance' => (float) ($period->isActive()
                    ? RideResult::where('weekly_period_id', $period->id)->sum('distance_km')
                    : $period->total_distance),
            ],
            'participantsCount' => $rows->count(),
            'rankings' => $rows->map(fn (array $row) => [
                'rank' => $row['rank'],
                'participant_id' => $row['participant']->id,
                'name' => $row['participant']->display_name,
                'distance_km' => $row['distance_km'],
                'rides_count' => $row['rides_count'],
            ]),
            'previous' => $previous ? ['year' => $previous->year, 'week_number' => $previous->week_number] : null,
            'next' => $next ? ['year' => $next->year, 'week_number' => $next->week_number] : null,
        ]);
    }
}
