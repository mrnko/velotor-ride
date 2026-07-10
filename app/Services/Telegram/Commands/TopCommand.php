<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;

class TopCommand implements TelegramCommand
{
    use FormatsLeaderboard;

    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly WeekResolverService $resolver,
    ) {}

    public function handle(Participant $participant): string
    {
        $period = $this->resolver->activePeriod();
        $rows = $this->leaderboard->forPeriod($period, 5);

        return implode("\n", [
            "🏆 Топ-5 тижня {$period->week_number}/{$period->year}",
            '',
            $this->formatLeaderboard($rows, 5),
        ]);
    }
}
