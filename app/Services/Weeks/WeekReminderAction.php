<?php

namespace App\Services\Weeks;

use App\Models\RideResult;
use App\Models\Setting;
use App\Models\WeeklyPeriod;
use App\Services\Telegram\TelegramClient;
use App\Support\DurationFormatter;

class WeekReminderAction
{
    public function __construct(
        private readonly WeekResolverService $resolver,
        private readonly TelegramClient $telegram,
    ) {}

    /**
     * Send a "week is closing soon" reminder to the club chat for the
     * currently active period. Pass $hours for the fixed lead-time
     * reminders the scheduler fires (2h/1h before Monday 00:00); omit it
     * (e.g. the admin's manual "send now" button) to report the period's
     * actual remaining time instead.
     *
     * @return array{ok: bool, error: string|null, period: WeeklyPeriod, minutes_remaining: int}
     */
    public function execute(?int $hours = null): array
    {
        $period = $this->resolver->activePeriod();
        $minutesRemaining = $hours !== null ? $hours * 60 : $this->minutesRemaining($period);

        $chatId = Setting::get('telegram_chat_id') ?: config('services.telegram.chat_id');

        if (! $chatId) {
            return ['ok' => false, 'error' => 'Telegram chat id is not configured', 'period' => $period, 'minutes_remaining' => $minutesRemaining];
        }

        $distance = (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km');
        $participants = RideResult::where('weekly_period_id', $period->id)
            ->distinct('participant_id')
            ->count('participant_id');

        $result = $this->telegram->sendMessage($chatId, $this->buildText($period, $minutesRemaining, $distance, $participants));

        return ['ok' => $result['ok'], 'error' => $result['error'], 'period' => $period, 'minutes_remaining' => $minutesRemaining];
    }

    public function minutesRemaining(WeeklyPeriod $period): int
    {
        $now = now(config('velotor.timezone'));

        return max(0, intdiv($period->end_date->getTimestamp() - $now->getTimestamp(), 60));
    }

    private function buildText(WeeklyPeriod $period, int $minutesRemaining, float $distance, int $participants): string
    {
        $km = number_format($distance, 0, '.', ' ');
        $timeLeft = DurationFormatter::humanize($minutesRemaining);

        $lines = [];
        $lines[] = "⏰ До закриття тижня {$period->week_number} лишилось: {$timeLeft}!";
        $lines[] = '';
        $lines[] = $participants > 0
            ? "Поки що в заліку {$km} км від {$participants} учасників."
            : 'Поки що в заліку немає жодного результату — станьте першим!';
        $lines[] = 'Встигніть надіслати свій кілометраж у чат, якщо ще не встигли — бот порахує миттєво і додасть у рейтинг тижня.';

        return implode("\n", $lines);
    }
}
