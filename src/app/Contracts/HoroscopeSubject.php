<?php

namespace App\Contracts;

use App\Models\City;

interface HoroscopeSubject
{
    public function getBirthDate(): string;
    public function getBirthTime(): ?string;
    public function isBirthTimeApproximate(): bool;
    public function getBirthCity(): ?City;

    /**
     * Chart tier:
     *   1 — birth date only (no time, no city)
     *   2 — approximate time, or exact time without city
     *   3 — exact time + city
     */
    public function getChartTier(): int;

    public function isGuest(): bool;
    public function isFull(): bool;
    public function isPremium(): bool;
    public function isDemo(): bool;
}
