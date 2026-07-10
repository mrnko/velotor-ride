<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(WeekResolverService $resolver, LeaderboardService $leaderboard): Response
    {
        $period = $resolver->activePeriod();
        $top5 = $leaderboard->forPeriod($period, 5);

        $yearTotal = (float) RideResult::whereIn(
            'weekly_period_id',
            WeeklyPeriod::where('year', $period->year)->pluck('id')
        )->sum('distance_km');

        $activeParticipants = RideResult::where('weekly_period_id', $period->id)
            ->distinct('participant_id')
            ->count('participant_id');

        return Inertia::render('Home', [
            'period' => [
                'year' => $period->year,
                'week_number' => $period->week_number,
                'title' => $period->title,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->copy()->subDay()->toDateString(),
            ],
            'weekTotalDistance' => (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km'),
            'yearTotalDistance' => $yearTotal,
            'activeParticipants' => $activeParticipants,
            'top5' => $top5->map(fn (array $row) => [
                'rank' => $row['rank'],
                'name' => $row['participant']->display_name,
                'participant_id' => $row['participant']->id,
                'distance_km' => $row['distance_km'],
            ]),
            'totalParticipants' => Participant::count(),
        ]);
    }
}
