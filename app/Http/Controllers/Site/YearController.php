<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\LeaderboardService;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use Inertia\Inertia;
use Inertia\Response;

class YearController extends Controller
{
    public function show(LeaderboardService $leaderboard, WeekResolverService $resolver, ?int $year = null): Response
    {
        $availableYears = WeeklyPeriod::query()
            ->selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');

        $year ??= $resolver->activePeriod()->year;

        $rows = $leaderboard->forYear((int) $year);
        $periodIds = WeeklyPeriod::where('year', $year)->pluck('id');
        $totalDistance = (float) RideResult::whereIn('weekly_period_id', $periodIds)->sum('distance_km');

        return Inertia::render('Year/Show', [
            'year' => (int) $year,
            'availableYears' => $availableYears,
            'totalDistance' => $totalDistance,
            'participantsCount' => $rows->count(),
            'totalTorcoins' => TorcoinCalculator::fromDistance($totalDistance),
            'rankings' => $rows->map(fn (array $row) => [
                'rank' => $row['rank'],
                'participant_id' => $row['participant']->id,
                'name' => $row['participant']->display_name,
                'distance_km' => $row['distance_km'],
                'rides_count' => $row['rides_count'],
                'torcoins' => TorcoinCalculator::fromDistance($row['distance_km']),
            ]),
        ]);
    }
}
