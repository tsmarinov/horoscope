<?php

namespace App\Console\Commands;

use App\DataTransfer\Horoscope\SolarReturnDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\Horoscope\SolarReturnService;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the Solar Return / Yearly horoscope.
 *
 * Renders a 72-char wide ASCII UI block in the console showing:
 *   - Solar return header (date, city, ASC)
 *   - Bi-wheel placeholder (natal inner + solar outer)
 *   - Solar return factors (ASC dispositor, top aspects, progressed Moon/Sun, solar arc)
 *   - Eclipses & lunations for the year
 *   - Key transits by quarter
 *   - Footer with summary
 */
class UiSolarReturn extends Command
{
    protected $signature = 'horoscope:ui-solar
                            {--profile= : Profile ID}
                            {year?   : Year (default: current year)}
                            {--city=       : City ID for solar return location (overrides profile solar return city)}
                            {--simplified  : Show 1-sentence simplified texts (uses _short sections)}
                            {--ai          : Generate yearly synthesis with Claude (AI L1)}';

    protected $description = 'Render a solar return / yearly horoscope in pseudo-browser console UI';

    // ── Layout constants ─────────────────────────────────────────────────
    private const W  = 72;
    private const IW = 68;

    // ── Glyph maps ───────────────────────────────────────────────────────
    private const BODY_GLYPHS = [
        0 => '☉', 1 => '☽',  2 => '☿', 3 => '♀',  4 => '♂',
        5 => '♃', 6 => '♄',  7 => '♅', 8 => '♆',  9 => '♇',
       10 => '⚷', 11 => '☊', 12 => '⚸',
    ];

    private const SIGN_GLYPHS = [
        0 => '♈', 1 => '♉', 2 => '♊',  3 => '♋',
        4 => '♌', 5 => '♍', 6 => '♎',  7 => '♏',
        8 => '♐', 9 => '♑', 10 => '♒', 11 => '♓',
    ];

    private const ASPECT_GLYPHS = [
        'conjunction'      => '☌', 'opposition'       => '☍',
        'trine'            => '△', 'square'           => '□',
        'sextile'          => '⚹', 'quincunx'         => '⚻',
        'semi_sextile'     => '∠', 'mutual_reception' => '⇌',
    ];

    private const MOON_PHASE_EMOJIS = [
        'new_moon'        => '🌑',
        'waxing_crescent' => '🌒',
        'first_quarter'   => '🌓',
        'waxing_gibbous'  => '🌔',
        'full_moon'       => '🌕',
        'waning_gibbous'  => '🌖',
        'last_quarter'    => '🌗',
        'waning_crescent' => '🌘',
    ];

    private const SIGN_ELEMENTS = [
        0 => 'fire',  1 => 'earth', 2 => 'air',   3 => 'water',
        4 => 'fire',  5 => 'earth', 6 => 'air',   7 => 'water',
        8 => 'fire',  9 => 'earth', 10 => 'air',  11 => 'water',
    ];

    private const ELEMENT_LABELS = [
        'fire' => 'Fire', 'earth' => 'Earth', 'air' => 'Air', 'water' => 'Water',
    ];

    // Traditional sign rulers (sign index => body index)
    private const SIGN_RULERS = [
        0  => 4, // Aries -> Mars
        1  => 3, // Taurus -> Venus
        2  => 2, // Gemini -> Mercury
        3  => 1, // Cancer -> Moon
        4  => 0, // Leo -> Sun
        5  => 2, // Virgo -> Mercury
        6  => 3, // Libra -> Venus
        7  => 9, // Scorpio -> Pluto
        8  => 5, // Sagittarius -> Jupiter
        9  => 6, // Capricorn -> Saturn
        10 => 7, // Aquarius -> Uranus
        11 => 8, // Pisces -> Neptune
    ];

    // ── Entry point ──────────────────────────────────────────────────────

