<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\RideResult;
use App\Services\Stats\LeaderboardService;
use App\Services\Torcoins\TorcoinCalculator;
use Inertia\Inertia;
use Inertia\Response;

class AllTimeController extends Controller
{
    public function __invoke(LeaderboardService $leaderboard): Response
    {
        $rows = $leaderboard->allTime();

        $activity = RideResult::selectRaw('participant_id, COUNT(DISTINCT weekly_period_id) as weeks_active, MAX(created_at) as last_activity')
            ->groupBy('participant_id')
            ->get()
            ->keyBy('participant_id');

        $totalDistance = (float) RideResult::sum('distance_km');

        return Inertia::render('AllTime/Index', [
            'totalDistance' => $totalDistance,
            'totalTorcoins' => TorcoinCalculator::fromDistance($totalDistance),
            'participantsCount' => $rows->count(),
            'rankings' => $rows->map(function (array $row) use ($activity) {
                $extra = $activity->get($row['participant']->id);

                return [
                    'rank' => $row['rank'],
                    'participant_id' => $row['participant']->id,
                    'name' => $row['participant']->display_name,
                    'distance_km' => $row['distance_km'],
                    'rides_count' => $row['rides_count'],
                    'weeks_active' => $extra ? (int) $extra->weeks_active : 0,
                    'torcoins' => TorcoinCalculator::fromDistance($row['distance_km']),
                    'last_activity' => $extra?->last_activity,
                ];
            }),
        ]);
    }
}
