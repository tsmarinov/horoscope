<?php

namespace App\Services\Horoscope;

use App\DataTransfer\Horoscope\AreaOfLifeDTO;
use App\DataTransfer\Horoscope\LunationDTO;
use App\DataTransfer\Horoscope\MonthlyHoroscopeDTO;
use App\DataTransfer\Horoscope\PlanetPositionDTO;
use App\DataTransfer\Horoscope\RetrogradePlanetDTO;
use App\DataTransfer\Horoscope\TransitAspectDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Services\AspectCalculator;
use App\Services\Horoscope\Shared\AreasOfLifeScorer;
use App\Services\Horoscope\Shared\KeyDatesBuilder;
use App\Services\Horoscope\Shared\LunationDetector;
use App\Services\Horoscope\Shared\ProgressedMoonCalculator;
use Carbon\Carbon;

class MonthlyHoroscopeService
{
    // Personal natal planet bodies (Sun-Mars)
    private const PERSONAL_BODIES = [0, 1, 2, 3, 4];

    public function __construct(
        private readonly AspectCalculator $calculator,
        private readonly AreasOfLifeScorer $scorer,
        private readonly LunationDetector $lunationDetector,
        private readonly KeyDatesBuilder $keyDatesBuilder,
        private readonly ProgressedMoonCalculator $progressedMoonCalculator,
    ) {}

    public function build(Profile $profile, string $date): MonthlyHoroscopeDTO
    {
        $monthStart = Carbon::parse($date)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        // Collect all dates in month
        $monthDates = [];
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $monthDates[] = $d->toDateString();
        }

        // Fetch positions for each day
        $monthPositions = [];
        foreach ($monthDates as $md) {
            $pos = PlanetaryPosition::forDate($md)->orderBy('body')->get();
            if ($pos->isNotEmpty()) {
                $monthPositions[$md] = $pos;
            }
        }

        if (empty($monthPositions)) {
            throw new \RuntimeException("No planetary positions found for {$monthStart->format('F Y')}.");
        }

        // 1st available day as representative
        $firstDate = array_key_first($monthPositions);
        $firstPos  = $monthPositions[$firstDate];

        $natalPlanets = $profile->natalChart?->planets ?? [];
        $houseCusps   = $profile->natalChart?->houses ?? [];

        // ── Transit-to-natal: 30-day merge ──────────────────────────────
        $bestByKey  = []; // key => ['asp' => ..., 'date' => ..., 'days' => ...]
        $countByKey = []; // key => number of days active

