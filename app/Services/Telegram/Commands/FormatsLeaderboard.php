<?php

namespace App\Services\Telegram\Commands;

use Illuminate\Support\Collection;

trait FormatsLeaderboard
{
    private function formatLeaderboard(Collection $rows, int $limit): string
    {
        if ($rows->isEmpty()) {
            return 'Поки немає жодного результату.';
        }

        return $rows->take($limit)
            ->map(fn (array $row) => "{$row['rank']}. {$row['participant']->display_name} — ".number_format($row['distance_km'], 0, '.', ' ').' км')
            ->implode("\n");
    }
}
