<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideResult extends Model
{
    /** @use HasFactory<\Database\Factories\RideResultFactory> */
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'weekly_period_id',
        'distance_km',
        'raw_message',
        'telegram_message_id',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function weeklyPeriod(): BelongsTo
    {
        return $this->belongsTo(WeeklyPeriod::class);
    }
}
