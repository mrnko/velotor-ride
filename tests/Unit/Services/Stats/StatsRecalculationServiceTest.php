<?php

namespace Tests\Unit\Services\Stats;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\StatsRecalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsRecalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculates_total_distance_from_ride_results(): void
    {
        $period = WeeklyPeriod::factory()->active()->create(['total_distance' => 0]);
        $participant = Participant::factory()->create();

        RideResult::factory()->create(['participant_id' => $participant->id, 'weekly_period_id' => $period->id, 'distance_km' => 30]);
        RideResult::factory()->create(['participant_id' => $participant->id, 'weekly_period_id' => $period->id, 'distance_km' => 15.5]);

        $updated = (new StatsRecalculationService())->recalculatePeriod($period);

        $this->assertEquals(45.5, (float) $updated->total_distance);
    }

    public function test_recalculate_all_fixes_a_stale_total_after_a_manual_delete(): void
    {
        $period = WeeklyPeriod::factory()->active()->create(['total_distance' => 999]);
        $participant = Participant::factory()->create();

        RideResult::factory()->create(['participant_id' => $participant->id, 'weekly_period_id' => $period->id, 'distance_km' => 10]);

        $count = (new StatsRecalculationService())->recalculateAll();

        $this->assertSame(1, $count);
        $this->assertEquals(10.0, (float) $period->fresh()->total_distance);
    }
}
