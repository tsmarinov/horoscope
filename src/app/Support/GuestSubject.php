<?php

namespace App\Support;

use App\Contracts\HoroscopeSubject;
use App\Models\City;

class GuestSubject implements HoroscopeSubject
{
    public function __construct(
        private readonly string  $birthDate,
        private readonly ?string $birthTime = null,
        private readonly bool    $birthTimeApproximate = false,
        private readonly ?int    $birthCityId = null,
    ) {}

    public function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public function getBirthTime(): ?string
    {
        return $this->birthTime;
    }

    public function isBirthTimeApproximate(): bool
    {
        return $this->birthTimeApproximate;
    }

    public function getBirthCity(): ?City
    {
        if ($this->birthCityId === null) {
            return null;
        }

        return City::find($this->birthCityId);
    }

    public function getChartTier(): int
    {
        if (! $this->birthTime) {
            return 1;
        }

        if ($this->birthTimeApproximate || ! $this->birthCityId) {
            return 2;
        }

        return 3;
    }

    public function isGuest(): bool
    {
        return true;
    }

    public function isFull(): bool
    {
        return false;
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