    public function handle(SolarReturnService $service): int
    {
        $year       = (int) ($this->argument('year') ?: now()->year);
        $simplified = (bool) $this->option('simplified');

        $profile = Profile::with(['birthCity', 'solarReturnCity'])->find($this->option('profile'));
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        // Override solar return city if --city is provided
        if ($cityId = $this->option('city')) {
            $overrideCity = \App\Models\City::find((int) $cityId);
            if ($overrideCity === null) {
                $this->error("City #{$cityId} not found.");
                return self::FAILURE;
            }
            $profile->setRelation('solarReturnCity', $overrideCity);
        }

        $gender = TextBlock::resolveGender($profile->gender?->value ?? null);

        $birthCityName  = $profile->birthCity?->name ?? '—';
        $solarCity      = $profile->solarReturnCity ?? $profile->birthCity;
        $solarCityName  = $solarCity?->name ?? $birthCityName;
        $birthDateTime  = ($profile->birth_date?->format('j M Y') ?? '—')
                        . ($profile->birth_time ? '  ' . $profile->birth_time : '');

        try {
            $dto = $service->build($profile, $year);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        // Format solar return datetime: local time first, UTC in parens
        $srUtc = \Carbon\Carbon::parse($dto->solarReturnUtcIso, 'UTC');
        $srTz  = $solarCity?->timezone ?? 'UTC';
        $srLocal = $srUtc->copy()->setTimezone($srTz);
        $srDisplay = $srLocal->format('j M Y') . " \u{00B7} " . $srLocal->format('H:i')
                   . ($srTz !== 'UTC' ? ' (' . $srUtc->format('H:i') . ' UTC)' : ' UTC');

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread(
            "  \u{2609} SOLAR RETURN \u{00B7} {$year}",
            "{$srDisplay}  "
        )));
        $this->put($this->row("  Born: {$birthDateTime} \u{00B7} {$birthCityName}"));
        if ($solarCityName !== $birthCityName) {
            $this->put($this->row($this->spread('', "Solar: {$solarCityName}  ")));
        }

        // Natal Sun sign
        $natalSunSign = $this->findNatalSunSign($dto);
        $ascGlyph     = self::SIGN_GLYPHS[$dto->solarAscSignIndex] ?? '';
        $progMoonGlyph = self::SIGN_GLYPHS[$dto->progressedMoon['sign'] ?? 0] ?? '';
        $progMoonSign  = $dto->progressedMoon['signName'] ?? '';

        $subLine = "  Solar ASC: {$ascGlyph} {$dto->solarAscSignName}"
                 . "  \u{00B7}  \u{2609} natal {$natalSunSign}"
                 . "  \u{00B7}  \u{263D} {$progMoonGlyph} {$progMoonSign}";
        $this->put($this->row($subLine));
        $this->put($this->divider());

        // ── Bi-wheel placeholder ─────────────────────────────────────────
        $this->put($this->row($this->center("NATAL + SOLAR RETURN BI-WHEEL \u{00B7} {$year}")));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Planet positions table ────────────────────────────────────────
        $natalPlanets = $profile->natalChart?->planets ?? [];
        $natalHouses  = $profile->natalChart?->houses  ?? [];
        $this->renderPlanetTable($natalPlanets, $dto->solarPlanets, $natalHouses, $dto);

