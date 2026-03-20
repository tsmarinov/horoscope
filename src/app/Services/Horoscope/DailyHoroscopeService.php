<?php

namespace App\Services\Horoscope;

use App\DataTransfer\Horoscope\AreaOfLifeDTO;
use App\DataTransfer\Horoscope\DailyHoroscopeDTO;
use App\DataTransfer\Horoscope\DayRulerDTO;
use App\DataTransfer\Horoscope\MoonDataDTO;
use App\DataTransfer\Horoscope\PlanetPositionDTO;
use App\DataTransfer\Horoscope\RetrogradePlanetDTO;
use App\DataTransfer\Horoscope\TransitAspectDTO;
use App\DataTransfer\Horoscope\TransitTransitDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\WeekdayText;
use App\Services\AspectCalculator;
use App\Services\Horoscope\Shared\AreasOfLifeScorer;
use Carbon\Carbon;

class DailyHoroscopeService
{
    // ── Moon phases: [from, to, slug] ────────────────────────────────────
    private const MOON_PHASES = [
        [0,   45,  'new_moon'],
        [45,  90,  'waxing_crescent'],
        [90,  135, 'first_quarter'],
        [135, 180, 'waxing_gibbous'],
        [180, 225, 'full_moon'],
        [225, 270, 'waning_gibbous'],
        [270, 315, 'last_quarter'],
        [315, 360, 'waning_crescent'],
    ];

    // ── Day rulers: indexed by Carbon dayOfWeek (0=Sun ... 6=Sat) ─────
    private const DAY_RULERS = [
        0 => ['planet' => 'Sun',     'body' => 0, 'number' => 1],
        1 => ['planet' => 'Moon',    'body' => 1, 'number' => 2],
        2 => ['planet' => 'Mars',    'body' => 4, 'number' => 9],
        3 => ['planet' => 'Mercury', 'body' => 2, 'number' => 5],
        4 => ['planet' => 'Jupiter', 'body' => 5, 'number' => 3],
        5 => ['planet' => 'Venus',   'body' => 3, 'number' => 6],
        6 => ['planet' => 'Saturn',  'body' => 6, 'number' => 8],
    ];

    public function __construct(
        private readonly AspectCalculator $calculator,
        private readonly AreasOfLifeScorer $scorer,
    ) {}

