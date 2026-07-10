<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;

class YearCommand implements TelegramCommand
{
    use FormatsLeaderboard;

    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly WeekResolverService $resolver,
    ) {}

    public function handle(Participant $participant): string
    {
        $year = $this->resolver->activePeriod()->year;
        $rows = $this->leaderboard->forYear($year);

        return implode("\n", [
            "📆 Рейтинг {$year} року",
            '',
            $this->formatLeaderboard($rows, 15),
        ]);
    }
}
