<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;

class WeekCommand implements TelegramCommand
{
    use FormatsLeaderboard;

    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly WeekResolverService $resolver,
    ) {}

    public function handle(Participant $participant): string
    {
        $period = $this->resolver->activePeriod();
        $rows = $this->leaderboard->forPeriod($period);

        return implode("\n", [
            "📅 Рейтинг тижня {$period->week_number}/{$period->year}",
            "{$period->start_date->format('d.m')} – {$period->end_date->copy()->subDay()->format('d.m')}",
            '',
            $this->formatLeaderboard($rows, 15),
        ]);
    }
}
