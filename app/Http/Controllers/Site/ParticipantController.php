<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Services\Stats\LeaderboardService;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function show(Participant $participant, LeaderboardService $leaderboard, WeekResolverService $resolver): Response
    {
        $summary = $leaderboard->participantSummary($participant, $resolver);

        $history = RideResult::where('participant_id', $participant->id)
            ->join('weekly_periods', 'weekly_periods.id', '=', 'ride_results.weekly_period_id')
            ->selectRaw('weekly_periods.id, weekly_periods.year, weekly_periods.week_number, weekly_periods.title, weekly_periods.start_date, SUM(ride_results.distance_km) as distance_km, COUNT(*) as rides_count')
            ->groupBy('weekly_periods.id', 'weekly_periods.year', 'weekly_periods.week_number', 'weekly_periods.title', 'weekly_periods.start_date')
            ->orderByDesc('weekly_periods.start_date')
            ->get();

        $bestWeeks = $history->sortByDesc('distance_km')->take(5)->values();

        return Inertia::render('Participant/Show', [
            'participant' => [
                'id' => $participant->id,
                'display_name' => $participant->display_name,
                'telegram_username' => $participant->telegram_username,
                'last_seen_at' => $participant->last_seen_at?->toDateTimeString(),
            ],
            'stats' => [
                'rank_week' => $summary['rank_week'],
                'rank_year' => $summary['rank_year'],
                'current_week_distance' => $summary['current_week_distance'],
                'last_week_distance' => $summary['last_week_distance'],
                'year_distance' => $summary['year_distance'],
                'all_time_distance' => $summary['all_time_distance'],
                'torcoins_year' => $summary['torcoins_year'],
                'torcoins_all_time' => $summary['torcoins_all_time'],
                'km_to_next_coin' => TorcoinCalculator::kmToNextCoin($summary['all_time_distance']),
                'progress_percent' => TorcoinCalculator::progressPercent($summary['all_time_distance']),
            ],
            'history' => $history->map(fn ($row) => [
                'year' => $row->year,
                'week_number' => $row->week_number,
                'title' => $row->title,
                'distance_km' => (float) $row->distance_km,
                'rides_count' => (int) $row->rides_count,
            ]),
            'bestWeeks' => $bestWeeks->map(fn ($row) => [
                'year' => $row->year,
                'week_number' => $row->week_number,
                'title' => $row->title,
                'distance_km' => (float) $row->distance_km,
            ]),
        ]);
    }
}