    public function build(Profile $profile, string $date): DailyHoroscopeDTO
    {
        $positions = PlanetaryPosition::forDate($date)->orderBy('body')->get();

        if ($positions->isEmpty()) {
            throw new \RuntimeException("No planetary positions found for {$date}.");
        }

        $planets = $positions->keyBy('body');
        $carbon  = Carbon::parse($date);

        // ── Moon data ───────────────────────────────────────────────────
        $moon       = $planets->get(PlanetaryPosition::MOON);
        $sun        = $planets->get(PlanetaryPosition::SUN);
        $elongation = fmod(($moon?->longitude ?? 0) - ($sun?->longitude ?? 0) + 360, 360);
        $lunarDay   = max(1, (int) ceil($elongation / (360 / 29.53)));

        [$moonPhaseSlug, $moonPhaseName] = $this->moonPhase($elongation);
        $moonSignIdx  = (int) floor(($moon?->longitude ?? 0) / 30);
        $moonSignName = PlanetaryPosition::SIGN_NAMES[$moonSignIdx] ?? '';

        $moonData = new MoonDataDTO(
            lunarDay:  $lunarDay,
            phaseSlug: $moonPhaseSlug,
            phaseName: $moonPhaseName,
            elongation: $elongation,
            signIndex:  $moonSignIdx,
            signName:   $moonSignName,
        );

        // ── Retrogrades (Mercury-Saturn, bodies 2-6) ────────────────────
        $retrogradeModels = $positions
            ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
            ->values();

        $retrogrades = $retrogradeModels->map(fn ($p) => new RetrogradePlanetDTO(
            body:      $p->body,
            name:      PlanetaryPosition::BODY_NAMES[$p->body] ?? '',
            signIndex: (int) floor($p->longitude / 30),
            signName:  PlanetaryPosition::SIGN_NAMES[(int) floor($p->longitude / 30)] ?? '',
        ))->all();

        $rxBodies = $retrogradeModels->pluck('body')->toArray();

        // ── Transit arrays for AspectCalculator ─────────────────────────
        $transitPlanets = $positions->map(fn ($p) => [
            'body'          => $p->body,
            'longitude'     => $p->longitude,
            'speed'         => $p->speed,
            'sign'          => (int) floor($p->longitude / 30),
            'is_retrograde' => $p->is_retrograde,
        ])->values()->all();

        // ── Transit-to-natal: full list for scoring, top 5 for display ──
        $natalPlanets = $profile->natalChart?->planets ?? [];
        $allTransitNatalAspects = ! empty($natalPlanets)
            ? $this->calculator->transitToNatal($transitPlanets, $natalPlanets)
            : [];

        $transitNatalAspects = array_map(
            fn ($asp) => new TransitAspectDTO(
                transitBody: $asp['transit_body'],
                transitName: PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '',
                natalBody:   $asp['natal_body'],
                natalName:   PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '',
                aspect:      $asp['aspect'],
                orb:         $asp['orb'],
            ),
            array_slice($allTransitNatalAspects, 0, 5),
        );

        // ── Transit-to-transit: top 3, skip mutual_reception ────────────
        $ttRaw = array_slice(
            array_filter(
                $this->calculator->transitToTransit($transitPlanets),
                fn ($a) => $a['aspect'] !== 'mutual_reception',
            ),
            0, 3,
        );

        $transitTransitAspects = array_map(
            fn ($asp) => new TransitTransitDTO(
                bodyA: $asp['body_a'],
                nameA: PlanetaryPosition::BODY_NAMES[$asp['body_a']] ?? '',
                bodyB: $asp['body_b'],
                nameB: PlanetaryPosition::BODY_NAMES[$asp['body_b']] ?? '',
                aspect: $asp['aspect'],
                orb:    $asp['orb'],
            ),
            $ttRaw,
        );

        // ── Areas of life ───────────────────────────────────────────────
        $houseCusps     = $profile->natalChart?->houses ?? [];
        $natalBodyScores = $this->scorer->buildDayScores($allTransitNatalAspects, $rxBodies);
        $areasOfLife     = $this->scorer->score($natalBodyScores, $houseCusps);

        // ── Day ruler ───────────────────────────────────────────────────
        $dow      = $carbon->dayOfWeek;
        $isoDay   = $dow === 0 ? 7 : $dow;
        $ruler    = self::DAY_RULERS[$dow];
        $wt       = WeekdayText::where('iso_day', $isoDay)->where('language', 'en')->first();
        $dayRuler = new DayRulerDTO(
            weekday:   $carbon->format('l'),
            dayOfWeek: $dow,
            planet:    $ruler['planet'],
            body:      $ruler['body'],
            color:     $wt?->colors ?? '',
            gem:       $wt?->gem ?? '',
            number:    $ruler['number'],
        );

        // ── Natal Venus sign (for clothing tip) ─────────────────────────
        $venusSign = null;
        foreach ($profile->natalChart?->planets ?? [] as $pl) {
            if ($pl['body'] === 3) {
                $venusSign = $pl['sign'];
                break;
            }
        }

        // ── Planet positions DTOs ───────────────────────────────────────
        $positionDTOs = $positions->map(fn ($p) => new PlanetPositionDTO(
            body:         $p->body,
            name:         PlanetaryPosition::BODY_NAMES[$p->body] ?? '',
            longitude:    $p->longitude,
            signIndex:    (int) floor($p->longitude / 30),
            signName:     PlanetaryPosition::SIGN_NAMES[(int) floor($p->longitude / 30)] ?? '',
            degreeInSign: fmod($p->longitude, 30),
            isRetrograde: $p->is_retrograde,
            speed:        $p->speed,
        ))->all();

        // ── Profile name ────────────────────────────────────────────────
        $profileName = $profile->name ?? $profile->user?->name ?? 'Profile #' . $profile->id;

        return new DailyHoroscopeDTO(
            date:                  $date,
            profileName:           $profileName,
            positions:             $positionDTOs,
            transitNatalAspects:   $transitNatalAspects,
            transitTransitAspects: $transitTransitAspects,
            retrogrades:           $retrogrades,
            moon:                  $moonData,
            dayRuler:              $dayRuler,
            areasOfLife:           $areasOfLife,
            natalVenusSign:        $venusSign,
        );
    }

    private function moonPhase(float $elongation): array
    {
        foreach (self::MOON_PHASES as [$from, $to, $slug]) {
            if ($elongation >= $from && $elongation < $to) {
                return [$slug, __('lunar.phases.' . $slug)];
            }
        }
        return ['new_moon', __('lunar.phases.new_moon')];
    }
}
