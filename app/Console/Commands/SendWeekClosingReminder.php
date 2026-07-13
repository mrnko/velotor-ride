<?php

namespace App\Console\Commands;

use App\Services\Weeks\WeekReminderAction;
use Illuminate\Console\Command;

class SendWeekClosingReminder extends Command
{
    protected $signature = 'week:remind {--hours=1 : How many hours are left until the week closes}';

    protected $description = 'Send a Telegram reminder to the club chat that the active week is about to close';

    public function handle(WeekReminderAction $action): int
    {
        $result = $action->execute((int) $this->option('hours'));

        if (! $result['ok']) {
            if ($result['error'] === 'Telegram chat id is not configured') {
                $this->warn('Telegram chat id is not configured — reminder not sent.');

                return self::SUCCESS;
            }

            $this->error("Failed to send reminder: {$result['error']}");

            return self::FAILURE;
        }

        $this->info("Reminder sent for week {$result['period']->week_number}/{$result['period']->year}.");

        return self::SUCCESS;
    }
}
