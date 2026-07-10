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
        $this->assertSame((int) $period->start_date->isoWeek, $period->week_number);
        $this->assertSame((int) $period->start_date->isoWeekYear, $period->year);
    }

    public function test_compute_window_for_date_returns_monday_to_monday(): void
    {
        $service = new WeekResolverService();

        // Wednesday 2026-07-08 -> week starts Monday 2026-07-06.
        [$start, $end] = $service->computeWindowForDate(Carbon::parse('2026-07-08 15:00:00', 'Europe/Kyiv'));

        $this->assertSame('2026-07-06', $start->toDateString());
        $this->assertSame('2026-07-13', $end->toDateString());
    }

    public function test_period_for_date_resolves_by_date_range_not_week_number(): void
    {
        $service = new WeekResolverService();

        $period = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 10,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-09',
            'status' => 'closed',
        ]);

        $found = $service->periodForDate(Carbon::parse('2026-03-05 10:00:00', 'Europe/Kyiv'));

        $this->assertNotNull($found);
        $this->assertSame($period->id, $found->id);
    }

    public function test_year_rollover_resets_week_number_and_bumps_year(): void
    {
        $service = new WeekResolverService();

        // ISO week 53 of 2026: Monday 2026-12-28 -> Monday 2027-01-04.
        $lastWeekOf2026 = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 53,
            'start_date' => '2026-12-28',
            'end_date' => '2027-01-04',
            'status' => 'active',
        ]);

        $next = $service->ensureNextPeriodExists($lastWeekOf2026);

        $this->assertSame(2027, $next->year);
        $this->assertSame(1, $next->week_number);
        $this->assertSame('2027-01-04', $next->start_date->toDateString());
        $this->assertSame('2027-01-11', $next->end_date->toDateString());
    }

    public function test_same_year_rollover_increments_week_number(): void
    {
        $service = new WeekResolverService();

        $week = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 28,
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-13',
            'status' => 'active',
        ]);

        $next = $service->ensureNextPeriodExists($week);

        $this->assertSame(2026, $next->year);
        $this->assertSame(29, $next->week_number);
    }

    public function test_ensure_next_period_is_idempotent(): void
    {
        $service = new WeekResolverService();

        $week = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 28,
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-13',
            'status' => 'closed',
        ]);

        $first = $service->ensureNextPeriodExists($week);
        $second = $service->ensureNextPeriodExists($week);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WeeklyPeriod::where('week_number', 29)->count());
    }
}
