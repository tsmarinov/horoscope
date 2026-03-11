<?php

namespace App\Models;

use App\Contracts\HoroscopeSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Unified profile — covers registered users, guests, and demo profiles.
 *
 * Exactly one of user_id / guest_id is set, or neither (is_demo = true).
 *
 * @property int              $id
 * @property int|null         $user_id
 * @property int|null         $guest_id
 * @property bool             $is_demo
 * @property string|null      $name
 * @property string|null      $slug
 * @property \Carbon\Carbon|null $birth_date
 * @property string|null      $birth_time
 * @property int|null         $birth_city_id
 */
class Profile extends Model implements HoroscopeSubject
{
    protected $fillable = [
        'user_id',
        'guest_id',
        'is_demo',
        'name',
        'slug',
        'birth_date',
        'birth_time',
        'birth_city_id',
    ];

    protected function casts(): array
    {
        return [
            'is_demo'    => 'boolean',
            'birth_date' => 'date',
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function birthCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'birth_city_id');
    }

    public function natalChart(): HasOne
    {
        return $this->hasOne(NatalChart::class);
    }

    // ── HoroscopeSubject ─────────────────────────────────────────────────

    public function getBirthDate(): string
    {
        return $this->birth_date?->format('Y-m-d') ?? '';
    }

    public function getBirthTime(): ?string
    {
        return $this->birth_time;
    }

    public function getBirthCity(): ?City
    {
        return $this->birthCity;
    }

    public function getChartTier(): int
    {
        if (! $this->birth_time) {
            return 1;
        }

        if (! $this->birth_city_id) {
            return 2;
        }

        return 3;
    }

    public function isGuest(): bool
    {
        return $this->guest_id !== null;
    }

    public function isFull(): bool
    {
        return $this->user_id !== null;
    }

    public function isPremium(): bool
    {
        return false;
    }

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }
}
