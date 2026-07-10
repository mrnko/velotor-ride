<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;

class MeCommand implements TelegramCommand
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly WeekResolverService $resolver,
    ) {}

    public function handle(Participant $participant): string
    {
        $s = $this->leaderboard->participantSummary($participant, $this->resolver);

        $rankWeek = $s['rank_week'] ? "#{$s['rank_week']}" : '—';
        $rankYear = $s['rank_year'] ? "#{$s['rank_year']}" : '—';

        return implode("\n", [
            "👤 {$participant->display_name}",
            '',
            'За цей тиждень: '.number_format($s['current_week_distance'], 2, '.', ' ')." км (місце {$rankWeek})",
            'За минулий тиждень: '.number_format($s['last_week_distance'], 2, '.', ' ').' км',
            'За рік: '.number_format($s['year_distance'], 2, '.', ' ')." км (місце {$rankYear})",
            'Усього: '.number_format($s['all_time_distance'], 2, '.', ' ').' км',
            '',
            "Torcoins за рік: {$s['torcoins_year']}",
            "Torcoins за весь час: {$s['torcoins_all_time']}",
        ]);
    }
}
