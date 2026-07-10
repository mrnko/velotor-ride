<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;

class StartCommand implements TelegramCommand
{
    public function handle(Participant $participant): string
    {
        return implode("\n", [
            '🚴 Привіт! Я бот велоклубу.',
            '',
            'Надішли свій результат у чат, наприклад:',
            'результат 25 км',
            '',
            'Я порахую твої кілометри, місце в рейтингу та Torcoins.',
            'Команда /help — короткий список того, що я вмію.',
        ]);
    }
}
