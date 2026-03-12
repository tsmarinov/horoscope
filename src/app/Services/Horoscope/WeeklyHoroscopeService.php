<?php

namespace App\Services\Horoscope;

use App\DataTransfer\Horoscope\AreaOfLifeDTO;
use App\DataTransfer\Horoscope\LunationDTO;
use App\DataTransfer\Horoscope\PlanetPositionDTO;
use App\DataTransfer\Horoscope\RetrogradePlanetDTO;
use App\DataTransfer\Horoscope\TransitAspectDTO;
use App\DataTransfer\Horoscope\WeeklyHoroscopeDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Services\AspectCalculator;
use App\Services\Horoscope\Shared\AreasOfLifeScorer;
use App\Services\Horoscope\Shared\KeyDatesBuilder;
use App\Services\Horoscope\Shared\LunationDetector;
use Carbon\Carbon;

class WeeklyHoroscopeService
{
    public function __construct(
        private readonly AspectCalculator $calculator,
        private readonly AreasOfLifeScorer $scorer,
        private readonly LunationDetector $lunationDetector,
        private readonly KeyDatesBuilder $keyDatesBuilder,
    ) {}

    public function build(Profile $profile, string $date): WeeklyHoroscopeDTO
    {
        // Week range: Monday-Sunday
        $monday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->endOfWeek(Carbon::SUNDAY);

        $weekDates = [];
        for ($d = $monday->copy(); $d->lte($sunday); $d->addDay()) {
            $weekDates[] = $d->toDateString();
        }

        // Fetch positions for each day
        $weekPositions = [];
        foreach ($weekDates as $wd) {
            $pos = PlanetaryPosition::forDate($wd)->orderBy('body')->get();
            if ($pos->isNotEmpty()) {
                $weekPositions[$wd] = $pos;
            }
        }

        if (empty($weekPositions)) {
            throw new \RuntimeException('No planetary positions found for this week.');
        }

        // Use Monday (or first available day) as representative
        $mondayDate    = array_key_first($weekPositions);
        $mondayPos     = $weekPositions[$mondayDate];
        $natalPlanets  = $profile->natalChart?->planets ?? [];
        $houseCusps    = $profile->natalChart?->houses ?? [];

        // ── Transit-to-natal: 7-day merge, unique per combo, best orb day ─
        $transitNatalAspects = [];
        if (! empty($natalPlanets)) {
            $bestByKey = []; // key => ['asp' => ..., 'date' => ...]
            foreach ($weekDates as $dayDate) {
                $dayPos = $weekPositions[$dayDate] ?? null;
                if (! $dayPos) {
                    continue;
                }
                $dayTransit = $dayPos->map(fn ($p) => [
                    'body'          => $p->body,
                    'longitude'     => $p->longitude,
                    'speed'         => $p->speed,
                    'sign'          => (int) floor($p->longitude / 30),
                    'is_retrograde' => $p->is_retrograde,
                ])->values()->all();

                foreach ($this->calculator->transitToNatal($dayTransit, $natalPlanets) as $asp) {
                    $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                    if (! isset($bestByKey[$k]) || $asp['orb'] < $bestByKey[$k]['asp']['orb']) {
                        $bestByKey[$k] = ['asp' => $asp, 'date' => $dayDate];
                    }
                }
            }

            // Sort: slow planets first (body >= 5), then fast; within each group by orb
            usort($bestByKey, function ($a, $b) {
                $aSlow = $a['asp']['transit_body'] >= 5 ? 0 : 1;
                $bSlow = $b['asp']['transit_body'] >= 5 ? 0 : 1;
                if ($aSlow !== $bSlow) {
                    return $aSlow - $bSlow;
                }
                return $a['asp']['orb'] <=> $b['asp']['orb'];
            });

            $top7 = array_slice($bestByKey, 0, 7);
            $transitNatalAspects = array_map(
                fn ($item) => new TransitAspectDTO(
                    transitBody: $item['asp']['transit_body'],
                    transitName: PlanetaryPosition::BODY_NAMES[$item['asp']['transit_body']] ?? '',
                    natalBody:   $item['asp']['natal_body'],
                    natalName:   PlanetaryPosition::BODY_NAMES[$item['asp']['natal_body']] ?? '',
                    aspect:      $item['asp']['aspect'],
                    orb:         $item['asp']['orb'],
                    peakDate:    $item['date'],
                ),
                $top7,
            );
        }

        // ── Retrogrades (Monday positions, Mercury-Saturn) ──────────────
        $retrogradeModels = $mondayPos
            ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
            ->values();

        $retrogrades = $retrogradeModels->map(fn ($p) => new RetrogradePlanetDTO(
            body:      $p->body,
            name:      PlanetaryPosition::BODY_NAMES[$p->body] ?? '',
            signIndex: (int) floor($p->longitude / 30),
            signName:  PlanetaryPosition::SIGN_NAMES[(int) floor($p->longitude / 30)] ?? '',
        ))->all();

        // ── Detect lunation (max 1 total per week) ──────────────────────
        $lunationDTOs = $this->lunationDetector->detect($weekPositions, 1);
        $lunation = ! empty($lunationDTOs) ? $lunationDTOs[0] : null;

        // ── Key dates ───────────────────────────────────────────────────
        $keyDates = $this->keyDatesBuilder->build(
            $transitNatalAspects,
            $lunation ? [$lunation] : [],
        );

        // ── Areas of life (7-day average) ───────────────────────────────
        $catScoreSums = array_fill(0, count(AreasOfLifeScorer::CATEGORIES), 0.0);
        $catScoreDays = 0;

        foreach ($weekDates as $dayDate) {
            $dayPos = $weekPositions[$dayDate] ?? null;
            if (! $dayPos) {
                continue;
            }

            $dayTransit = $dayPos->map(fn ($p) => [
                'body'          => $p->body,
                'longitude'     => $p->longitude,
                'speed'         => $p->speed,
                'sign'          => (int) floor($p->longitude / 30),
                'is_retrograde' => $p->is_retrograde,
            ])->values()->all();

            $dayRxBodies = $dayPos
                ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
                ->pluck('body')->toArray();

            $dayAspects = $this->calculator->transitToNatal($dayTransit, $natalPlanets);
            $dayScores  = $this->scorer->buildDayScores($dayAspects, $dayRxBodies);

            // Score each category for this day and accumulate
            foreach (AreasOfLifeScorer::CATEGORIES as $i => $cat) {
                $score      = 0.0;
                $rulerCount = 0;
                foreach ($cat['houses'] as $hIdx) {
                    $cuspLon = $houseCusps[$hIdx] ?? null;
                    if ($cuspLon === null) {
                        continue;
                    }
                    $signIdx   = (int) floor(fmod($cuspLon, 360) / 30);
                    $rulerBody = AreasOfLifeScorer::SIGN_RULERS[$signIdx] ?? null;
                    if ($rulerBody !== null) {
                        $score += $dayScores[$rulerBody] ?? 0;
                        $rulerCount++;
                    }
                }
                $catScoreSums[$i] += $rulerCount > 1 ? $score / $rulerCount : $score;
            }
            $catScoreDays++;
        }

        // Average and score
        $avgNatalBodyScores = [];
        if ($catScoreDays > 0) {
            foreach (AreasOfLifeScorer::CATEGORIES as $i => $cat) {
                $catScoreSums[$i] /= $catScoreDays;
            }
        }

        // Build AreaOfLifeDTO[] from averaged category scores
        $areasOfLife = [];
        foreach (AreasOfLifeScorer::CATEGORIES as $i => $cat) {
            $avgScore = $catScoreSums[$i];
            $score100 = max(0, min(100, (int) round(50.0 + ($avgScore / 4.0) * 50.0)));

            $rating = 0;
            foreach ([[75, 5], [55, 4], [42, 3], [30, 2]] as [$min, $r]) {
                if ($score100 >= $min) { $rating = $r; break; }
            }

            $areasOfLife[] = new \App\DataTransfer\Horoscope\AreaOfLifeDTO(
                slug:      $cat['slug'],
                name:      $cat['name'],
                score100:  $score100,
                rating:    $rating,
                maxRating: AreasOfLifeScorer::MAX_RATING,
            );
        }

        // ── Planet positions DTOs (Monday) ──────────────────────────────
        $positionDTOs = $mondayPos->map(fn ($p) => new PlanetPositionDTO(
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

        return new WeeklyHoroscopeDTO(
            weekStart:           $monday->toDateString(),
            weekEnd:             $sunday->toDateString(),
            profileName:         $profileName,
            positions:           $positionDTOs,
            transitNatalAspects: $transitNatalAspects,
            retrogrades:         $retrogrades,
            lunation:            $lunation,
            keyDates:            $keyDates,
            areasOfLife:         $areasOfLife,
        );
    }
}
