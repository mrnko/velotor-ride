<?php

namespace App\Models;

use Database\Factories\WeeklyPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyPeriod extends Model
{
    /** @use HasFactory<WeeklyPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'year',
        'week_number',
        'title',
        'start_date',
        'end_date',
        'status',
        'total_distance',
        'report_sent_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_distance' => 'decimal:2',
            'report_sent_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function rideResults(): HasMany
    {
        return $this->hasMany(RideResult::class);
    }

    public function botReports(): HasMany
    {
        return $this->hasMany(BotReport::class);
    }

    public function torcoinBonuses(): HasMany
    {
        return $this->hasMany(TorcoinBonus::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
