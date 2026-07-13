<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnnounceUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.announce_secret' => 'test-secret',
            'services.telegram.chat_id' => '-100123456',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);
    }

    public function test_rejects_missing_secret(): void
    {
        $this->get('/telegram/announce')->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_rejects_wrong_secret(): void
    {
        $this->get('/telegram/announce?secret=wrong')->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_sends_announcement_with_correct_secret(): void
    {
        $this->get('/telegram/announce?secret=test-secret')->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['chat_id'], '-100123456')
            && str_contains($request['text'], 'оновлено'));
    }
}
