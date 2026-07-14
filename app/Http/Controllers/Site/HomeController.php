<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\LeaderboardService;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        return $this->render($resolver->activePeriod(), $resolver, $leaderboard);
    }

    public function show(int $year, int $weekNumber, WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        $period = WeeklyPeriod::where('year', $year)->where('week_number', $weekNumber)->firstOrFail();

        return $this->render($period, $resolver, $leaderboard);
    }

    private function render(WeeklyPeriod $period, WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        $weekRows = $leaderboard->forPeriod($period);
        $allTimeRows = $leaderboard->allTime();
        $weekBonuses = TorcoinCalculator::bonusesByParticipant([$period->id]);
        $allTimeBonuses = TorcoinCalculator::bonusesByParticipant();

        $activeParticipants = $weekRows->count();
        $weekTorcoins = $weekRows->sum(fn (array $row) => round(
            TorcoinCalculator::fromDistance($row['distance_km']) + $weekBonuses->get($row['participant']->id, 0),
            2
        ));
        $weekRidesCount = RideResult::where('weekly_period_id', $period->id)->count();

        // Last 12 weeks of club-wide distance for the weekly chart (always the
        // latest weeks, regardless of which week is being viewed).
        $recentPeriods = WeeklyPeriod::orderByDesc('start_date')->limit(12)->get()->reverse()->values();
        $recentTotals = RideResult::whereIn('weekly_period_id', $recentPeriods->pluck('id'))
            ->selectRaw('weekly_period_id, SUM(distance_km) as total')
            ->groupBy('weekly_period_id')
            ->pluck('total', 'weekly_period_id');
        $recentActive = RideResult::whereIn('weekly_period_id', $recentPeriods->pluck('id'))
            ->selectRaw('weekly_period_id, COUNT(DISTINCT participant_id) as total')
            ->groupBy('weekly_period_id')
            ->pluck('total', 'weekly_period_id');

        $weeklyChart = $recentPeriods->map(fn (WeeklyPeriod $p) => [
            'label' => 'Т'.$p->week_number,
            'title' => $p->title,
            'distance_km' => round((float) ($recentTotals[$p->id] ?? 0), 1),
            'active_participants' => (int) ($recentActive[$p->id] ?? 0),
        ]);

        $mapRanking = fn (array $row, $bonuses) => [
            'rank' => $row['rank'],
            'name' => $row['participant']->display_name,
            'participant_id' => $row['participant']->id,
            'slug' => $row['participant']->slug,
            'avatar_url' => $row['participant']->avatar_url,
            'initials' => $row['participant']->initials(),
            'distance_km' => $row['distance_km'],
            'rides_count' => $row['rides_count'],
            'torcoins' => round(TorcoinCalculator::fromDistance($row['distance_km']) + $bonuses->get($row['participant']->id, 0), 2),
        ];

        $previous = $resolver->previousPeriod($period);
        $next = $resolver->nextPeriod($period);
        $allTimeTorcoins = $allTimeRows->sum(fn (array $row) => round(
            TorcoinCalculator::fromDistance($row['distance_km']) + $allTimeBonuses->get($row['participant']->id, 0),
            2
        ));

        return Inertia::render('Home', [
            'weeklyChart' => $weeklyChart,
            'period' => [
                'year' => $period->year,
                'week_number' => $period->week_number,
                'title' => $period->title,
                'status' => $period->status,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->copy()->subDay()->toDateString(),
            ],
            'previous' => $previous ? ['year' => $previous->year, 'week_number' => $previous->week_number] : null,
            'next' => $next ? ['year' => $next->year, 'week_number' => $next->week_number] : null,
            'weekTotalDistance' => (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km'),
            'weekTorcoins' => $weekTorcoins,
            'weekRidesCount' => $weekRidesCount,
            'activeParticipants' => $activeParticipants,
            'leader' => $weekRows->isNotEmpty() ? $mapRanking($weekRows->first(), $weekBonuses) : null,
            'weekRankings' => $weekRows->map(fn (array $row) => $mapRanking($row, $weekBonuses)),
            'allTimeTop10' => $allTimeRows->take(10)->values()->map(fn (array $row) => $mapRanking($row, $allTimeBonuses)),
            'totalParticipants' => Participant::count(),
            'clubStats' => [
                'total_distance' => (float) RideResult::sum('distance_km'),
                'total_rides' => RideResult::count(),
                'total_torcoins' => $allTimeTorcoins,
                'weeks_count' => WeeklyPeriod::count(),
            ],
            'seo' => Seo::make(
                title: "Статистика велоклубу — тиждень {$period->week_number}, {$period->year}",
                description: "Статистика велоклубу «ВелоТОР» за {$period->week_number}-й тиждень {$period->year} року: лідер, кілометраж, Torcoins, усі учасники та топ-10 за весь час.",
            ),
        ]);
    }
}
