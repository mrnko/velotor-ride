<?php

namespace App\Console\Commands;

use App\Services\Weeks\WeeklyCloseAction;
use Illuminate\Console\Command;

class CloseWeeklyPeriod extends Command
{
    protected $signature = 'week:close';

    protected $description = 'Close the active weekly period, send the Telegram report, and open the next week';

    public function handle(WeeklyCloseAction $action): int
    {
        $closed = $action->execute();

        if (! $closed) {
            $this->info('Nothing to close: no active period, or it was already closed (idempotent no-op).');

            return self::SUCCESS;
        }

        $this->info("Closed week {$closed->week_number}/{$closed->year} — {$closed->total_distance} km total.");

        return self::SUCCESS;
    }
}
