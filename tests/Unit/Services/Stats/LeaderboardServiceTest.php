<?php

namespace Tests\Unit\Services\Stats;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\TorcoinBonus;
use App\Models\WeeklyPeriod;
use App\Services\Stats\LeaderboardService;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_period_ranks_participants_by_total_distance_desc(): void
    {
        $period = WeeklyPeriod::factory()->active()->create();
        $alice = Participant::factory()->create(['display_name' => 'Alice']);
        $bob = Participant::factory()->create(['display_name' => 'Bob']);

        RideResult::factory()->create(['participant_id' => $alice->id, 'weekly_period_id' => $period->id, 'distance_km' => 20]);
        RideResult::factory()->create(['participant_id' => $bob->id, 'weekly_period_id' => $period->id, 'distance_km' => 50]);
        RideResult::factory()->create(['participant_id' => $alice->id, 'weekly_period_id' => $period->id, 'distance_km' => 40]);

        $leaderboard = (new LeaderboardService)->forPeriod($period);

        $this->assertSame(2, $leaderboard->count());
        $this->assertSame('Alice', $leaderboard[0]['participant']->display_name);
        $this->assertEquals(60.0, $leaderboard[0]['distance_km']);
        $this->assertSame(2, $leaderboard[0]['rides_count']);
        $this->assertSame(1, $leaderboard[0]['rank']);
        $this->assertSame('Bob', $leaderboard[1]['participant']->display_name);
        $this->assertSame(2, $leaderboard[1]['rank']);
    }

    public function test_participant_summary_reports_week_year_and_all_time_totals(): void
    {
        $resolver = app(WeekResolverService::class);
        $active = $resolver->activePeriod();

        $participant = Participant::factory()->create();
        RideResult::factory()->create(['participant_id' => $participant->id, 'weekly_period_id' => $active->id, 'distance_km' => 60]);
        RideResult::factory()->create(['participant_id' => $participant->id, 'weekly_period_id' => $active->id, 'distance_km' => 45]);
        TorcoinBonus::create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $active->id,
            'amount' => 0.1,
            'reason' => TorcoinBonus::REASON_FIRST_WEEKLY_RESULT,
        ]);

        $summary = (new LeaderboardService)->participantSummary($participant, $resolver);

        $this->assertEquals(105.0, $summary['current_week_distance']);
        $this->assertEquals(105.0, $summary['year_distance']);
        $this->assertEquals(105.0, $summary['all_time_distance']);
        $this->assertSame(1.15, $summary['torcoins_year']);
        $this->assertSame(1.15, $summary['torcoins_all_time']);
        $this->assertSame(1, $summary['rank_week']);
    }
}
