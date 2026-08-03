<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\Setting;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeekResolverService;
use App\Support\Transliterate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_can_edit_participant_name_and_username_and_slug_follows(): void
    {
        $participant = Participant::factory()->create([
            'display_name' => 'Іван Петров',
            'slug' => 'ivan-petrov',
            'telegram_username' => 'old_user',
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/participants/{$participant->id}", [
                'display_name' => 'Петро Іваненко',
                'telegram_username' => '@petro_new',
            ])
            ->assertRedirect('/admin/participants');

        $participant->refresh();
        $this->assertSame('Петро Іваненко', $participant->display_name);
        $this->assertSame('petro_new', $participant->telegram_username); // leading @ stripped
        $this->assertSame(Transliterate::slug('Петро Іваненко'), $participant->slug);
        $this->assertNotSame('ivan-petrov', $participant->slug);
    }

    public function test_editing_participant_requires_a_name(): void
    {
        $participant = Participant::factory()->create(['display_name' => 'Іван Петров']);

        $this->actingAs($this->admin())
            ->put("/admin/participants/{$participant->id}", ['display_name' => ''])
            ->assertSessionHasErrors('display_name');
    }

    public function test_renaming_to_an_existing_name_gets_a_unique_slug(): void
    {
        $base = Transliterate::slug('Олег Сидоренко');
        Participant::factory()->create(['display_name' => 'Олег Сидоренко', 'slug' => $base]);
        $other = Participant::factory()->create(['display_name' => 'Хтось Інший']);

        $this->actingAs($this->admin())
            ->put("/admin/participants/{$other->id}", ['display_name' => 'Олег Сидоренко'])
            ->assertRedirect();

        $other->refresh();
        $this->assertNotSame($base, $other->slug);
        $this->assertStringStartsWith($base, $other->slug);
    }

    public function test_admin_participants_page_flags_possible_duplicates_by_name(): void
    {
        $legacy = Participant::factory()->create(['display_name' => 'Alex Kh']);
        $linked = Participant::factory()->create(['display_name' => 'Alex Khrumalo']);
        $unrelated = Participant::factory()->create(['display_name' => 'Дмитрий Скоробогатов']);

        $this->actingAs($this->admin())->get('/admin/participants')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Participants/Index')
                ->where('participants', function ($participants) use ($legacy, $linked, $unrelated) {
                    $byId = collect($participants)->keyBy('id');

                    return count($byId[$legacy->id]['possible_duplicates']) === 1
                        && $byId[$legacy->id]['possible_duplicates'][0]['id'] === $linked->id
                        && count($byId[$linked->id]['possible_duplicates']) === 1
                        && $byId[$unrelated->id]['possible_duplicates'] === [];
                })
            );
    }

    public function test_admin_can_merge_a_legacy_duplicate_into_the_telegram_linked_participant(): void
    {
        $period = app(WeekResolverService::class)->activePeriod();

        $legacy = Participant::factory()->create([
            'legacy_source' => 'old_site',
            'legacy_id' => 42,
            'telegram_user_id' => 9_000_000_000_000_042,
            'telegram_username' => null,
            'display_name' => 'Данил Ілларіонов',
        ]);
        $telegramLinked = Participant::factory()->create([
            'legacy_source' => null,
            'legacy_id' => null,
            'telegram_user_id' => 123456789,
            'telegram_username' => 'danil_i',
            'display_name' => 'Danil Illarionov',
        ]);

        RideResult::factory()->create([
            'participant_id' => $legacy->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 90,
        ]);
        RideResult::factory()->create([
            'participant_id' => $telegramLinked->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 10,
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/participants/{$telegramLinked->id}/merge", ['into_id' => $legacy->id])
            ->assertRedirect('/admin/participants');

        $this->assertModelMissing($telegramLinked);

        $legacy->refresh();
        $this->assertSame(123456789, $legacy->telegram_user_id);
        $this->assertSame('danil_i', $legacy->telegram_username);
        $this->assertSame(2, RideResult::where('participant_id', $legacy->id)->count());
        $this->assertEquals(100.0, (float) RideResult::where('participant_id', $legacy->id)->sum('distance_km'));
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

    public function test_admin_can_add_a_participant_result_for_current_or_previous_week(): void
    {
        $previous = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 31,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-03',
        ]);
        WeeklyPeriod::factory()->active()->create([
            'year' => 2026,
            'week_number' => 32,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-10',
        ]);
        $participant = Participant::factory()->create(['display_name' => 'Test Rider']);

        $this->actingAs($this->admin())->get('/admin/ride-results/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/RideResults/Create')
                ->has('participants', 1)
                ->has('periods', 2)
            );

        $this->actingAs($this->admin())->post('/admin/ride-results', [
            'participant_id' => $participant->id,
            'weekly_period_id' => $previous->id,
            'distance_km' => 37.5,
        ])->assertRedirect('/admin/ride-results')->assertSessionHas('success');

        $this->assertDatabaseHas('ride_results', [
            'participant_id' => $participant->id,
            'weekly_period_id' => $previous->id,
            'distance_km' => 37.5,
            'source' => 'admin',
        ]);
        $this->assertEquals(37.5, (float) $previous->fresh()->total_distance);
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

    public function test_admin_can_rollback_the_last_closed_week_from_dashboard(): void
    {
        $previous = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 31,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-03',
        ]);
        $active = WeeklyPeriod::factory()->active()->create([
            'year' => 2026,
            'week_number' => 32,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-10',
        ]);

        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('rollbackPeriod.previous_period_id', $previous->id)
                ->where('rollbackPeriod.active_period_id', $active->id)
            );

        $this->actingAs($this->admin())->post('/admin/weekly-periods/rollback', [
            'active_period_id' => $active->id,
            'previous_period_id' => $previous->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('weekly_periods', ['id' => $active->id]);
        $this->assertSame('active', $previous->fresh()->status);
    }

    public function test_admin_can_manually_send_a_week_closing_reminder(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200)]);
        Setting::set('telegram_chat_id', '-100999');

        app(WeekResolverService::class)->activePeriod();

        $this->actingAs($this->admin())->post('/admin/weekly-periods/remind')
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'До закриття тижня'));
    }

    public function test_admin_weekly_periods_page_shows_time_remaining_for_active_period(): void
    {
        $active = app(WeekResolverService::class)->activePeriod();

        $this->actingAs($this->admin())->get('/admin/weekly-periods')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/WeeklyPeriods/Index')
                ->where('activePeriod.week_number', $active->week_number)
                ->has('activePeriod.time_remaining')
            );
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

        $this->assertSame('500', Setting::where('key', 'max_distance_km')->value('value'));
    }

    public function test_admin_can_view_bot_logs(): void
    {
        $this->actingAs($this->admin())->get('/admin/bot-logs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/BotLogs/Index'));
    }

    public function test_admin_can_change_own_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/password', [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-secret-password', $admin->fresh()->password));
    }

    public function test_admin_cannot_change_password_with_wrong_current_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/password', [
            'current_password' => 'wrong',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }
}
