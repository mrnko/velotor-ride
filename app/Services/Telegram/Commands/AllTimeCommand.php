<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;
use App\Services\Stats\LeaderboardService;

class AllTimeCommand implements TelegramCommand
{
    use FormatsLeaderboard;

    public function __construct(private readonly LeaderboardService $leaderboard) {}

    public function handle(Participant $participant): string
    {
        $rows = $this->leaderboard->allTime();

        return implode("\n", [
            '🌍 Рейтинг за весь час',
            '',
            $this->formatLeaderboard($rows, 15),
        ]);
    }
}
