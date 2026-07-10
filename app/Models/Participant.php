<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    /** @use HasFactory<\Database\Factories\ParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'telegram_username',
        'first_name',
        'last_name',
        'display_name',
        'avatar_url',
        'is_active',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function rideResults(): HasMany
    {
        return $this->hasMany(RideResult::class);
    }

    public static function resolveDisplayName(?string $firstName, ?string $lastName, ?string $username): string
    {
        $name = trim(($firstName ?? '').' '.($lastName ?? ''));

        return $name !== '' ? $name : ($username ?? 'Учасник');
    }
}
