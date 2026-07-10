<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['password' => 'password']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_log_in_and_reach_dashboard(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_admin_can_view_participants(): void
    {
        Participant::factory()->count(3)->create();

        $this->actingAs($this->admin())->get('/admin/participants')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Participants/Index')->has('participants', 3));
    }

    public function test_admin_can_edit_a_ride_result_and_totals_recalculate(): void
    {
        $period = app(WeekResolverService::class)->activePeriod();
        $participant = Participant::factory()->create();
        $result = RideResult::factory()->create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 20,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/ride-results/{$result->id}", ['distance_km' => 55])
            ->assertRedirect('/admin/ride-results');

        $this->assertEquals(55.0, (float) $result->fresh()->distance_km);
        $this->assertEquals(55.0, (float) $period->fresh()->total_distance);
    }

    public function test_admin_can_delete_a_ride_result(): void
    {
        $period = app(WeekResolverService::class)->activePeriod();
        $participant = Participant::factory()->create();
        $result = RideResult::factory()->create([
            'participant_id' => $participant->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 20,
        ]);

        $this->actingAs($this->admin())->delete("/admin/ride-results/{$result->id}")->assertRedirect();

        $this->assertSame(0, RideResult::count());
        $this->assertEquals(0.0, (float) $period->fresh()->total_distance);
    }

    public function test_admin_can_manually_close_the_active_week(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200)]);

        app(WeekResolverService::class)->activePeriod();
        $active = WeeklyPeriod::where('status', 'active')->first();

        $this->actingAs($this->admin())->post('/admin/weekly-periods/close')->assertRedirect();

        $this->assertSame('closed', $active->fresh()->status);
        $this->assertSame(2, WeeklyPeriod::count());
    }

    public function test_admin_can_update_settings(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', [
            'telegram_chat_id' => '-100999',
            'telegram_invite_url' => 'https://t.me/example',
            'timezone' => 'Europe/Kyiv',
            'max_distance_km' => 500,
            'duplicate_window_minutes' => 20,
            'duplicate_distance_delta_km' => 1,
        ])->assertRedirect();

        $this->assertSame('500', \App\Models\Setting::where('key', 'max_distance_km')->value('value'));
    }

    public function test_admin_can_view_bot_logs(): void
    {
        $this->actingAs($this->admin())->get('/admin/bot-logs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/BotLogs/Index'));
    }
}
