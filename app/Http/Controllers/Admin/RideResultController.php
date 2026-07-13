<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RideResult;
use App\Services\Stats\StatsRecalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
