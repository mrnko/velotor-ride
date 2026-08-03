<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeeklyCloseAction;
use App\Services\Weeks\WeeklyRollbackAction;
use App\Services\Weeks\WeekReminderAction;
use App\Support\DurationFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyPeriodController extends Controller
{
    public function index(WeekReminderAction $reminder): Response
    {
        $periods = WeeklyPeriod::orderByDesc('start_date')->paginate(30);
        $active = WeeklyPeriod::where('status', 'active')->first();

        return Inertia::render('Admin/WeeklyPeriods/Index', [
            'periods' => $periods->through(fn (WeeklyPeriod $p) => [
                'id' => $p->id,
                'year' => $p->year,
                'week_number' => $p->week_number,
                'title' => $p->title,
                'status' => $p->status,
                'start_date' => $p->start_date->format('d.m.Y'),
                'end_date' => $p->end_date->copy()->subDay()->format('d.m.Y'),
                'total_distance' => (float) $p->total_distance,
                'report_sent_at' => $p->report_sent_at?->format('d.m.Y H:i'),
            ]),
            'activePeriod' => $active ? [
                'week_number' => $active->week_number,
                'year' => $active->year,
                'time_remaining' => DurationFormatter::humanize($reminder->minutesRemaining($active)),
            ] : null,
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

    public function remind(WeekReminderAction $action): RedirectResponse
    {
        $result = $action->execute();

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? "Нагадування надіслано в чат для тижня {$result['period']->week_number}/{$result['period']->year}."
                : "Не вдалося надіслати нагадування: {$result['error']}"
        );
    }

    public function rollback(Request $request, WeeklyRollbackAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'active_period_id' => ['required', 'integer'],
            'previous_period_id' => ['required', 'integer'],
        ]);

        $result = $action->execute(
            $validated['active_period_id'],
            $validated['previous_period_id'],
        );

        if ($result['period']) {
            $period = $result['period'];

            return back()->with('success', "Тиждень {$period->week_number}/{$period->year} знову відкрито.");
        }

        $message = match ($result['reason']) {
            'active_period_has_data' => 'Відкат неможливий: у новому поточному тижні вже є результати.',
            'stale_periods' => 'Стан тижнів уже змінився. Оновіть сторінку та спробуйте ще раз.',
            default => 'Не вдалося відкотити останню фіксацію.',
        };

        return back()->with('error', $message);
    }
}