        // ── AI Synthesis (under planet table) ────────────────────────────
        if ($this->option('ai')) {
            /** @var \App\Services\Ai\HoroscopeSynthesisService $synthesisService */
            $synthesisService = app(\App\Services\Ai\HoroscopeSynthesisService::class);
            $aiResponse = $synthesisService->solar(
                dto:        $dto,
                natalPlanets: $natalPlanets,
                natalHouses:  $natalHouses,
                year:         $year,
                simplified:   $simplified,
                profileId:    $profile->id,
                gender:       $gender,
            );
            if ($aiResponse) {
                $wasCached = $aiResponse->inputTokens === 0;
                if ($wasCached) {
                    $this->line("  <fg=gray>[AI synthesis: cached]</>");
                } else {
                    $cost = number_format($aiResponse->costUsd, 5);
                    $this->line("  <fg=gray>[AI synthesis: {$aiResponse->inputTokens} in / {$aiResponse->outputTokens} out / \${$cost}]</>");
                }
                $synthesis = $aiResponse->text;
            }
            if ($synthesis ?? null) {
                $this->put($this->divider());
                $this->put($this->row($this->spread("  \u{2726}  " . ui_trans('solar.ai_overview', $gender), 'AI  ')));
                $this->put($this->row(''));
                foreach (preg_split('/\n{2,}/', trim($synthesis)) as $para) {
                    foreach ($this->wrap(trim($para), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                    $this->put($this->row(''));
                }
            }
        }

        // ── Solar ASC analysis ────────────────────────────────────────────
        if (! empty($natalHouses)) {
            $this->renderSolarAscAnalysis($dto, $natalHouses, $simplified, $gender);
        } else {
            $this->put($this->row('  🔒 Solar ASC analysis — add birth time & place to unlock'));
            $this->put($this->row(''));
        }

        $this->put($this->divider());

        // ── Build solar house rulers map ──────────────────────────────────
        // solarRuledHouses[$bodyId] = [1, 5, ...] (solar house numbers)
        $solarRuledHouses = [];
        foreach ($dto->solarHouses as $hIdx => $cuspLon) {
            $signIdx = (int) floor((float) $cuspLon / 30);
            $ruler   = self::SIGN_RULERS[$signIdx] ?? null;
            if ($ruler !== null) {
                $solarRuledHouses[$ruler][] = $hIdx + 1;
            }
        }

        // ── Angular activations (≤1°) ─────────────────────────────────────
        $angularHits = [];

        foreach ($dto->solarPlanets as $sp) {
            foreach ($natalHouses as $hIdx => $cuspLon) {
                $orb = $this->lonOrb($sp->longitude, (float) $cuspLon);
                if ($orb <= 1.0) {
                    $angularHits[] = [
                        'orb'   => $orb,
                        'label' => sprintf(
                            "  \u{2609} Solar %s %s \u{2192} natal H%d cusp  \u{00B7}  %.1f\u{00B0}",
                            self::BODY_GLYPHS[$sp->body] ?? '', $sp->name, $hIdx + 1, $orb
                        ),
                    ];
                }
            }
        }
        foreach ($natalPlanets as $np) {
            $nBody  = (int) ($np['body'] ?? -1);
            $nLon   = (float) ($np['longitude'] ?? 0);
            $nName  = \App\Models\PlanetaryPosition::BODY_NAMES[$nBody] ?? '';
            $nGlyph = self::BODY_GLYPHS[$nBody] ?? '';
            foreach ($dto->solarHouses as $hIdx => $cuspLon) {
                $orb = $this->lonOrb($nLon, (float) $cuspLon);
                if ($orb <= 1.0) {
                    $angularHits[] = [
                        'orb'   => $orb,
                        'label' => sprintf(
                            "  \u{2295} Natal %s %s \u{2192} solar H%d cusp  \u{00B7}  %.1f\u{00B0}",
                            $nGlyph, $nName, $hIdx + 1, $orb
                        ),
                    ];
                }
            }
        }
        usort($angularHits, fn ($a, $b) => $a['orb'] <=> $b['orb']);

        // ── Solar Return Factors ─────────────────────────────────────────
        $this->put($this->row("  \u{25C6}  " . ui_trans('solar.factors', $gender)));
        $this->put($this->row(''));

        // 1. Angular activations
        if (! empty($angularHits)) {
            foreach ($angularHits as $hit) {
                $this->put($this->row($hit['label']));
            }
            $this->put($this->row(''));
        }

        // 2. Sort aspects: dispositor first, then slow (body≥5), then fast — each group by orb
        $dispositorBody = self::SIGN_RULERS[$dto->solarAscSignIndex] ?? -1;
        $aspects        = $dto->solarNatalAspects;

        $dispAspects  = array_filter($aspects, fn ($a) => $a->transitBody === $dispositorBody);
        $slowAspects  = array_filter($aspects, fn ($a) => $a->transitBody !== $dispositorBody && $a->transitBody >= 5);
        $fastAspects  = array_filter($aspects, fn ($a) => $a->transitBody !== $dispositorBody && $a->transitBody < 5);

        $dispAspects = array_values($dispAspects);
        $slowAspects = array_values($slowAspects);
        $fastAspects = array_values($fastAspects);
        usort($dispAspects, fn ($a, $b) => $a->orb <=> $b->orb);
        usort($slowAspects, fn ($a, $b) => $a->orb <=> $b->orb);
        usort($fastAspects, fn ($a, $b) => $a->orb <=> $b->orb);

        $sortedAspects = array_merge($dispAspects, $slowAspects, $fastAspects);

        foreach ($sortedAspects as $asp) {
            $tGlyph   = self::BODY_GLYPHS[$asp->transitBody] ?? '?';
            $nGlyph   = self::BODY_GLYPHS[$asp->natalBody] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$asp->aspect] ?? "\u{00B7}";
            $aspWord  = ui_trans('aspects.' . $asp->aspect, $gender) ?: ucfirst(str_replace('_', ' ', $asp->aspect));

            // Solar house ruler annotation
            $ruledHouses = $solarRuledHouses[$asp->transitBody] ?? [];
            $rulerTag    = ! empty($ruledHouses) ? '  · solar H' . implode('/H', $ruledHouses) . ' rul.' : '';

            $chip = "  {$tGlyph} {$asp->transitName}{$rulerTag}  {$aspGlyph} {$aspWord}  {$nGlyph} natal {$asp->natalName}";
            $this->put($this->row($chip));

            $key     = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
            $section = $simplified ? 'transit_natal_short' : 'transit_natal';
            $block   = TextBlock::pick($key, $section, 1, 'en', $gender);
            $text    = $block ? trim(strip_tags($block->text)) : null;

            if ($text) {
                $this->put($this->row(''));
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
            $this->put($this->row(''));
        }

        // 3 & 4. Progressed Moon + Sun
        $pmSign  = $dto->progressedMoon['signName'] ?? '';
        $pmHouse = $dto->progressedMoon['houseIndex'] ?? null;
        $pmGlyph = self::SIGN_GLYPHS[$dto->progressedMoon['sign'] ?? 0] ?? '';
        $pmHouseStr = $pmHouse ? " \u{00B7} H{$pmHouse}" : '';

        $psSign  = $dto->progressedSun['signName'] ?? '';
        $psHouse = $dto->progressedSun['houseIndex'] ?? null;
        $psGlyph = self::SIGN_GLYPHS[$dto->progressedSun['sign'] ?? 0] ?? '';
        $psHouseStr = $psHouse ? " \u{00B7} H{$psHouse}" : '';

        $this->put($this->row("  \u{2015}\u{2015} " . ui_trans('solar.progressions', $gender)));
        $this->put($this->row("  \u{1F319} Prog Moon {$pmGlyph} {$pmSign}{$pmHouseStr}"));
        $this->put($this->row("  \u{2609} Prog Sun  {$psGlyph} {$psSign}{$psHouseStr}"));
        $this->put($this->row(''));

        // 5. Solar Arc Directions
        if (! empty($dto->solarArcDirections)) {
            $this->put($this->row("  \u{2015}\u{2015} " . ui_trans('solar.arc_directions', $gender)));
            foreach ($dto->solarArcDirections as $dir) {
                $dGlyph   = self::BODY_GLYPHS[$dir->directedBody] ?? '?';
                $tGlyph   = self::BODY_GLYPHS[$dir->natalTargetBody] ?? '?';
                $aspGlyph = self::ASPECT_GLYPHS[$dir->aspect] ?? "\u{00B7}";
                $aspWord  = ui_trans('aspects.' . $dir->aspect, $gender) ?: ucfirst(str_replace('_', ' ', $dir->aspect));
                $this->put($this->row("  {$dGlyph} {$dir->directedName}  {$aspGlyph} {$aspWord}  {$tGlyph} natal {$dir->natalTargetName}"));
            }
            $this->put($this->row(''));
        }

        // ── Eclipses & Lunations ─────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row("  \u{1F311}  " . ui_trans('solar.lunations_title', $gender) . " \u{00B7} {$year}"));
        $this->put($this->row(''));

        if (empty($dto->lunations)) {
            $this->put($this->row('    No lunation data available.'));
        } else {
            foreach ($dto->lunations as $lun) {
                $dayStr   = \Carbon\Carbon::parse($lun->date)->format('j M');
                $emoji    = $lun->type === 'new_moon' ? "\u{1F311}" : "\u{1F315}";
                $typeLabel = __('lunar.lunations.' . $lun->type);
                $signG    = self::SIGN_GLYPHS[$lun->signIndex] ?? '';

                // Mark eclipse: lunation within 12° of North Node
                $nnRow = \App\Models\PlanetaryPosition::forDate($lun->date)
                    ->where('body', \App\Models\PlanetaryPosition::NNODE)->first();
                $eclipseTag = '';
                if ($nnRow) {
                    $diff = abs(fmod(abs($lun->longitude - $nnRow->longitude), 360));
                    if ($diff > 180) $diff = 360 - $diff;
                    if ($diff <= 12.0) $eclipseTag = '  ★ Eclipse';
                }

                $this->put($this->row("  \u{00B7} {$dayStr}  {$emoji} {$typeLabel} {$signG} {$lun->signName}{$eclipseTag}"));
            }
        }
        $this->put($this->row(''));

        // ── Key Transits by Quarter ──────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row("  \u{1F4C5}  " . ui_trans('solar.key_transits', $gender)));
        $this->put($this->row(''));

        foreach ($dto->quarters as $q) {
            $itemsStr = ! empty($q->items) ? implode('  \u{00B7}  ', $q->items) : 'no major events';
            $line = "  {$q->label}  \u{00B7}  {$itemsStr}";
            // If too long, wrap
            if (mb_strlen($line) > self::IW) {
                $this->put($this->row("  {$q->label}"));
                foreach ($q->items as $item) {
                    $this->put($this->row("    \u{00B7} {$item}"));
                }
            } else {
                $this->put($this->row($line));
            }
        }
        $this->put($this->row(''));

        // ── Footer ───────────────────────────────────────────────────────
        $this->put($this->divider());
        $rxLabel = ! empty($dto->retrogrades)
            ? implode(' ', array_map(
                fn ($rx) => (self::BODY_GLYPHS[$rx->body] ?? '') . 'Rx',
                $dto->retrogrades,
            ))
            : ui_trans('no_rx', $gender);
        $factorCount = count($dto->solarNatalAspects) + count($dto->solarArcDirections);
        $footer = "  solar  \u{00B7}  {$year}  \u{00B7}  {$dto->cityName}  \u{00B7}  {$factorCount} factors  \u{00B7}  {$rxLabel}";
        // If footer is too long, drop city name
        if (mb_strlen($footer) > self::IW) {
            $footer = "  solar  \u{00B7}  {$year}  \u{00B7}  {$factorCount} factors  \u{00B7}  {$rxLabel}";
        }
        $this->put($this->row($footer));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function findNatalSunSign(SolarReturnDTO $dto): string
    {
        foreach ($dto->solarPlanets as $pl) {
            if ($pl->body === PlanetaryPosition::SUN) {
                $glyph = self::SIGN_GLYPHS[$pl->signIndex] ?? '';
                return "{$glyph} {$pl->signName}";
            }
        }
        return '';
    }

    private function findHouseForLon(float $longitude, array $cusps): ?int
    {
        if (count($cusps) < 12) {
            return null;
        }

        $lon = fmod($longitude + 360, 360);

        for ($h = 0; $h < 12; $h++) {
            $cusp     = fmod($cusps[$h] + 360, 360);
            $nextCusp = fmod($cusps[($h + 1) % 12] + 360, 360);

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

    // ── Planet positions table ───────────────────────────────────────────

    /**
     * Two-column table: natal planets (left) | solar return planets (right).
     *
     * @param  array                $natalPlanets  raw natal arrays ['body','longitude','is_retrograde',...]
     * @param  PlanetPositionDTO[]  $solarPlanets
     */
    private function renderPlanetTable(array $natalPlanets, array $solarPlanets, array $natalHouses = [], SolarReturnDTO $dto = null): void
    {
        $this->put($this->divider());
        $hdr = $this->mbPad('  NATAL', 34) . $this->mbPad('  SOLAR RETURN', 34);
        $this->put($this->row($hdr));
        $this->put($this->row('  ' . str_repeat('─', 30) . '  ' . str_repeat('─', 30)));

        // Index solar planets by body for quick lookup
        $solarByBody = [];
        foreach ($solarPlanets as $sp) {
            $solarByBody[$sp->body] = $sp;
        }

        // Render rows for each natal planet
        foreach ($natalPlanets as $np) {
            $body = (int) ($np['body'] ?? -1);
            $lon  = (float) ($np['longitude'] ?? 0);
            $rx   = ! empty($np['is_retrograde']);

            $signIdx   = (int) floor($lon / 30);
            $signGlyph = self::SIGN_GLYPHS[$signIdx] ?? '';
            $signName  = \App\Models\PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $degInSign = fmod($lon, 30);
            $deg       = (int) $degInSign;
            $min       = (int) round(($degInSign - $deg) * 60);
            $name      = \App\Models\PlanetaryPosition::BODY_NAMES[$body] ?? '';
            $glyph     = self::BODY_GLYPHS[$body] ?? '';

            $natalCell = sprintf('%s %-8s %s %-11s %2d°%02d\'%s',
                $glyph, $name,
                $signGlyph, $signName,
                $deg, $min,
                $rx ? ' Rx' : '   '
            );

            $sp        = $solarByBody[$body] ?? null;
            $solarCell = '';
            if ($sp) {
                $sDeg = (int) $sp->degreeInSign;
                $sMin = (int) round(($sp->degreeInSign - $sDeg) * 60);
                $solarCell = sprintf('%s %-8s %s %-11s %2d°%02d\'%s',
                    self::BODY_GLYPHS[$sp->body] ?? '',
                    $sp->name,
                    self::SIGN_GLYPHS[$sp->signIndex] ?? '',
                    $sp->signName,
                    $sDeg, $sMin,
                    $sp->isRetrograde ? ' Rx' : '   '
                );
            }

            $line = '  ' . $this->mbPad($natalCell, 32) . '  ' . $solarCell;
            $this->put($this->row(mb_substr($line, 0, self::IW)));
        }

        // ASC row
        if ($dto !== null && ! empty($natalHouses)) {
            $this->put($this->row('  ' . str_repeat('─', 30) . '  ' . str_repeat('─', 30)));

            // Natal ASC
            $natalAscLon  = (float) ($natalHouses[0] ?? 0);
            $nSignIdx     = (int) floor($natalAscLon / 30);
            $nDeg         = (int) fmod($natalAscLon, 30);
            $nMin         = (int) round((fmod($natalAscLon, 30) - $nDeg) * 60);
            $nSignGlyph   = self::SIGN_GLYPHS[$nSignIdx] ?? '';
            $nSignName    = \App\Models\PlanetaryPosition::SIGN_NAMES[$nSignIdx] ?? '';
            $natalAscCell = sprintf('ASC      %s %-11s %2d°%02d\'   ', $nSignGlyph, $nSignName, $nDeg, $nMin);

            // Solar ASC
            $sAscLon      = $dto->solarAscLon;
            $sSignIdx     = $dto->solarAscSignIndex;
            $sDeg         = (int) fmod($sAscLon, 30);
            $sMin         = (int) round((fmod($sAscLon, 30) - $sDeg) * 60);
            $sSignGlyph   = self::SIGN_GLYPHS[$sSignIdx] ?? '';
            $solarAscCell = sprintf('ASC      %s %-11s %2d°%02d\'   ', $sSignGlyph, $dto->solarAscSignName, $sDeg, $sMin);

            $line = '  ' . $this->mbPad($natalAscCell, 32) . '  ' . $solarAscCell;
            $this->put($this->row(mb_substr($line, 0, self::IW)));
        }

        $this->put($this->row(''));
    }

    // ── Solar ASC analysis ────────────────────────────────────────────────

    /**
     * Show: Solar ASC in which natal house + dispositor in which natal house/sign.
     */
    private function renderSolarAscAnalysis(SolarReturnDTO $dto, array $natalHouses, bool $simplified = false, ?string $gender = null): void
    {
        $this->put($this->divider());
        $this->put($this->row("  \u{25C6}  SOLAR ASC ANALYSIS"));
        $this->put($this->row(''));

        // 1. Solar ASC in natal house
        $solarAscNatalHouse = $this->natalHouseOf($dto->solarAscLon, $natalHouses);
        $ascGlyph  = self::SIGN_GLYPHS[$dto->solarAscSignIndex] ?? '';
        $this->put($this->row("  Solar ASC {$ascGlyph} {$dto->solarAscSignName} \u{2192} natal H{$solarAscNatalHouse}"));

        $ascSection = $simplified ? 'solar_asc_house_short' : 'solar_asc_house';
        $block = TextBlock::pick("solar_asc_natal_house_{$solarAscNatalHouse}", $ascSection, 1, 'en', $gender);
        if ($block) {
            $this->put($this->row(''));
            foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                $this->put($this->row('    ' . $line));
            }
        }

        $this->put($this->row(''));

        // 2. Dispositor of Solar ASC
        $rulerBody    = self::SIGN_RULERS[$dto->solarAscSignIndex] ?? null;
        $rulerName    = $rulerBody !== null ? (\App\Models\PlanetaryPosition::BODY_NAMES[$rulerBody] ?? '') : '';
        $rulerGlyph   = $rulerBody !== null ? (self::BODY_GLYPHS[$rulerBody] ?? '') : '';

        // Find dispositor in solar planets
        $dispSolar = null;
        foreach ($dto->solarPlanets as $sp) {
            if ($sp->body === $rulerBody) {
                $dispSolar = $sp;
                break;
            }
        }

        if ($dispSolar !== null) {
            $dispNatalHouse = $this->natalHouseOf($dispSolar->longitude, $natalHouses);
            $dSignGlyph     = self::SIGN_GLYPHS[$dispSolar->signIndex] ?? '';
            $this->put($this->row("  Dispositor {$rulerGlyph} {$rulerName} \u{2192} {$dSignGlyph} {$dispSolar->signName} \u{00B7} natal H{$dispNatalHouse}"));

            $dispSection = $simplified ? 'solar_dispositor_house_short' : 'solar_dispositor_house';
            $block2 = TextBlock::pick("solar_dispositor_natal_house_{$dispNatalHouse}", $dispSection, 1, 'en', $gender);
            if ($block2) {
                $this->put($this->row(''));
                foreach ($this->wrap(trim(strip_tags($block2->text)), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }

        $this->renderSolarSingletonSection($dto->solarPlanets, $simplified, $gender);

        $this->put($this->row(''));
    }

    // ── Singleton / Missing element (solar planets) ───────────────────────

    private function renderSolarSingletonSection(array $solarPlanets, bool $simplified = false, ?string $gender = null): void
    {
        // Count solar planets per element — only bodies 0–9 (Sun–Pluto)
        $elements = ['fire' => [], 'earth' => [], 'air' => [], 'water' => []];
        foreach ($solarPlanets as $sp) {
            if ($sp->body < 0 || $sp->body > 9) continue;
            $el = self::SIGN_ELEMENTS[$sp->signIndex] ?? null;
            if ($el) $elements[$el][] = $sp;
        }

        $section = $simplified ? 'singleton_short' : 'singleton';

        foreach ($elements as $element => $list) {
            $count = count($list);
            if ($count !== 0 && $count !== 1) continue;

            $label = self::ELEMENT_LABELS[$element];

            if ($count === 1) {
                $pGlyph = self::BODY_GLYPHS[$list[0]->body] ?? '';
                $pName  = $list[0]->name;
                $header = "  \u{2605}  " . ui_trans('solar.singleton', $gender) . ": {$pGlyph} {$pName} ({$label})";
                $key    = 'singleton_' . $element;
            } else {
                $header = "  \u{25CB}  " . ui_trans('solar.missing_element', $gender) . ": {$label}";
                $key    = 'missing_' . $element;
            }

            $this->put($this->row(''));
            $this->put($this->row($header));
            $block = TextBlock::pick($key, $section, 1, 'en', $gender);
            if ($block) {
                $this->put($this->row(''));
                foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }
    }

    /**
     * Shortest arc between two ecliptic longitudes (0–180°).
     */
    private function lonOrb(float $a, float $b): float
    {
        $diff = fmod(abs($a - $b), 360);
        return $diff > 180 ? 360 - $diff : $diff;
    }

    /**
     * Return 1-based natal house number for a given ecliptic longitude.
     */
    private function natalHouseOf(float $longitude, array $houseCusps): int
    {
        $lon = fmod($longitude, 360);
        if ($lon < 0) {
            $lon += 360;
        }

        for ($h = 0; $h < 12; $h++) {
            $start = fmod((float) ($houseCusps[$h] ?? 0), 360);
            $end   = fmod((float) ($houseCusps[($h + 1) % 12] ?? 0), 360);

            if ($start <= $end) {
                if ($lon >= $start && $lon < $end) {
                    return $h + 1;
                }
            } else {
                // wraps around 0°
                if ($lon >= $start || $lon < $end) {
                    return $h + 1;
                }
            }
        }

        return 1;
    }

    // ── Bi-wheel placeholder ─────────────────────────────────────────────

    private function wheelLines(): array
    {
        return [
            '          · ☉ ·         ',
            '       ♆   ·   ·   ♄    ',
            '     ♅   · natal ·   ♃  ',
            '    ♇  ·   · ✦ ·  ·  ♂  ',
            '     ⚸   · solar ·   ♀  ',
            '       ☽   ·   ·   ☿    ',
            '          · ☊ ·         ',
        ];
    }

    // ── Box-drawing helpers ──────────────────────────────────────────────

    private function top(): string
    {
        return "\u{250C}" . str_repeat("\u{2500}", self::W - 2) . "\u{2510}";
    }

    private function bottom(): string
    {
        return "\u{2514}" . str_repeat("\u{2500}", self::W - 2) . "\u{2518}";
    }

    private function divider(): string
    {
        return "\u{251C}" . str_repeat("\u{2500}", self::W - 2) . "\u{2524}";
    }

    private function row(string $content): string
    {
        return "\u{2502} " . $this->mbPad($content, self::IW) . " \u{2502}";
    }

    private function center(string $text): string
    {
        $len = mb_strlen($text);
        if ($len >= self::IW) {
            return $text;
        }
        $pad   = self::IW - $len;
        $left  = (int) floor($pad / 2);
        $right = $pad - $left;
        return str_repeat(' ', $left) . $text . str_repeat(' ', $right);
    }

    private function spread(string $left, string $right, int $width = self::IW): string
    {
        $gap = max(1, $width - mb_strlen($left) - mb_strlen($right));
        return $left . str_repeat(' ', $gap) . $right;
    }

    private function mbPad(string $str, int $width): string
    {
        $len = mb_strlen($str);
        if ($len >= $width) {
            return mb_substr($str, 0, $width);
        }
        return $str . str_repeat(' ', $width - $len);
    }

    private function wrap(string $text, int $width): array
    {
        $text  = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        $lines = [];
        while (mb_strlen($text) > $width) {
            $pos = mb_strrpos(mb_substr($text, 0, $width), ' ');
            if ($pos === false) {
                $pos = $width;
            }
            $lines[] = mb_substr($text, 0, $pos);
            $text    = ltrim(mb_substr($text, $pos));
        }
        if ($text !== '') {
            $lines[] = $text;
        }
        return $lines ?: [''];
    }

    private function put(string $line): void
    {
        $this->line($line);
    }


}
