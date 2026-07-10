<?php

namespace Tests\Unit\Services\Weeks;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeeklyCloseAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeeklyCloseActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200),
        ]);

        config(['services.telegram.chat_id' => '-100123456']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_closes_active_period_and_creates_next(): void
    {
        $period = WeeklyPeriod::factory()->active()->create([
            'year' => 2026,
            'week_number' => 1,
            'start_date' => '2026-01-04',
            'end_date' => '2026-01-11',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-01-11 00:00:00', 'Europe/Kyiv'));

        $alice = Participant::factory()->create(['display_name' => 'Alice']);
        $bob = Participant::factory()->create(['display_name' => 'Bob']);

        RideResult::factory()->create(['participant_id' => $alice->id, 'weekly_period_id' => $period->id, 'distance_km' => 60]);
        RideResult::factory()->create(['participant_id' => $bob->id, 'weekly_period_id' => $period->id, 'distance_km' => 40]);

        $action = app(WeeklyCloseAction::class);
        $closed = $action->execute();

        $this->assertNotNull($closed);
        $this->assertSame('closed', $closed->status);
        $this->assertEquals(100.0, (float) $closed->total_distance);
        $this->assertNotNull($closed->report_sent_at);
        $this->assertNotNull($closed->closed_at);

        $this->assertSame(2, WeeklyPeriod::count());
        $next = WeeklyPeriod::where('status', 'active')->first();
        $this->assertNotNull($next);
        $this->assertSame(2, $next->week_number);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Alice')
            && str_contains($request['text'], 'Bob'));
    }

    public function test_second_invocation_right_after_the_first_is_a_no_op(): void
    {
        WeeklyPeriod::factory()->active()->create([
            'year' => 2026, 'week_number' => 1, 'start_date' => '2026-01-04', 'end_date' => '2026-01-11',
        ]);

        // Freeze "now" at exactly the moment the scheduler fires (Sunday 00:00).
        Carbon::setTestNow(Carbon::parse('2026-01-11 00:00:00', 'Europe/Kyiv'));

        $action = app(WeeklyCloseAction::class);

        $first = $action->execute();
        // A second, near-instant invocation (e.g. scheduler firing twice) sees
        // the newly-opened week 2, whose end_date is still 7 days away.
        $second = $action->execute();

        $this->assertNotNull($first);
        $this->assertNull($second);

        $this->assertSame(2, WeeklyPeriod::count());
        Http::assertSentCount(1);
    }

    public function test_close_refuses_to_act_before_the_period_has_ended(): void
    {
        WeeklyPeriod::factory()->active()->create([
            'year' => 2026, 'week_number' => 1, 'start_date' => '2026-01-04', 'end_date' => '2026-01-11',
        ]);

        // "Now" is mid-week, well before end_date.
        Carbon::setTestNow(Carbon::parse('2026-01-06 12:00:00', 'Europe/Kyiv'));

        $action = app(WeeklyCloseAction::class);

        $this->assertNull($action->execute());
        $this->assertSame(1, WeeklyPeriod::count());
        Http::assertNothingSent();
    }

    public function test_force_closes_a_period_early_for_manual_admin_use(): void
    {
        WeeklyPeriod::factory()->active()->create([
            'year' => 2026, 'week_number' => 1, 'start_date' => '2026-01-04', 'end_date' => '2026-01-11',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-01-06 12:00:00', 'Europe/Kyiv'));

        $action = app(WeeklyCloseAction::class);
        $closed = $action->execute(force: true);

        $this->assertNotNull($closed);
        $this->assertSame('closed', $closed->status);
    }

    public function test_no_active_period_is_a_no_op(): void
    {
        $action = app(WeeklyCloseAction::class);

        $this->assertNull($action->execute());
        Http::assertNothingSent();
    }
}
