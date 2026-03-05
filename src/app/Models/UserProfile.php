<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'birth_date',
        'birth_time',
        'birth_time_approximate',
        'birth_city_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date'             => 'date',
            'birth_time_approximate' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function birthCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'birth_city_id');
    }

    public function getBirthDate(): string
    {
        return $this->birth_date?->format('Y-m-d') ?? '';
    }

    public function getBirthTime(): ?string
    {
        return $this->birth_time;
    }

    public function isBirthTimeApproximate(): bool
    {
        return (bool) $this->birth_time_approximate;
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

        if ($this->birth_time_approximate || ! $this->birth_city_id) {
            return 2;
        }

        return 3;
    }
}
