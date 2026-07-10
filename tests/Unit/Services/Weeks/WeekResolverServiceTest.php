<?php

namespace Tests\Unit\Services\Weeks;

use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WeekResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_period_bootstraps_on_fresh_install(): void
    {
        $service = new WeekResolverService();

        $this->assertSame(0, WeeklyPeriod::count());

        $period = $service->activePeriod();

        $this->assertSame(1, WeeklyPeriod::count());
        $this->assertTrue($period->isActive());
        $this->assertSame(1, $period->week_number);
    }

    public function test_compute_window_for_date_returns_sunday_to_sunday(): void
    {
        $service = new WeekResolverService();

        // Wednesday 2026-07-08 -> week should start Sunday 2026-07-05.
        [$start, $end] = $service->computeWindowForDate(Carbon::parse('2026-07-08 15:00:00', 'Europe/Kyiv'));

        $this->assertSame('2026-07-05', $start->toDateString());
        $this->assertSame('2026-07-12', $end->toDateString());
    }

    public function test_period_for_date_resolves_by_date_range_not_week_number(): void
    {
        $service = new WeekResolverService();

        $period = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 10,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-08',
            'status' => 'closed',
        ]);

        $found = $service->periodForDate(Carbon::parse('2026-03-05 10:00:00', 'Europe/Kyiv'));

        $this->assertNotNull($found);
        $this->assertSame($period->id, $found->id);
    }

    public function test_year_rollover_resets_week_number_and_bumps_year(): void
    {
        $service = new WeekResolverService();

        // Last week of 2026: Sunday 2026-12-27 -> Sunday 2027-01-03.
        $lastWeekOf2026 = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 52,
            'start_date' => '2026-12-27',
            'end_date' => '2027-01-03',
            'status' => 'active',
        ]);

        $next = $service->ensureNextPeriodExists($lastWeekOf2026);

        $this->assertSame(2027, $next->year);
        $this->assertSame(1, $next->week_number);
        $this->assertSame('2027-01-03', $next->start_date->toDateString());
        $this->assertSame('2027-01-10', $next->end_date->toDateString());
    }

    public function test_same_year_rollover_increments_week_number(): void
    {
        $service = new WeekResolverService();

        $week = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 27,
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-12',
            'status' => 'active',
        ]);

        $next = $service->ensureNextPeriodExists($week);

        $this->assertSame(2026, $next->year);
        $this->assertSame(28, $next->week_number);
    }

    public function test_ensure_next_period_is_idempotent(): void
    {
        $service = new WeekResolverService();

        $week = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 27,
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-12',
            'status' => 'closed',
        ]);

        $first = $service->ensureNextPeriodExists($week);
        $second = $service->ensureNextPeriodExists($week);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WeeklyPeriod::where('week_number', 28)->count());
    }
}
