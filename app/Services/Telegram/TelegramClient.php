<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramClient
{
    private string $baseUrl;

    private string $token;

    public function __construct(?string $botToken = null)
    {
        $this->token = (string) ($botToken ?? config('services.telegram.bot_token'));
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
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

    /**
     * Best-effort download of the user's current Telegram profile photo (its
     * largest available size) as raw image bytes. Returns null if the user has
     * no photo, has it hidden by privacy settings, or any API call fails —
     * never throws, so callers can treat it as an optional enhancement.
     */
    public function fetchUserProfilePhoto(int|string $userId): ?string
    {
        try {
            $photos = Http::get("{$this->baseUrl}/getUserProfilePhotos", [
                'user_id' => $userId,
                'limit' => 1,
            ]);

            // result.photos is a list of photos, each a list of size variants
            // ordered small→large. Take the first (current) photo, largest size.
            $sizes = $photos->json('result.photos.0');

            if (! is_array($sizes) || $sizes === []) {
                return null;
            }

            $fileId = end($sizes)['file_id'] ?? null;

            if (! $fileId) {
                return null;
            }

            $filePath = Http::get("{$this->baseUrl}/getFile", ['file_id' => $fileId])
                ->json('result.file_path');

            if (! $filePath) {
                return null;
            }

            $download = Http::get("https://api.telegram.org/file/bot{$this->token}/{$filePath}");

            return $download->successful() ? $download->body() : null;
        } catch (\Throwable $e) {
            Log::warning('Telegram profile photo fetch failed', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
