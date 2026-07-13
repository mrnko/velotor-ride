<?php

namespace App\Support;

class DurationFormatter
{
    /**
     * Human-readable Ukrainian duration from a whole number of minutes,
     * e.g. 1500 -> "1 день 1 година", 90 -> "1 година 30 хвилин".
     * Minutes are dropped once the duration spans whole days — not useful
     * precision at that scale.
     */
    public static function humanize(int $totalMinutes): string
    {
        $totalMinutes = max(0, $totalMinutes);

        $days = intdiv($totalMinutes, 1440);
        $hours = intdiv($totalMinutes % 1440, 60);
        $minutes = $totalMinutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} ".self::pluralize($days, 'день', 'дні', 'днів');
        }

        if ($hours > 0) {
            $parts[] = "{$hours} ".self::pluralize($hours, 'година', 'години', 'годин');
        }

        if ($minutes > 0 && $days === 0) {
            $parts[] = "{$minutes} ".self::pluralize($minutes, 'хвилина', 'хвилини', 'хвилин');
        }

        return $parts === [] ? 'менше хвилини' : implode(' ', $parts);
    }

    private static function pluralize(int $count, string $one, string $few, string $many): string
    {
        $count = abs($count) % 100;
        $lastDigit = $count % 10;

        if ($count >= 11 && $count <= 14) {
            return $many;
        }

        return match (true) {
            $lastDigit === 1 => $one,
            $lastDigit >= 2 && $lastDigit <= 4 => $few,
            default => $many,
        };
    }
}
