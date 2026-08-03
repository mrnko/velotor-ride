<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Models\Setting;
use App\Models\WeeklyPeriod;
use App\Services\Stats\StatsRecalculationService;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RideResultController extends Controller
{
    public function index(): Response
    {
        $results = RideResult::with(['participant:id,display_name', 'weeklyPeriod:id,year,week_number'])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->through(fn (RideResult $r) => [
                'id' => $r->id,
                'participant_name' => $r->participant?->display_name,
                'period_label' => $r->weeklyPeriod ? "{$r->weeklyPeriod->week_number}/{$r->weeklyPeriod->year}" : '—',
                'distance_km' => (float) $r->distance_km,
                'source' => $r->source,
                'raw_message' => $r->raw_message,
                'created_at' => $r->created_at->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Admin/RideResults/Index', [
            'results' => $results,
        ]);
    }

    public function edit(RideResult $rideResult): Response
    {
        $rideResult->load('participant:id,display_name');

        return Inertia::render('Admin/RideResults/Edit', [
            'rideResult' => [
                'id' => $rideResult->id,
                'participant_name' => $rideResult->participant?->display_name,
                'distance_km' => (float) $rideResult->distance_km,
                'raw_message' => $rideResult->raw_message,
                'created_at' => $rideResult->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function create(WeekResolverService $resolver): Response
    {
        $active = $resolver->activePeriod();
        $previous = $resolver->previousPeriod($active);
        $periods = collect([$active, $previous])->filter()->map(fn (WeeklyPeriod $period) => [
            'id' => $period->id,
            'label' => "{$period->week_number}/{$period->year}",
            'status' => $period->status,
        ])->values();

        return Inertia::render('Admin/RideResults/Create', [
            'participants' => Participant::where('is_active', true)
                ->orderBy('display_name')
                ->get(['id', 'display_name']),
            'periods' => $periods,
            'maxDistanceKm' => (float) Setting::get('max_distance_km', config('velotor.max_distance_km', 1000)),
        ]);
    }

    public function store(Request $request, WeekResolverService $resolver, StatsRecalculationService $recalculation): RedirectResponse
    {
        $active = $resolver->activePeriod();
        $previous = $resolver->previousPeriod($active);
        $allowedPeriodIds = collect([$active, $previous])->filter()->pluck('id')->all();
        $maxDistance = (float) Setting::get('max_distance_km', config('velotor.max_distance_km', 1000));

        $validated = $request->validate([
            'participant_id' => [
                'required',
                'integer',
                Rule::exists('participants', 'id')->where('is_active', true),
            ],
            'weekly_period_id' => ['required', 'integer', Rule::in($allowedPeriodIds)],
            'distance_km' => ['required', 'numeric', 'min:0.01', "max:{$maxDistance}"],
        ]);

        $result = RideResult::create([
            ...$validated,
            'raw_message' => 'Додано вручну через адміністративну панель',
            'source' => 'admin',
        ]);

        $recalculation->recalculatePeriod($result->weeklyPeriod);

        return redirect()->route('admin.ride-results.index')->with('success', 'Результат додано.');
    }

    public function update(Request $request, RideResult $rideResult, StatsRecalculationService $recalculation): RedirectResponse
    {
        $validated = $request->validate([
            'distance_km' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ]);

        $rideResult->update(['distance_km' => $validated['distance_km']]);
        $recalculation->recalculatePeriod($rideResult->weeklyPeriod);

        return redirect()->route('admin.ride-results.index')->with('success', 'Результат оновлено.');
    }

    public function destroy(RideResult $rideResult, StatsRecalculationService $recalculation): RedirectResponse
    {
        $period = $rideResult->weeklyPeriod;
        $rideResult->delete();
        $recalculation->recalculatePeriod($period);

        return redirect()->route('admin.ride-results.index')->with('success', 'Результат видалено.');
    }
}
