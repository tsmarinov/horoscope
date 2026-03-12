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
                            {profile : Profile ID}
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

        $profile = Profile::with(['birthCity', 'solarReturnCity'])->find($this->argument('profile'));
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

        // ── Pre-collect texts for AI synthesis ───────────────────────────
        $assembledTexts = [];
        foreach (array_slice($dto->solarNatalAspects, 0, 6) as $asp) {
            $aspWord = __('ui.aspects.' . $asp->aspect, [], null) ?: ucfirst(str_replace('_', ' ', $asp->aspect));
            $key     = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
            $section = $simplified ? 'transit_natal_short' : 'transit_natal';
            $block   = TextBlock::pick($key, $section, 1);
            if ($block) {
                $assembledTexts[] = "[{$asp->transitName} {$aspWord} natal {$asp->natalName}]\n" . trim(strip_tags($block->text));
            }
        }

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
            $synthesis = $this->generateSynthesis(
                $dto,
                $assembledTexts,
                $year,
                $simplified,
                $profile->id,
            );
            if ($synthesis) {
                $this->put($this->divider());
                $this->put($this->row($this->spread("  \u{2726}  YEARLY OVERVIEW", 'AI  ')));
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
            $this->renderSolarAscAnalysis($dto, $natalHouses, $simplified);
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
        $this->put($this->row("  \u{25C6}  SOLAR RETURN FACTORS"));
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
            $aspWord  = __('ui.aspects.' . $asp->aspect, [], null) ?: ucfirst(str_replace('_', ' ', $asp->aspect));

            // Solar house ruler annotation
            $ruledHouses = $solarRuledHouses[$asp->transitBody] ?? [];
            $rulerTag    = ! empty($ruledHouses) ? '  · solar H' . implode('/H', $ruledHouses) . ' rul.' : '';

            $chip = "  {$tGlyph} {$asp->transitName}{$rulerTag}  {$aspGlyph} {$aspWord}  {$nGlyph} natal {$asp->natalName}";
            $this->put($this->row($chip));

            $key     = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
            $section = $simplified ? 'transit_natal_short' : 'transit_natal';
            $block   = TextBlock::pick($key, $section, 1);
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

        $this->put($this->row("  \u{2015}\u{2015} PROGRESSIONS"));
        $this->put($this->row("  \u{1F319} Prog Moon {$pmGlyph} {$pmSign}{$pmHouseStr}"));
        $this->put($this->row("  \u{2609} Prog Sun  {$psGlyph} {$psSign}{$psHouseStr}"));
        $this->put($this->row(''));

        // 5. Solar Arc Directions
        if (! empty($dto->solarArcDirections)) {
            $this->put($this->row("  \u{2015}\u{2015} SOLAR ARC DIRECTIONS"));
            foreach ($dto->solarArcDirections as $dir) {
                $dGlyph   = self::BODY_GLYPHS[$dir->directedBody] ?? '?';
                $tGlyph   = self::BODY_GLYPHS[$dir->natalTargetBody] ?? '?';
                $aspGlyph = self::ASPECT_GLYPHS[$dir->aspect] ?? "\u{00B7}";
                $aspWord  = __('ui.aspects.' . $dir->aspect, [], null) ?: ucfirst(str_replace('_', ' ', $dir->aspect));
                $this->put($this->row("  {$dGlyph} {$dir->directedName}  {$aspGlyph} {$aspWord}  {$tGlyph} natal {$dir->natalTargetName}"));
            }
            $this->put($this->row(''));
        }

        // ── Eclipses & Lunations ─────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row("  \u{1F311}  ECLIPSES & LUNATIONS \u{00B7} {$year}"));
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
        $this->put($this->row("  \u{1F4C5}  KEY TRANSITS BY QUARTER"));
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
            : __('ui.no_rx');
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
    private function renderSolarAscAnalysis(SolarReturnDTO $dto, array $natalHouses, bool $simplified = false): void
    {
        $this->put($this->divider());
        $this->put($this->row("  \u{25C6}  SOLAR ASC ANALYSIS"));
        $this->put($this->row(''));

        // 1. Solar ASC in natal house
        $solarAscNatalHouse = $this->natalHouseOf($dto->solarAscLon, $natalHouses);
        $ascGlyph  = self::SIGN_GLYPHS[$dto->solarAscSignIndex] ?? '';
        $this->put($this->row("  Solar ASC {$ascGlyph} {$dto->solarAscSignName} \u{2192} natal H{$solarAscNatalHouse}"));

        $ascSection = $simplified ? 'solar_asc_house_short' : 'solar_asc_house';
        $block = TextBlock::pick("solar_asc_natal_house_{$solarAscNatalHouse}", $ascSection, 1);
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
            $block2 = TextBlock::pick("solar_dispositor_natal_house_{$dispNatalHouse}", $dispSection, 1);
            if ($block2) {
                $this->put($this->row(''));
                foreach ($this->wrap(trim(strip_tags($block2->text)), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }

        $this->put($this->row(''));
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

    // ── AI synthesis ─────────────────────────────────────────────────────

    private function generateSynthesis(
        SolarReturnDTO $dto,
        array $assembledTexts,
        int $year,
        bool $simplified = false,
        int $profileId = 0,
        string $language = 'en',
    ): ?string {
        $cacheKey = 'solar_' . $profileId . '_' . $year . ($simplified ? '_short' : '');
        $cached   = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', $language)
            ->first();

        if ($cached) {
            $this->line("  <fg=gray>[AI synthesis: cached]</>");
            return $cached->text;
        }

        /** @var \App\Contracts\AiProvider $ai */
        $ai = app(\App\Contracts\AiProvider::class);

        // Build context lines
        $ascGlyph = self::SIGN_GLYPHS[$dto->solarAscSignIndex] ?? '';
        $pmSign   = $dto->progressedMoon['signName'] ?? '';
        $psSign   = $dto->progressedSun['signName'] ?? '';
        $pmHouse  = $dto->progressedMoon['houseIndex'] ?? null;
        $psHouse  = $dto->progressedSun['houseIndex'] ?? null;

        $prompt  = "Solar Return Year: {$year}\n";
        $prompt .= "Solar ASC: {$dto->solarAscSignName}\n";
        $prompt .= "Progressed Moon: {$pmSign}" . ($pmHouse ? " H{$pmHouse}" : '') . "\n";
        $prompt .= "Progressed Sun: {$psSign}" . ($psHouse ? " H{$psHouse}" : '') . "\n";

        if (! empty($dto->solarArcDirections)) {
            $arcLines = [];
            foreach (array_slice($dto->solarArcDirections, 0, 3) as $dir) {
                $aspWord = __('ui.aspects.' . $dir->aspect, [], null) ?: ucfirst(str_replace('_', ' ', $dir->aspect));
                $arcLines[] = "Solar Arc {$dir->directedName} {$aspWord} natal {$dir->natalTargetName}";
            }
            $prompt .= "Solar Arc: " . implode('; ', $arcLines) . "\n";
        }

        if ($assembledTexts) {
            $prompt .= "\nKey solar return factors with pre-generated descriptions:\n\n";
            $prompt .= implode("\n\n", $assembledTexts);
        }

        if ($simplified) {
            $prompt       .= "\n\nWrite exactly 1 paragraph of 5 short sentences as a compact yearly horoscope overview.";
            $paragraphRule = "- 1 paragraph only — exactly 5 sentences, max 12 words each";
            $maxTokens     = 200;
        } else {
            $prompt       .= "\n\nWrite exactly 5 paragraphs as a yearly horoscope portrait synthesizing what follows.";
            $paragraphRule = "- Exactly 5 paragraphs separated by blank lines — no headers, no bullets\n"
                . "- Paragraphs 1-4: 4-5 sentences each\n"
                . "- Paragraph 5: 6-8 sentences — a synthesis pulling the whole year together";
            $maxTokens     = 900;
        }

        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
        $system   = "{$langNote}\n\nYou are writing a personalized yearly horoscope intro for a single person.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . "- Plain everyday words only — no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real life — concrete behaviour only\n"
            . "- Paragraph 1: overall tone of the year (Solar ASC + dispositor)\n"
            . "- Paragraph 2: main aspects — key opportunities or tensions\n"
            . "- Paragraph 3: inner development (Progressed Moon + Sun)\n"
            . "- Paragraph 4: Solar Arc directions — concrete shifts or turning points\n"
            . "- Paragraph 5: synthesis — what kind of year this is as a whole\n"
            . "- Do NOT start with \"This year...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $ai->generate($prompt, $system, maxTokens: $maxTokens);
            $cost     = number_format($response->costUsd, 5);
            $this->line("  <fg=gray>[AI synthesis: {$response->inputTokens} in / {$response->outputTokens} out / \${$cost}]</>");

            if ($profileId > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1],
                    [
                        'text'       => $response->text,
                        'tone'       => 'neutral',
                        'tokens_in'  => $response->inputTokens,
                        'tokens_out' => $response->outputTokens,
                        'cost_usd'   => $response->costUsd,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            return $response->text;
        } catch (\Exception $e) {
            $this->warn('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }
}
