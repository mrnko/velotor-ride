<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\LeaderboardService;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class YearController extends Controller
{
    public function show(LeaderboardService $leaderboard, WeekResolverService $resolver, ?int $year = null): Response
    {
        // Years that never got a single ride result (e.g. placeholder history
        // rows) are hidden from the selector — except the currently active
        // year, which must stay selectable even before its first result.
        $activeYear = $resolver->activePeriod()->year;

        $availableYears = WeeklyPeriod::query()
            ->where(function ($query) use ($activeYear) {
                $query->whereIn('id', RideResult::select('weekly_period_id')->distinct())
                    ->orWhere('year', $activeYear);
            })
            ->selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');

        $year ??= $activeYear;

        $rows = $leaderboard->forYear((int) $year);
        $periodIds = WeeklyPeriod::where('year', $year)->pluck('id');
        $totalDistance = (float) RideResult::whereIn('weekly_period_id', $periodIds)->sum('distance_km');
        $bonuses = TorcoinCalculator::bonusesByParticipant($periodIds);

        return Inertia::render('Year/Show', [
            'year' => (int) $year,
            'availableYears' => $availableYears,
            'totalDistance' => $totalDistance,
            'participantsCount' => $rows->count(),
            'totalTorcoins' => round(TorcoinCalculator::fromDistance($totalDistance) + $bonuses->sum(), 2),
            'rankings' => $rows->map(fn (array $row) => [
                'rank' => $row['rank'],
                'participant_id' => $row['participant']->id,
                'slug' => $row['participant']->slug,
                'avatar_url' => $row['participant']->avatar_url,
                'initials' => $row['participant']->initials(),
                'name' => $row['participant']->display_name,
                'distance_km' => $row['distance_km'],
                'rides_count' => $row['rides_count'],
                'torcoins' => round(TorcoinCalculator::fromDistance($row['distance_km']) + $bonuses->get($row['participant']->id, 0), 2),
            ]),
            'seo' => Seo::make(
                title: "Річний рейтинг {$year}",
                description: "Річний рейтинг велоклубу «ВелоТОР» за {$year} рік: загальний кілометраж, учасники та Torcoins.",
            ),
        ]);
    }
}
