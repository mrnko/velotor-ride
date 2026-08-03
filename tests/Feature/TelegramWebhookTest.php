<?php

namespace Tests\Feature;

use App\Models\BotMessageLog;
use App\Models\Participant;
use App\Models\RideResult;
use App\Models\TorcoinBonus;
use App\Models\WeeklyPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);
    }

    private function update(string $text, array $overrides = []): array
    {
        return array_merge([
            'update_id' => random_int(1, 999999),
            'message' => array_merge([
                'message_id' => random_int(1, 999999),
                'from' => [
                    'id' => 555111,
                    'is_bot' => false,
                    'first_name' => 'Олексій',
                    'last_name' => 'Мироненко',
                    'username' => 'oleksii',
                ],
                'chat' => ['id' => -100123456, 'type' => 'supergroup'],
                'date' => now()->timestamp,
                'text' => $text,
            ], $overrides),
        ], []);
    }

    private function callbackUpdate(string $data): array
    {
        return [
            'update_id' => random_int(1, 999999),
            'callback_query' => [
                'id' => (string) random_int(1, 999999),
                'from' => [
                    'id' => 555111,
                    'is_bot' => false,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 123,
                    'chat' => ['id' => -100123456, 'type' => 'supergroup'],
                ],
                'data' => $data,
            ],
        ];
    }

    public function test_ignores_unrelated_chat_message(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('хто сьогодні їде на заїзд?'))
            ->assertOk();

        $this->assertSame(0, RideResult::count());
        $this->assertSame('ignored', BotMessageLog::first()->status);
        Http::assertNothingSent();
    }

    public function test_recognizes_dot_decimal_result(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 10.5'))->assertOk();

        $this->assertSame(1, RideResult::count());
        $this->assertEquals(10.5, (float) RideResult::first()->distance_km);
    }

    public function test_recognizes_comma_decimal_result(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 10,5'))->assertOk();

        $this->assertEquals(10.5, (float) RideResult::first()->distance_km);
    }

    public function test_recognizes_plus_led_format(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('+10 км'))->assertOk();

        $this->assertEquals(10.0, (float) RideResult::first()->distance_km);
    }

    public function test_recognizes_english_result_keyword(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('result 10'))->assertOk();

        $this->assertEquals(10.0, (float) RideResult::first()->distance_km);
    }

    public function test_auto_registers_new_participant(): void
    {
        $this->assertSame(0, Participant::count());

        $this->postJson(route('telegram.webhook'), $this->update('результат 5 км'))->assertOk();

        $participant = Participant::first();
        $this->assertNotNull($participant);
        $this->assertSame(555111, $participant->telegram_user_id);
        $this->assertSame('Олексій Мироненко', $participant->display_name);
    }

    public function test_sends_success_reply_with_totals(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 25 км'))->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains(($request['text'] ?? ''), 'результат — 25')
                && str_contains(($request['text'] ?? ''), 'Torcoins');
        });
    }

    public function test_first_result_of_week_earns_bonus_and_announces_updated_balance(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 25 км'))->assertOk();

        $bonus = TorcoinBonus::first();
        $this->assertNotNull($bonus);
        $this->assertSame(0.1, (float) $bonus->amount);
        $this->assertSame(Participant::first()->id, $bonus->participant_id);
        $this->assertSame(RideResult::first()->weekly_period_id, $bonus->weekly_period_id);

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'першим учасником')
            && str_contains(($request['text'] ?? ''), '<b>Олексій Мироненко</b>')
            && str_contains(($request['text'] ?? ''), '<b>0.1 TOR.COINS</b>')
            && str_contains(($request['text'] ?? ''), 'Ваш баланс TOR.COINS: <b>0.35</b>'));
    }

    public function test_only_one_participant_receives_the_weekly_first_result_bonus(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 25 км'))->assertOk();

        $secondParticipant = [
            'id' => 777222,
            'is_bot' => false,
            'first_name' => 'Інший',
            'last_name' => 'Учасник',
            'username' => 'another',
        ];
        $this->postJson(route('telegram.webhook'), $this->update('результат 30 км', [
            'from' => $secondParticipant,
        ]))->assertOk();

        $this->assertSame(2, RideResult::count());
        $this->assertSame(1, TorcoinBonus::count());
        $this->assertSame(555111, TorcoinBonus::first()->participant->telegram_user_id);

        $announcements = Http::recorded(fn ($request) => str_contains(($request['text'] ?? ''), 'першим учасником'));
        $this->assertCount(1, $announcements);
    }

    public function test_deleting_the_first_ride_does_not_award_the_same_weekly_bonus_again(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 25 км'))->assertOk();
        RideResult::query()->delete();

        $this->postJson(route('telegram.webhook'), $this->update('результат 30 км', [
            'from' => [
                'id' => 777222,
                'is_bot' => false,
                'first_name' => 'Інший',
                'last_name' => 'Учасник',
                'username' => 'another',
            ],
        ]))->assertOk();

        $this->assertSame(1, RideResult::count());
        $this->assertSame(1, TorcoinBonus::count());
        $this->assertSame(555111, TorcoinBonus::first()->participant->telegram_user_id);
        $this->assertSame('result_saved', BotMessageLog::latest('id')->first()->handler);
    }

    public function test_adopts_telegram_profile_photo_when_participant_has_no_avatar(): void
    {
        Storage::fake('public');
        // Reset to a clean factory: the broad api.telegram.org/* stub from
        // setUp() is registered first and would otherwise shadow the specific
        // profile-photo stubs below (first matching stub wins).
        Http::swap(new Factory);
        Http::fake([
            '*getUserProfilePhotos*' => Http::response(['ok' => true, 'result' => [
                'total_count' => 1,
                'photos' => [[
                    ['file_id' => 'small', 'width' => 160, 'height' => 160],
                    ['file_id' => 'big', 'width' => 640, 'height' => 640],
                ]],
            ]], 200),
            '*getFile*' => Http::response(['ok' => true, 'result' => ['file_path' => 'photos/file_1.jpg']], 200),
            'api.telegram.org/file/*' => Http::response('BINARY-IMAGE-BYTES', 200),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $this->postJson(route('telegram.webhook'), $this->update('результат 12 км'))->assertOk();

        $participant = Participant::first();
        $this->assertSame("/storage/avatars/{$participant->id}.jpg", $participant->avatar_url);
        Storage::disk('public')->assertExists("avatars/{$participant->id}.jpg");
    }

    public function test_keeps_placeholder_when_participant_has_no_telegram_photo(): void
    {
        Storage::fake('public');
        // Default fake (from setUp) returns a payload without a `photos` array.
        $this->postJson(route('telegram.webhook'), $this->update('результат 12 км'))->assertOk();

        $this->assertNull(Participant::first()->avatar_url);
    }

    public function test_rejects_too_large_distance(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 5000 км'))->assertOk();

        $this->assertSame(0, RideResult::count());
        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Занадто великий результат'));
    }

    public function test_warns_on_duplicate_submission(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 20 км'))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->update('результат 20 км'))->assertOk();

        $this->assertSame(1, RideResult::count());
        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Схожий результат'));
    }

    public function test_start_command_replies(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('/start'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'бот велоклубу'));
    }

    public function test_help_command_replies(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('/help'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Як надіслати результат'));
    }

    public function test_me_command_replies_with_stats(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 30 км'))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->update('/me'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Мироненко'));
    }

    public function test_top_command_replies(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('результат 15 км'))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->update('/top'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Топ-5'));
    }

    public function test_week_command_replies(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('/week'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Рейтинг тижня'));
    }

    public function test_year_command_replies(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('/year'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'Рейтинг'));
    }

    public function test_alltime_command_replies(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('/alltime'))->assertOk();

        Http::assertSent(fn ($request) => str_contains(($request['text'] ?? ''), 'весь час'));
    }

    public function test_telegram_chat_administrator_can_open_admin_menu(): void
    {
        Http::swap(new Factory);
        Http::fake([
            '*getChatMember*' => Http::response(['ok' => true, 'result' => ['status' => 'administrator']], 200),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $this->postJson(route('telegram.webhook'), $this->update('/admin'))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && isset($request['reply_markup']['inline_keyboard']));
    }

    public function test_admin_can_add_a_result_for_a_registered_participant_through_bot_buttons(): void
    {
        config(['services.telegram.admin_ids' => ['555111']]);

        $period = WeeklyPeriod::factory()->active()->create([
            'year' => 2026,
            'week_number' => 31,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-03',
        ]);
        $target = Participant::factory()->create(['display_name' => 'Target Rider']);

        $this->postJson(route('telegram.webhook'), $this->update('/admin'))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('admin:add'))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate("admin:period:{$period->id}"))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate("admin:participant:{$period->id}:{$target->id}"))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->update('42,5'))->assertOk();

        $this->assertDatabaseHas('ride_results', [
            'participant_id' => $target->id,
            'weekly_period_id' => $period->id,
            'distance_km' => 42.5,
            'source' => 'admin',
        ]);
        $this->assertEquals(42.5, (float) $period->fresh()->total_distance);
        $this->assertSame('admin_manual_result', BotMessageLog::latest('id')->first()->handler);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains((string) ($request['text'] ?? ''), 'Target Rider')
            && str_contains((string) ($request['text'] ?? ''), '42.50'));
    }

    public function test_admin_can_rollback_an_empty_new_week_through_bot_confirmation(): void
    {
        config(['services.telegram.admin_ids' => ['555111']]);

        $previous = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 30,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-27',
        ]);
        $active = WeeklyPeriod::factory()->active()->create([
            'year' => 2026,
            'week_number' => 31,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-03',
        ]);

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('admin:rollback'))->assertOk();
        $this->assertSame('closed', $previous->fresh()->status);

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate("admin:rollback_confirm:{$active->id}:{$previous->id}"))->assertOk();

        $this->assertDatabaseMissing('weekly_periods', ['id' => $active->id]);
        $this->assertSame('active', $previous->fresh()->status);
        $this->assertNull($previous->fresh()->report_sent_at);
        $this->assertNull($previous->fresh()->closed_at);

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('admin:close'))->assertOk();
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate("admin:close_confirm:{$previous->id}"))->assertOk();

        $this->assertSame('closed', $previous->fresh()->status);
        $this->assertDatabaseHas('weekly_periods', ['year' => 2026, 'week_number' => 31, 'status' => 'active']);

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate("admin:close_confirm:{$previous->id}"))->assertOk();
        $this->assertDatabaseHas('weekly_periods', ['year' => 2026, 'week_number' => 31, 'status' => 'active']);
    }

    public function test_week_rollback_refuses_to_delete_a_new_week_that_has_results(): void
    {
        config(['services.telegram.admin_ids' => ['555111']]);

        $previous = WeeklyPeriod::factory()->create([
            'year' => 2026,
            'week_number' => 30,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-27',
        ]);
        $active = WeeklyPeriod::factory()->active()->create([
            'year' => 2026,
            'week_number' => 31,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-03',
        ]);
        RideResult::factory()->create(['weekly_period_id' => $active->id]);

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate("admin:rollback_confirm:{$active->id}:{$previous->id}"))->assertOk();

        $this->assertDatabaseHas('weekly_periods', ['id' => $active->id, 'status' => 'active']);
        $this->assertSame('closed', $previous->fresh()->status);
        Http::assertSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), 'у новому тижні вже є дані'));
    }

    public function test_webhook_rejects_request_with_wrong_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'super-secret']);

        $this->postJson(route('telegram.webhook'), $this->update('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-token',
        ])->assertForbidden();
    }

    public function test_webhook_accepts_request_with_correct_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'super-secret']);

        $this->postJson(route('telegram.webhook'), $this->update('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'super-secret',
        ])->assertOk();
    }

    public function test_malformed_payload_is_logged_as_error_and_still_returns_200(): void
    {
        $malformed = [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                // 'from' is missing its required 'id' key entirely.
                'from' => ['is_bot' => false, 'first_name' => 'Ghost'],
                'chat' => ['id' => -100123456, 'type' => 'supergroup'],
                'date' => now()->timestamp,
                'text' => 'результат 10 км',
            ],
        ];

        $this->postJson(route('telegram.webhook'), $malformed)->assertOk();

        $this->assertSame('error', BotMessageLog::latest('id')->first()->status);
    }

    public function test_completely_empty_payload_does_not_crash(): void
    {
        $this->postJson(route('telegram.webhook'), [])->assertOk();

        $this->assertSame('ignored', BotMessageLog::first()->status);
    }
}
