<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Services\Stats\LeaderboardService;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use App\Support\Seo;
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

        // Last 12 weeks of this participant's distance for the weekly chart.
        $weeklyChart = $history->take(12)->reverse()->values()->map(fn ($row) => [
            'label' => 'Т'.$row->week_number,
            'title' => $row->title,
            'distance_km' => round((float) $row->distance_km, 1),
        ]);

        $profileUrl = route('stat.participants.show', $participant->slug);

        return Inertia::render('Participant/Show', [
            'participant' => [
                'id' => $participant->id,
                'slug' => $participant->slug,
                'display_name' => $participant->display_name,
                'initials' => $participant->initials(),
                'avatar_url' => $participant->avatar_url,
                'telegram_username' => $participant->telegram_username,
                'last_seen_at' => $participant->last_seen_at?->toDateTimeString(),
                'profile_url' => $profileUrl,
            ],
            'weeklyChart' => $weeklyChart,
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
            'seo' => Seo::make(
                title: $participant->display_name,
                description: "{$participant->display_name} у велоклубі «ВелоТОР»: {$summary['all_time_distance']} км за весь час, {$summary['torcoins_all_time']} Torcoins.",
                canonical: $profileUrl,
                type: 'profile',
                schema: [
                    '@context' => 'https://schema.org',
                    '@type' => 'ProfilePage',
                    'mainEntity' => [
                        '@type' => 'Person',
                        'name' => $participant->display_name,
                        'url' => $profileUrl,
                    ],
                ],
            ),
        ]);
    }
}
