<?php

namespace Database\Factories;

use App\Models\BotReport;
use App\Models\WeeklyPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BotReport>
 */
class BotReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'weekly_period_id' => WeeklyPeriod::factory(),
            'chat_id' => (string) fake()->numberBetween(-999999999, -100000),
            'telegram_message_id' => fake()->numberBetween(1000, 9999999),
            'report_type' => 'weekly_close',
            'content' => fake()->paragraph(),
            'status' => 'sent',
            'sent_at' => now(),
        ];
    }
}
