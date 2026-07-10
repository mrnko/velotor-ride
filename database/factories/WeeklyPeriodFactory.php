<?php

namespace Database\Factories;

use App\Models\WeeklyPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WeeklyPeriod>
 */
class WeeklyPeriodFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('-1 year', 'now'))
            ->timezone(config('velotor.timezone'))
            ->startOfWeek(Carbon::SUNDAY)
            ->startOfDay();

        $end = $start->copy()->addDays(7);

        return [
            'year' => $start->year,
            'week_number' => (int) $start->weekOfYear,
            'title' => "Тиждень {$start->weekOfYear}/{$start->year}",
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'closed',
            'total_distance' => 0,
            'report_sent_at' => $end,
            'closed_at' => $end,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'report_sent_at' => null,
            'closed_at' => null,
        ]);
    }
}
