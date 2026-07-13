<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A manually-triggered endpoint (visit the URL once) that posts a "the site
 * was updated" announcement into the club's Telegram chat. Protected by a
 * dedicated secret so it can't be spammed by strangers who find the URL.
 */
class AnnounceUpdateController extends Controller
{
    public function __invoke(Request $request, TelegramClient $telegram): Response
    {
        $secret = config('services.telegram.announce_secret');

        if (! $secret || ! hash_equals($secret, (string) $request->query('secret', ''))) {
            abort(403, 'Invalid or missing secret.');
        }

        $chatId = Setting::get('telegram_chat_id') ?: config('services.telegram.chat_id');

        if (! $chatId) {
            return response('Telegram chat id is not configured.', 200);
        }

        $release = $this->currentRelease();

        $lines = [];
        $lines[] = '🎉 Сайт велоклубу «ВелоТОР» оновлено!';
        $lines[] = '';
        $lines[] = trim("Версія {$release['version']} — {$release['title']}", ' —');
        $lines[] = '';
        $lines[] = 'Загляньте подивитись, що нового — статистика, дизайн і трохи магії під капотом. 🚴';
        $lines[] = '';
        $lines[] = '➡️ '.route('stat.home').' ⬅️';

        $result = $telegram->sendMessage($chatId, implode("\n", $lines));

        return response($result['ok'] ? 'OK' : 'Failed: '.$result['error'], 200);
    }

    /**
     * @return array{version: string, title: string}
     */
    private function currentRelease(): array
    {
        $path = base_path('CHANGELOG.md');

        if (is_file($path) && preg_match('/^## \[([^\]]+)\][^\n]*\R+### (.+)$/m', file_get_contents($path), $matches)) {
            return ['version' => $matches[1], 'title' => trim($matches[2])];
        }

        return ['version' => '?', 'title' => ''];
    }
}
