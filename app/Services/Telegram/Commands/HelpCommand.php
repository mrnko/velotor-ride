<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;

class HelpCommand implements TelegramCommand
{
    public function handle(Participant $participant): string
    {
        return implode("\n", [
            '📖 Як надіслати результат:',
            'результат 25',
            'результат 25 км',
            'результат 25.5',
            'результат 25,5',
            '+25 км',
            '',
            '🤖 Команди:',
            '/me — моя статистика',
            '/top — топ поточного тижня',
            '/week — рейтинг поточного тижня',
            '/year — рейтинг поточного року',
            '/alltime — рейтинг за весь час',
        ]);
    }
}
