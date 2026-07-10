<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramClient
{
    private string $baseUrl;

    public function __construct(?string $botToken = null)
    {
        $token = $botToken ?? config('services.telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$token}";
    }

    /**
     * @return array{ok: bool, message_id: int|null, error: string|null}
     */
    public function sendMessage(string $chatId, string $text): array
    {
        try {
            $response = Http::asJson()->post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful() && $response->json('ok')) {
                return [
                    'ok' => true,
                    'message_id' => $response->json('result.message_id'),
                    'error' => null,
                ];
            }

            Log::warning('Telegram sendMessage failed', ['response' => $response->json()]);

            return ['ok' => false, 'message_id' => null, 'error' => $response->json('description', 'unknown error')];
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }
}
