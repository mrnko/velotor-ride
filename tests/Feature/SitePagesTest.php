<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    private function seedOneResult(): Participant
    {
        $period = app(WeekResolverService::class)->activePeriod();
        $participant = Participant::factory()->create(['display_name' => 'Тестовий Учасник']);

        RideResult::factory()->create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 42,
        ]);

        return $participant;
    }

    public function test_landing_page_renders(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Landing'));
    }

    public function test_privacy_page_renders(): void
    {
        $this->get('/privacy-policy')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Privacy'));
    }

    public function test_sitemap_renders(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_stat_home_page_renders(): void
    {
        $this->seedOneResult();

        $this->get('/stat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home')
                ->has('weekRankings', 1)
                ->has('allTimeTop10', 1)
                ->has('clubStats')
                ->where('period.week_number', (int) now()->startOfWeek()->isoWeek));
    }

    public function test_stat_home_page_renders_with_no_data(): void
    {
        $this->get('/stat')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_week_archive_page_renders(): void
    {
        $this->seedOneResult();

        $this->get('/stat/weeks')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Week/Archive'));
    }

    public function test_specific_week_page_renders(): void
    {
        $period = WeeklyPeriod::factory()->create(['year' => 2025, 'week_number' => 5, 'status' => 'closed']);

        $this->get("/stat/weeks/{$period->year}/{$period->week_number}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home')->where('period.week_number', 5));
    }

    public function test_specific_week_page_404s_for_unknown_week(): void
    {
        $this->get('/stat/weeks/1999/1')->assertNotFound();
    }

    public function test_year_page_defaults_to_current_year(): void
    {
        $this->seedOneResult();

        $this->get('/stat/year')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Year/Show'));
    }

    public function test_all_time_page_renders(): void
    {
        $this->seedOneResult();

        $this->get('/stat/all-time')->assertOk()->assertInertia(fn (Assert $page) => $page->component('AllTime/Index'));
    }

    public function test_participant_page_renders(): void
    {
        $participant = $this->seedOneResult();

        $this->get("/stat/user/{$participant->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Participant/Show')
                ->where('participant.display_name', 'Тестовий Учасник'));
    }

    public function test_participant_page_404s_for_unknown_slug(): void
    {
        $this->get('/stat/user/does-not-exist')->assertNotFound();
    }

    public function test_rules_page_renders(): void
    {
        $this->get('/stat/rules')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Rules'));
    }
}
