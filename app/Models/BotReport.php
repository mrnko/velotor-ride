<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotReport extends Model
{
    /** @use HasFactory<\Database\Factories\BotReportFactory> */
    use HasFactory;

    protected $fillable = [
        'weekly_period_id',
        'chat_id',
        'telegram_message_id',
        'report_type',
        'content',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function weeklyPeriod(): BelongsTo
    {
        return $this->belongsTo(WeeklyPeriod::class);
    }
}
