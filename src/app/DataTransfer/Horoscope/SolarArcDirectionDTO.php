<?php

namespace App\DataTransfer\Horoscope;

class SolarArcDirectionDTO
{
    public function __construct(
        public readonly int    $directedBody,
        public readonly string $directedName,
        public readonly int    $natalTargetBody,
        public readonly string $natalTargetName,
        public readonly string $aspect,
        public readonly float  $orb,
    ) {}
}
