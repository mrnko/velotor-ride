<?php

namespace App\Services\Weeks;

use App\Models\BotReport;
use App\Models\Participant;
use App\Models\RideResult;
use App\Models\Setting;
use App\Models\WeeklyPeriod;
use App\Services\Telegram\TelegramClient;
use App\Services\Torcoins\TorcoinCalculator;
use Illuminate\Support\Facades\DB;

class WeeklyCloseAction
{
    public function __construct(
        private readonly WeekResolverService $resolver,
        private readonly TelegramClient $telegram,
    ) {}

    /**
     * Close the currently active weekly period, send the report, and open
     * the next one.
     *
     * Idempotent against the scheduler firing twice: the first invocation
     * closes the active period and immediately opens the next one, whose
     * end_date is a full week away. A second invocation moments later finds
     * that new period still active — closing it too would silently compress
     * two weeks into one, so unless $force is set, we refuse to close a
     * period before its end_date has actually arrived. `report_sent_at`
     * being already set is an additional guard for the (same) period.
     *
     * $force skips only the "hasn't ended yet" check, for the admin's
     * manual "close now" action — it never re-sends a report for a period
     * that already has one.
     */
    public function execute(bool $force = false): ?WeeklyPeriod
    {
        return DB::transaction(function () use ($force) {
            $period = WeeklyPeriod::where('status', 'active')->lockForUpdate()->first();

            if (! $period || $period->status !== 'active' || $period->report_sent_at !== null) {
                return null;
            }

            if (! $force && now(config('velotor.timezone'))->lt($period->end_date)) {
                return null;
            }

            $totalDistance = (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km');
            $top5 = $this->topParticipants($period, 5);

            $reportText = $this->buildReportText($period, $totalDistance, $top5);
            $chatId = Setting::get('telegram_chat_id') ?: config('services.telegram.chat_id');

            $sendResult = $chatId
                ? $this->telegram->sendMessage($chatId, $reportText)
                : ['ok' => false, 'message_id' => null, 'error' => 'Telegram chat id is not configured'];

            BotReport::create([
                'weekly_period_id' => $period->id,
                'chat_id' => (string) ($chatId ?? ''),
                'telegram_message_id' => $sendResult['message_id'],
                'report_type' => 'weekly_close',
                'content' => $reportText,
                'status' => $sendResult['ok'] ? 'sent' : 'failed',
                'error_message' => $sendResult['error'],
                'sent_at' => $sendResult['ok'] ? now() : null,
            ]);

            $period->update([
                'status' => 'closed',
                'total_distance' => $totalDistance,
                'report_sent_at' => now(),
                'closed_at' => now(),
            ]);

            $this->resolver->ensureNextPeriodExists($period);

            return $period->fresh();
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{participant: Participant, distance: float}>
     */
    private function topParticipants(WeeklyPeriod $period, int $limit): \Illuminate\Support\Collection
    {
        return RideResult::query()
            ->selectRaw('participant_id, SUM(distance_km) as total_distance')
            ->where('weekly_period_id', $period->id)
            ->groupBy('participant_id')
            ->orderByDesc('total_distance')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'participant' => Participant::find($row->participant_id),
                'distance' => (float) $row->total_distance,
            ]);
    }

    private function buildReportText(WeeklyPeriod $period, float $totalDistance, \Illuminate\Support\Collection $top5): string
    {
        $participantsCount = RideResult::where('weekly_period_id', $period->id)
            ->distinct('participant_id')
            ->count('participant_id');

        $lines = [];
        $lines[] = "🚴 -= ТИЖДЕНЬ {$period->week_number} | {$period->year} =- закінчився!";
        $lines[] = '';
        $lines[] = 'Дякуємо всім за участь! Ви найкращі! 🙌';
        $lines[] = '';
        $lines[] = 'Загальна дистанція клубу за тиждень: '.number_format($totalDistance, 0, '.', ' ').' км';
        $lines[] = "Учасників цього тижня: {$participantsCount}";
        $lines[] = '';

        if ($top5->isNotEmpty()) {
            $lines[] = '🏆 ТОП-5 найактивніших учасників тижня:';
            $lines[] = '';
            foreach ($top5->values() as $index => $row) {
                $name = $row['participant']?->display_name ?? 'Учасник';
                $km = number_format($row['distance'], 0, '.', ' ');
                $coins = number_format(TorcoinCalculator::fromDistance($row['distance']), 2, '.', ' ');
                $lines[] = ($index + 1).". {$name}, з результатом — {$km} км ({$coins} Torcoins)";
            }
            $lines[] = '';
        }

        $lines[] = '🚀 Новий тиждень вже стартував — не забувай надсилати свій кілометраж!';
        $lines[] = '';
        $lines[] = '➡️ Повна статистика — '.route('stat.home').' ⬅️';

        return implode("\n", $lines);
    }
}
