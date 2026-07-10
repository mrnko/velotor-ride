<?php

namespace App\Console\Commands;

use App\Models\RideResult;
use App\Models\Setting;
use App\Models\WeeklyPeriod;
use App\Services\Telegram\TelegramClient;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Console\Command;

class SendWeekClosingReminder extends Command
{
    protected $signature = 'week:remind {--hours=1 : How many hours are left until the week closes}';

    protected $description = 'Send a Telegram reminder to the club chat that the active week is about to close';

    public function handle(WeekResolverService $resolver, TelegramClient $telegram): int
    {
        $period = $resolver->activePeriod();
        $hours = (int) $this->option('hours');

        $chatId = Setting::get('telegram_chat_id') ?: config('services.telegram.chat_id');

        if (! $chatId) {
            $this->warn('Telegram chat id is not configured — reminder not sent.');

            return self::SUCCESS;
        }

        $distance = (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km');
        $participants = RideResult::where('weekly_period_id', $period->id)
            ->distinct('participant_id')
            ->count('participant_id');

        $result = $telegram->sendMessage($chatId, $this->buildText($period, $hours, $distance, $participants));

        if (! $result['ok']) {
            $this->error("Failed to send reminder: {$result['error']}");

            return self::FAILURE;
        }

        $this->info("Reminder ({$hours}h left) sent for week {$period->week_number}/{$period->year}.");

        return self::SUCCESS;
    }

    private function buildText(WeeklyPeriod $period, int $hours, float $distance, int $participants): string
    {
        $km = number_format($distance, 0, '.', ' ');
        $timeLabel = $hours === 1 ? 'година' : 'години';

        $lines = [];
        $lines[] = "⏰ До закриття тижня {$period->week_number} лишилась {$hours} {$timeLabel}!";
        $lines[] = '';
        $lines[] = $participants > 0
            ? "Поки що в заліку {$km} км від {$participants} учасників."
            : 'Поки що в заліку немає жодного результату — станьте першим!';
        $lines[] = 'Встигніть надіслати свій кілометраж у чат, якщо ще не встигли — бот порахує миттєво і додасть у рейтинг тижня.';

        return implode("\n", $lines);
    }
}
