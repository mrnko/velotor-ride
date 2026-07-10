<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $firstSeen = fake()->dateTimeBetween('-1 year', '-1 month');

        return [
            'telegram_user_id' => fake()->unique()->numberBetween(100000000, 999999999),
            'telegram_username' => fake()->boolean(70) ? Str::lower(fake()->userName()) : null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => "{$firstName} {$lastName}",
            'is_active' => true,
            'first_seen_at' => $firstSeen,
            'last_seen_at' => fake()->dateTimeBetween($firstSeen, 'now'),
        ];
    }
}
