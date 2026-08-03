<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotMessageLog;
use App\Models\Participant;
use App\Models\RideResult;
use App\Services\Stats\StatsRecalculationService;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(WeekResolverService $resolver): Response
    {
        $period = $resolver->activePeriod();
        $previous = $resolver->previousPeriod($period);

        return Inertia::render('Admin/Dashboard', [
            'activePeriod' => [
                'year' => $period->year,
                'week_number' => $period->week_number,
                'title' => $period->title,
                'start_date' => $period->start_date->format('d.m.Y'),
                'end_date' => $period->end_date->copy()->subDay()->format('d.m.Y'),
                'total_distance' => (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km'),
            ],
            'participantsCount' => Participant::count(),
            'rideResultsCount' => RideResult::count(),
            'recentErrorsCount' => BotMessageLog::where('status', 'error')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'rollbackPeriod' => $previous && $previous->status === 'closed' ? [
                'active_period_id' => $period->id,
                'previous_period_id' => $previous->id,
                'week_number' => $previous->week_number,
                'year' => $previous->year,
            ] : null,
        ]);
    }

    public function recalculate(StatsRecalculationService $recalculation): RedirectResponse
    {
        $count = $recalculation->recalculateAll();

        return back()->with('success', "Перераховано {$count} тижнів.");
    }
}