        foreach ($monthDates as $dayDate) {
            $dayPos = $monthPositions[$dayDate] ?? null;
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
                // Personal natal bodies always included; outer natal bodies only for slow transiting planets
                $isSlowTransit = $asp['transit_body'] >= 5;
                if (! $isSlowTransit && ! in_array($asp['natal_body'], self::PERSONAL_BODIES, true)) {
                    continue;
                }
                $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                $countByKey[$k] = ($countByKey[$k] ?? 0) + 1;
                if (! isset($bestByKey[$k]) || $asp['orb'] < $bestByKey[$k]['asp']['orb']) {
                    $bestByKey[$k] = ['asp' => $asp, 'date' => $dayDate];
                }
            }
        }

        // Filter: fast planets only if active >= 14 days; slow always pass
        $filtered = [];
        foreach ($bestByKey as $k => $item) {
            $isFast = $item['asp']['transit_body'] < 5;
            if ($isFast && ($countByKey[$k] ?? 0) < 14) {
                continue;
            }
            $item['days'] = $countByKey[$k] ?? 1;
            $filtered[$k] = $item;
        }

        // Sort: slow first, then fast; within each group by orb
        usort($filtered, function ($a, $b) {
            $aSlow = $a['asp']['transit_body'] >= 5 ? 0 : 1;
            $bSlow = $b['asp']['transit_body'] >= 5 ? 0 : 1;
            if ($aSlow !== $bSlow) {
                return $aSlow - $bSlow;
            }
            return $a['asp']['orb'] <=> $b['asp']['orb'];
        });

        $top15 = array_slice(array_values($filtered), 0, 15);
        $transitNatalAspects = array_map(
            fn ($item) => new TransitAspectDTO(
                transitBody: $item['asp']['transit_body'],
                transitName: PlanetaryPosition::BODY_NAMES[$item['asp']['transit_body']] ?? '',
                natalBody:   $item['asp']['natal_body'],
                natalName:   PlanetaryPosition::BODY_NAMES[$item['asp']['natal_body']] ?? '',
                aspect:      $item['asp']['aspect'],
                orb:         $item['asp']['orb'],
                peakDate:    $item['date'],
                activeDays:  $item['days'],
            ),
            $top15,
        );

        // ── Retrogrades (1st of month, Mercury-Saturn) ──────────────────
        $retrogradeModels = $firstPos
            ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
            ->values();

        $retrogrades = $retrogradeModels->map(fn ($p) => new RetrogradePlanetDTO(
            body:      $p->body,
            name:      PlanetaryPosition::BODY_NAMES[$p->body] ?? '',
            signIndex: (int) floor($p->longitude / 30),
            signName:  PlanetaryPosition::SIGN_NAMES[(int) floor($p->longitude / 30)] ?? '',
        ))->all();

        // ── Lunations (both NM and FM) ──────────────────────────────────
        $lunations = $this->lunationDetector->detect($monthPositions, 1);

        // Resolve natal house for each lunation
        $lunationsWithHouse = array_map(function (LunationDTO $lun) use ($houseCusps) {
            $house = $this->findHouse($lun->longitude, $houseCusps);
            if ($house === null) {
                return $lun;
            }
            return new LunationDTO(
                date:      $lun->date,
                type:      $lun->type,
                name:      $lun->name,
                signIndex: $lun->signIndex,
                signName:  $lun->signName,
                longitude: $lun->longitude,
                house:     $house,
            );
        }, $lunations);

        // ── Progressed Moon ─────────────────────────────────────────────
        $progressedMoon = null;
        $birthDate = $profile->birth_date;
        if ($birthDate) {
            $natalMoonLon = null;
            foreach ($natalPlanets as $p) {
                if (($p['body'] ?? -1) === 1) {
                    $natalMoonLon = (float) ($p['longitude'] ?? 0);
                    break;
                }
            }
            if ($natalMoonLon !== null) {
                $progressedMoon = $this->progressedMoonCalculator->calculate(
                    $birthDate,
                    $natalMoonLon,
                    $monthStart,
                    $natalPlanets,
                    $houseCusps,
                );
            }
        }

        // ── Key dates ───────────────────────────────────────────────────
        $keyDates = $this->keyDatesBuilder->build($transitNatalAspects, $lunationsWithHouse);

        // ── Areas of life (30-day average, orb-weighted) ────────────────
        $catScoreSums = array_fill(0, count(AreasOfLifeScorer::CATEGORIES), 0.0);
        $catScoreDays = 0;

        foreach ($monthDates as $dayDate) {
            $dayPos = $monthPositions[$dayDate] ?? null;
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

        // Average and build AreaOfLifeDTO[]
        $areasOfLife = [];
        foreach (AreasOfLifeScorer::CATEGORIES as $i => $cat) {
            $avgScore = $catScoreDays > 0 ? $catScoreSums[$i] / $catScoreDays : 0;
            $score100 = max(0, min(100, (int) round(50.0 + ($avgScore / 4.0) * 50.0)));

            $rating = 0;
            foreach ([[75, 5], [55, 4], [42, 3], [30, 2]] as [$min, $r]) {
                if ($score100 >= $min) { $rating = $r; break; }
            }

            $areasOfLife[] = new AreaOfLifeDTO(
                slug:      $cat['slug'],
                name:      $cat['name'],
                score100:  $score100,
                rating:    $rating,
                maxRating: AreasOfLifeScorer::MAX_RATING,
            );
        }

        // ── Planet positions DTOs (1st of month) ────────────────────────
        $positionDTOs = $firstPos->map(fn ($p) => new PlanetPositionDTO(
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

        return new MonthlyHoroscopeDTO(
            monthStart:          $monthStart->toDateString(),
            monthEnd:            $monthEnd->toDateString(),
            profileName:         $profileName,
            positions:           $positionDTOs,
            transitNatalAspects: $transitNatalAspects,
            retrogrades:         $retrogrades,
            lunations:           $lunationsWithHouse,
            progressedMoon:      $progressedMoon,
            keyDates:            $keyDates,
            areasOfLife:         $areasOfLife,
        );
    }

    /**
     * Find natal house number (1-indexed) for a given longitude.
     */
    private function findHouse(float $longitude, array $houseCusps): ?int
    {
        if (count($houseCusps) < 12) {
            return null;
        }

        $lon = fmod($longitude + 360, 360);

        for ($h = 0; $h < 12; $h++) {
            $cusp     = fmod($houseCusps[$h] + 360, 360);
            $nextCusp = fmod($houseCusps[($h + 1) % 12] + 360, 360);

            if ($cusp <= $nextCusp) {
                if ($lon >= $cusp && $lon < $nextCusp) {
                    return $h + 1;
                }
            } else {
                if ($lon >= $cusp || $lon < $nextCusp) {
                    return $h + 1;
                }
            }
        }

        return 1;
    }
}
