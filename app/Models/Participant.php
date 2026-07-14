<?php

namespace App\Models;

use App\Support\Transliterate;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'legacy_source',
        'legacy_id',
        'telegram_username',
        'first_name',
        'last_name',
        'display_name',
        'slug',
        'avatar_url',
        'is_active',
        'first_seen_at',
        'last_seen_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (Participant $participant): void {
            if (blank($participant->slug) && filled($participant->display_name)) {
                $participant->slug = $participant->uniqueSlug($participant->display_name);
            }
        });
    }

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

    public function torcoinBonuses(): HasMany
    {
        return $this->hasMany(TorcoinBonus::class);
    }

    public static function resolveDisplayName(?string $firstName, ?string $lastName, ?string $username): string
    {
        $name = trim(($firstName ?? '').' '.($lastName ?? ''));

        return $name !== '' ? $name : ($username ?? 'Учасник');
    }

    /**
     * Latin, lowercase, hyphenated slug that is unique across participants.
     * Appends -2, -3, … on collision (e.g. two "Іван Петров").
     */
    public function uniqueSlug(string $source): string
    {
        $base = Transliterate::slug($source);
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Initials used for the placeholder avatar (e.g. "ОМ").
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->display_name)) ?: [];
        $letters = array_map(fn ($p) => mb_substr($p, 0, 1, 'UTF-8'), array_slice($parts, 0, 2));

        return mb_strtoupper(implode('', $letters), 'UTF-8') ?: '?';
    }
}
