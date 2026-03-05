<?php

namespace App\Models;

use App\Contracts\HoroscopeSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HoroscopeSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function natalChart(): MorphOne
    {
        return $this->morphOne(NatalChart::class, 'subject');
    }

    public function getBirthDate(): string
    {
        return $this->profile?->getBirthDate() ?? '';
    }

    public function getBirthTime(): ?string
    {
        return $this->profile?->getBirthTime();
    }

    public function isBirthTimeApproximate(): bool
    {
        return $this->profile?->isBirthTimeApproximate() ?? false;
    }

    public function getBirthCity(): ?City
    {
        return $this->profile?->getBirthCity();
    }

    public function getChartTier(): int
    {
        return $this->profile?->getChartTier() ?? 1;
    }

    public function isGuest(): bool
    {
        return false;
    }

    public function isFull(): bool
    {
        return true;
    }

    public function isPremium(): bool
    {
        return false;
    }

    public function isDemo(): bool
    {
        return false;
    }
}
