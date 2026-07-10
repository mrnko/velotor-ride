<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotMessageLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'telegram_update_id',
        'chat_id',
        'telegram_user_id',
        'message_text',
        'direction',
        'handler',
        'status',
        'error_message',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
        ];
    }
}
