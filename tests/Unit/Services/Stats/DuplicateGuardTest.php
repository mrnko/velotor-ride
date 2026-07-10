<?php

namespace Tests\Unit\Services\Stats;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\DuplicateGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DuplicateGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_same_distance_within_window_is_a_duplicate(): void
    {
        $participant = Participant::factory()->create();
        $period = WeeklyPeriod::factory()->active()->create();

        RideResult::factory()->create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 20,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue((new DuplicateGuard())->isDuplicate($participant, 20));
    }

    public function test_different_distance_within_window_is_not_a_duplicate(): void
    {
        $participant = Participant::factory()->create();
        $period = WeeklyPeriod::factory()->active()->create();

        RideResult::factory()->create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 20,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse((new DuplicateGuard())->isDuplicate($participant, 35));
    }

    public function test_same_distance_outside_window_is_not_a_duplicate(): void
    {
        $participant = Participant::factory()->create();
        $period = WeeklyPeriod::factory()->active()->create();

        RideResult::factory()->create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 20,
            'created_at' => now()->subMinutes(30),
        ]);

        $this->assertFalse((new DuplicateGuard())->isDuplicate($participant, 20));
    }

    public function test_no_prior_result_is_not_a_duplicate(): void
    {
        $participant = Participant::factory()->create();

        $this->assertFalse((new DuplicateGuard())->isDuplicate($participant, 20));
    }
}
