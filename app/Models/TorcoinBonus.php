<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorcoinBonus extends Model
{
    public const REASON_FIRST_WEEKLY_RESULT = 'first_weekly_result';

    protected $fillable = [
        'participant_id',
        'weekly_period_id',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
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
