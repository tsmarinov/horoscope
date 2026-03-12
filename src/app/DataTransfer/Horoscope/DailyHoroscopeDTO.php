<?php

namespace App\DataTransfer\Horoscope;

class DailyHoroscopeDTO
{
    /**
     * @param  PlanetPositionDTO[]    $positions
     * @param  TransitAspectDTO[]     $transitNatalAspects   top 5
     * @param  TransitTransitDTO[]    $transitTransitAspects top 3
     * @param  RetrogradePlanetDTO[]  $retrogrades
     * @param  AreaOfLifeDTO[]        $areasOfLife
     */
    public function __construct(
        public readonly string $date,
        public readonly string $profileName,
        public readonly array $positions,
        public readonly array $transitNatalAspects,
        public readonly array $transitTransitAspects,
        public readonly array $retrogrades,
        public readonly MoonDataDTO $moon,
        public readonly DayRulerDTO $dayRuler,
        public readonly array $areasOfLife,
        public readonly ?int $natalVenusSign,
    ) {}
}
