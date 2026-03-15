<?php

namespace App\Models;

use App\Contracts\HoroscopeSubject;
use App\Enums\Gender;
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
 * @property string           $first_name
 * @property string|null      $last_name
 * @property Gender            $gender
 * @property-read string      $name       First + last name (computed)
 * @property string|null      $slug
 * @property \Carbon\Carbon|null $birth_date
 * @property string|null      $birth_time
 * @property int|null         $birth_city_id
 * @property int|null         $solar_return_city_id
 */
class Profile extends Model implements HoroscopeSubject
{
    protected $fillable = [
        'user_id',
        'guest_id',
        'is_demo',
        'first_name',
        'last_name',
        'slug',
        'birth_date',
        'birth_time',
        'birth_city_id',
        'solar_return_city_id',
        'gender',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_demo'    => 'boolean',
            'birth_date' => 'date',
            'gender'     => Gender::class,
        ];
    }

    // ── Computed attributes ───────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ($this->last_name ? ' ' . $this->last_name : ''));
    }

    /** Sun sign glyph + name based on birth date, or null if no date. */
    public function sunSign(): ?array
    {
        if (! $this->birth_date) {
            return null;
        }

        $m = (int) $this->birth_date->format('n');
        $d = (int) $this->birth_date->format('j');

        $signs = [
            ['glyph' => '♑', 'name' => 'Capricorn',   'from' => [12, 22]],
            ['glyph' => '♒', 'name' => 'Aquarius',     'from' => [1,  20]],
            ['glyph' => '♓', 'name' => 'Pisces',       'from' => [2,  19]],
            ['glyph' => '♈', 'name' => 'Aries',        'from' => [3,  21]],
            ['glyph' => '♉', 'name' => 'Taurus',       'from' => [4,  20]],
            ['glyph' => '♊', 'name' => 'Gemini',       'from' => [5,  21]],
            ['glyph' => '♋', 'name' => 'Cancer',       'from' => [6,  21]],
            ['glyph' => '♌', 'name' => 'Leo',          'from' => [7,  23]],
            ['glyph' => '♍', 'name' => 'Virgo',        'from' => [8,  23]],
            ['glyph' => '♎', 'name' => 'Libra',        'from' => [9,  23]],
            ['glyph' => '♏', 'name' => 'Scorpio',      'from' => [10, 23]],
            ['glyph' => '♐', 'name' => 'Sagittarius',  'from' => [11, 22]],
            ['glyph' => '♑', 'name' => 'Capricorn',    'from' => [12, 22]],
        ];

        $result = $signs[0]; // default Capricorn (early Jan)
        foreach ($signs as $sign) {
            [$sm, $sd] = $sign['from'];
            if ($m > $sm || ($m === $sm && $d >= $sd)) {
                $result = $sign;
            }
        }

        return $result;
    }

    /** Age in years, or null if no birth date. */
    public function age(): ?int
    {
        return $this->birth_date ? (int) $this->birth_date->diffInYears(now()) : null;
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

    public function solarReturnCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'solar_return_city_id');
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
