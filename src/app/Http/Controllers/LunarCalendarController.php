<?php

namespace App\Http\Controllers;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\HouseCalculator;
use Carbon\Carbon;

class LunarCalendarController extends Controller
{
    private const MOON_PHASES = [
        [0,     22.5,  '🌑', 'New Moon'],
        [22.5,  67.5,  '🌒', 'Waxing Crescent'],
        [67.5,  112.5, '🌓', 'First Quarter'],
        [112.5, 157.5, '🌔', 'Waxing Gibbous'],
        [157.5, 202.5, '🌕', 'Full Moon'],
        [202.5, 247.5, '🌖', 'Waning Gibbous'],
        [247.5, 292.5, '🌗', 'Last Quarter'],
        [292.5, 360,   '🌘', 'Waning Crescent'],
    ];

    public function redirect()
    {
        return redirect()->route('lunar.show', [
            now()->year,
            str_pad(now()->month, 2, '0', STR_PAD_LEFT),
        ]);
    }

    public function show(int $year, int $month)
    {
        $data = $this->buildMonthData($year, $month);
        return view('lunar.show', $data);
    }

    public function pdf(int $year, int $month)
    {
        $data     = $this->buildMonthData($year, $month);
        $pdf      = \PDF::loadView('lunar.pdf', $data)
            ->setPaper('a4')
            ->setOption('encoding', 'UTF-8')
            ->setOption('margin-top', '15')
            ->setOption('margin-bottom', '22')
            ->setOption('margin-left', '20')
            ->setOption('margin-right', '20');
        $filename = 'lunar-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->stream($filename);
    }

