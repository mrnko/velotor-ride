<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeekResolverService;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeekController extends Controller
{
    public function archive(Request $request, WeekResolverService $resolver, ?int $year = null): Response
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
            'seo' => Seo::make(
                title: "Архів тижнів {$year}",
                description: "Архів щотижневих рейтингів велоклубу «ВелоТОР» за {$year} рік.",
            ),
        ]);
    }
}
