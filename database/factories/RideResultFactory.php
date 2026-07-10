<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RideResult>
 */
class RideResultFactory extends Factory
{
    public function definition(): array
    {
        $distance = fake()->randomFloat(2, 5, 80);

        return [
            'participant_id' => Participant::factory(),
            'weekly_period_id' => WeeklyPeriod::factory(),
            'distance_km' => $distance,
            'raw_message' => "результат {$distance} км",
            'telegram_message_id' => fake()->unique()->numberBetween(1000, 9999999),
            'source' => 'telegram',
        ];
    }
}
