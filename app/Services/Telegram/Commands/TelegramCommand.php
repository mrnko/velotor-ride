<?php

namespace App\Services\Telegram\Commands;

use App\Models\Participant;

interface TelegramCommand
{
    public function handle(Participant $participant): string;
}
