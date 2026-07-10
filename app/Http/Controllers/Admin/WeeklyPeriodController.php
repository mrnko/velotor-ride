<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeeklyCloseAction;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyPeriodController extends Controller
{
    public function index(): Response
    {
        $periods = WeeklyPeriod::orderByDesc('start_date')->paginate(30);

        return Inertia::render('Admin/WeeklyPeriods/Index', [
            'periods' => $periods->through(fn (WeeklyPeriod $p) => [
                'id' => $p->id,
                'year' => $p->year,
                'week_number' => $p->week_number,
                'title' => $p->title,
                'status' => $p->status,
                'start_date' => $p->start_date->toDateString(),
                'end_date' => $p->end_date->copy()->subDay()->toDateString(),
                'total_distance' => (float) $p->total_distance,
                'report_sent_at' => $p->report_sent_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function close(WeeklyCloseAction $action): RedirectResponse
    {
        $closed = $action->execute(force: true);

        return back()->with(
            $closed ? 'success' : 'error',
            $closed ? "Тиждень {$closed->week_number}/{$closed->year} закрито." : 'Немає активного тижня для закриття.'
        );
    }
}