    private function buildMonthData(int $year, int $month): array
    {
        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2040) {
            abort(404);
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();
        $today = now()->toDateString();

        // ── Load Sun + Moon positions (±3 days for cross-boundary lunations) ──
        $queryStart = $start->copy()->subDays(3);
        $queryEnd   = $end->copy()->addDays(3);

        $allPositions = PlanetaryPosition::whereBetween('date', [
            $queryStart->toDateString(),
            $queryEnd->toDateString(),
        ])
            ->whereIn('body', [PlanetaryPosition::SUN, PlanetaryPosition::MOON])
            ->orderBy('date')
            ->orderBy('body')
            ->get()
            ->groupBy('date');

        // ── Build per-day array ───────────────────────────────────────────
        $days = [];
        for ($d = 1; $d <= $end->day; $d++) {
            $dateStr = $start->format('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dayPos  = $allPositions->get($dateStr, collect());
            $moon    = $dayPos->firstWhere('body', PlanetaryPosition::MOON);
            $sun     = $dayPos->firstWhere('body', PlanetaryPosition::SUN);

            $moonLon    = $moon?->longitude ?? 0.0;
            $sunLon     = $sun?->longitude ?? 0.0;
            $elongation = fmod($moonLon - $sunLon + 360, 360);

            [$phaseEmoji, $phaseName] = $this->moonPhase($elongation);

            $signIdx  = (int) floor($moonLon / 30);
            $lunarDay = max(1, (int) ceil($elongation / (360 / 29.53)));

            $distNew  = min($elongation, 360 - $elongation);
            $distFull = abs($elongation - 180);

            $days[$d] = [
                'date'       => $dateStr,
                'carbon'     => Carbon::parse($dateStr),
                'moon_lon'   => $moonLon,
                'elongation' => $elongation,
                'phase'      => $phaseEmoji,
                'phase_name' => $phaseName,
                'sign_idx'   => $signIdx,
                'lunar_day'  => $lunarDay,
                'new_moon'   => false,
                'full_moon'  => false,
                'dist_new'   => $distNew,
                'dist_full'  => $distFull,
                'is_today'   => $dateStr === $today,
            ];
        }

        // ── Build extended day data for cross-boundary lunation detection ──
        $extDays = [];
        for ($cur = $queryStart->copy(); $cur->lte($queryEnd); $cur->addDay()) {
            $ds     = $cur->toDateString();
            $dayPos = $allPositions->get($ds, collect());
            $moon   = $dayPos->firstWhere('body', PlanetaryPosition::MOON);
            $sun    = $dayPos->firstWhere('body', PlanetaryPosition::SUN);
            if (! $moon || ! $sun) {
                continue;
            }
            $elong = fmod($moon->longitude - $sun->longitude + 360, 360);
            $extDays[$ds] = [
                'dist_new'  => min($elong, 360 - $elong),
                'dist_full' => abs($elong - 180),
            ];
        }

        $this->resolveLunations($days, $extDays, $start->toDateString(), $end->toDateString());

        $prevMonth    = $start->copy()->subMonth();
        $nextMonth    = $start->copy()->addMonth();
        $firstWeekday = $start->dayOfWeekIso; // 1=Mon … 7=Sun

        // ── Profile (most-recently-used) ─────────────────────────────────
        $profile = null;
        if (auth()->check()) {
            $profile = Profile::where('user_id', auth()->id())
                ->whereNotNull('last_used_at')
                ->orderByDesc('last_used_at')
                ->first()
                ?? Profile::where('user_id', auth()->id())->orderBy('first_name')->first();
        }

        // ── Personalized lunation cards ───────────────────────────────────
        $lunationCards = [];
        if ($profile && $profile->natalChart && is_array($profile->natalChart->houses)) {
            $houseCalculator = new HouseCalculator();
            $cusps           = $profile->natalChart->houses;
            $natalPlanets    = $profile->natalChart->planets ?? [];
            $gender          = TextBlock::resolveGender($profile->gender?->value ?? null);

            foreach ($days as $day) {
                if (! $day['new_moon'] && ! $day['full_moon']) {
                    continue;
                }

                $type     = $day['new_moon'] ? 'New Moon' : 'Full Moon';
                $icon     = $day['new_moon'] ? '🌑' : '🌕';
                $lunKey   = $day['new_moon'] ? 'new_moon' : 'full_moon';
                $signName = PlanetaryPosition::SIGN_NAMES[$day['sign_idx']] ?? '';

                [$house]  = $houseCalculator->assignHouses($cusps, [$day['moon_lon']]);
                $houseOrd = $this->ordinal($house);
                $textKey  = $lunKey . '_house_' . $house;
                $block    = TextBlock::pickForProfile($textKey, 'lunation_house', 'en', $gender, $profile->id);
                $text     = $block ? trim(strip_tags($block->text)) : null;

                $conjunctions = [];
                foreach ($natalPlanets as $np) {
                    $diff = abs($day['moon_lon'] - $np['longitude']);
                    if ($diff > 180) {
                        $diff = 360 - $diff;
                    }
                    if ($diff <= 5.0) {
                        $conjunctions[] = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '?';
                    }
                }

                $lunationCards[] = [
                    'type'         => $type,
                    'icon'         => $icon,
                    'sign_name'    => $signName,
                    'house'        => $house,
                    'house_ord'    => $houseOrd,
                    'carbon'       => $day['carbon'],
                    'text'         => $text,
                    'conjunctions' => $conjunctions,
                ];
            }
        }

        // ── Moon sign texts (only signs appearing this month) ────────────
        $moonTexts = [];
        $seenSigns = [];
        $gender    = $profile ? TextBlock::resolveGender($profile->gender?->value ?? null) : null;
        foreach ($days as $day) {
            $idx = $day['sign_idx'];
            if (! isset($seenSigns[$idx])) {
                $seenSigns[$idx] = true;
                $signName        = PlanetaryPosition::SIGN_NAMES[$idx] ?? '';
                $block           = TextBlock::pickForProfile(
                    'moon_in_' . strtolower($signName),
                    'lunar_day',
                    'en',
                    $gender,
                    $profile?->id
                );
                $moonTexts[$idx] = $block ? trim(strip_tags($block->text)) : null;
            }
        }

        return compact(
            'days', 'year', 'month', 'start',
            'prevMonth', 'nextMonth', 'firstWeekday',
            'profile', 'lunationCards', 'moonTexts', 'today'
        );
    }

    // ── Lunation resolver ────────────────────────────────────────────────

    private function resolveLunations(array &$days, array $extDays, string $monthStart, string $monthEnd): void
    {
        foreach (['new' => 'dist_new', 'full' => 'dist_full'] as $type => $distKey) {
            $field      = $type . '_moon';
            $threshold  = 22.5;

            // Candidates across extended range (date strings, sorted)
            $candidates = [];
            foreach ($extDays as $ds => $data) {
                if ($data[$distKey] < $threshold) {
                    $candidates[] = $ds;
                }
            }
            sort($candidates);

            // Group into consecutive-date runs
            $runs = [];
            $run  = [];
            foreach ($candidates as $ds) {
                if (empty($run) || (strtotime($ds) - strtotime(end($run))) === 86400) {
                    $run[] = $ds;
                } else {
                    $runs[] = $run;
                    $run    = [$ds];
                }
            }
            if (! empty($run)) {
                $runs[] = $run;
            }

            foreach ($runs as $run) {
                // Find date with minimum distance in this run
                $winner = null;
                foreach ($run as $ds) {
                    if ($winner === null || $extDays[$ds][$distKey] < $extDays[$winner][$distKey]) {
                        $winner = $ds;
                    }
                }

                // Only mark if winner falls within the current month
                if ($winner !== null && $winner >= $monthStart && $winner <= $monthEnd) {
                    $dayNum = (int) Carbon::parse($winner)->day;
                    if (isset($days[$dayNum])) {
                        $days[$dayNum][$field] = true;
                    }
                }
            }
        }
    }

    // ── Moon phase ───────────────────────────────────────────────────────

    private function moonPhase(float $elongation): array
    {
        foreach (self::MOON_PHASES as [$from, $to, $emoji, $name]) {
            if ($elongation >= $from && $elongation < $to) {
                return [$emoji, $name];
            }
        }
        return ['🌑', 'New Moon'];
    }

    // ── Ordinal helper ───────────────────────────────────────────────────

    private function ordinal(int $n): string
    {
        $suffix = match (true) {
            $n % 100 >= 11 && $n % 100 <= 13 => 'th',
            $n % 10 === 1                     => 'st',
            $n % 10 === 2                     => 'nd',
            $n % 10 === 3                     => 'rd',
            default                           => 'th',
        };
        return $n . $suffix;
    }
}
