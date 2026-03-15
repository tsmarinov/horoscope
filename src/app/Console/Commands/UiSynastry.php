<?php

namespace App\Console\Commands;

use App\Enums\Gender;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\AspectCalculator;
use App\Services\Horoscope\Shared\AreasOfLifeScorer;
use App\Services\SynastryCalculator;
use App\Services\SynastryScorer;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for 4.11 Synastry (compatibility).
 *
 * Layout:
 *   ┌─ header (title + subtitle) ─────────────────────────────────────┐
 *   ├─ person chips (A & B with Sun sign + ASC) ──────────────────────┤
 *   ├─ bi-wheel placeholder ──────────────────────────────────────────┤
 *   ├─ POSITIONS TABLE (A | B side-by-side) ──────────────────────────┤
 *   ├─ type switcher ─────────────────────────────────────────────────┤
 *   ├─ CATEGORIES (scored, with [text] tag) ─────────────────────────┤
 *   ├─ INTRO (pre-gen free) / SYNTHESIS (AI L1 premium) ─────────────┤
 *   ├─ CROSS-CHART PLACEMENTS (☉☽ A→B, ☉☽ B→A) [tier 3 only] ───────┤
 *   ├─ ASC CROSS-PLACEMENTS (ASC A→B, ASC B→A) [tier 3 only] ────────┤
 *   └─ АСПЕКТИ (all cross-chart aspects with [text] tags) ───────────┘
 *
 *   Tier 3 = both profiles have birth time (ascendant not null).
 */
class UiSynastry extends Command
{
    protected $signature = 'horoscope:ui-synastry
                            {--profile-a= : Profile ID for person A}
                            {--profile-b= : Profile ID for person B}
                            {--type=general : Relationship type (general|romantic|business|friends|family|spiritual|communication|emotion|sexual|creative)}
                            {--simplified  : Show shortened texts (uses _short sections)}
                            {--ai          : Generate synthesis overview with Claude (AI L1)}';

    protected $description = 'Render the synastry compatibility view in pseudo-browser console UI';

    // ── Layout constants ──────────────────────────────────────────────
    private const W  = 72;
    private const IW = 68;

    // ── Planet glyphs ─────────────────────────────────────────────────
    private const BODY_GLYPHS = [
        0  => '☉', 1  => '☽', 2  => '☿', 3  => '♀', 4  => '♂',
        5  => '♃', 6  => '♄', 7  => '⛢', 8  => '♆', 9  => '♇',
        10 => '⚷', 11 => '☊', 12 => '⚸',
    ];

    // ── Aspect glyphs ─────────────────────────────────────────────────
    private const ASPECT_GLYPHS = [
        'conjunction'  => '☌',
        'opposition'   => '☍',
        'trine'        => '△',
        'square'       => '□',
        'sextile'      => '⚹',
        'quincunx'     => '⚻',
        'semi_sextile' => '⚺',
    ];

    // ── Type icons + labels ───────────────────────────────────────────
    private const TYPE_ICONS = [
        'general'       => '✦',
        'romantic'      => '❤',
        'business'      => '🤝',
        'friends'       => '🫂',
        'family'        => '👨‍👩‍👧',
        'spiritual'     => '🔮',
        'communication' => '💬',
        'emotion'       => '🌙',
        'sexual'        => '🔥',
        'creative'      => '🎨',
    ];

    // ── Short labels for horizontal tab row (space-constrained) ──────
    private const TAB_LABELS = [
        'communication' => 'Comm.',
    ];

    // ── Planets shown in positions table (in order) ───────────────────
    private const POSITIONS_ORDER = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 11];

    // ── Entry point ───────────────────────────────────────────────────

    public function handle(
        AspectCalculator   $calculator,
        SynastryCalculator $synastry,
        SynastryScorer     $scorer,
    ): int {
        $idA        = $this->option('profile-a');
        $idB        = $this->option('profile-b');
        $type       = $this->option('type') ?? 'general';
        $simplified = (bool) $this->option('simplified');
        $ai         = (bool) $this->option('ai');

        // Section name resolver: when simplified, append _short
        $sec = fn (string $base): string => $simplified ? $base . '_short' : $base;

        if ($idA === null || $idB === null) {
            $this->error('Both --profile-a and --profile-b are required.');
            return self::FAILURE;
        }

        if (! in_array($type, SynastryScorer::types())) {
            $this->error('Invalid type. Use: ' . implode(', ', SynastryScorer::types()));
            return self::FAILURE;
        }

        $profileA = Profile::find((int) $idA);
        $profileB = Profile::find((int) $idB);

        if ($profileA === null || $profileB === null) {
            $this->error('One or both profiles not found.');
            return self::FAILURE;
        }

        $genderA = TextBlock::resolveGender($profileA->gender?->value ?? null);
        $genderB = TextBlock::resolveGender($profileB->gender?->value ?? null);

        $chartA = $calculator->calculate($profileA);
        $chartB = $calculator->calculate($profileB);

        // A→B aspects (for scoring + display); B→A for reverse direction
        $allAspects = $synastry->calculate($chartA, $chartB);

        // Score all types; general = average of the other 9
        $typeScores = [];
        foreach (SynastryScorer::types() as $t) {
            if ($t === 'general') continue;
            $cats           = $scorer->score($allAspects, $t);
            $avg            = count($cats) > 0
                ? array_sum(array_column($cats, 'stars')) / count($cats)
                : 3;
            $typeScores[$t] = (int) round($avg);
        }
        $typeScores['general'] = (int) round(array_sum($typeScores) / count($typeScores));

        // Split into directional groups, personal planets first
        $sortByPersonal = function (array $a, array $b): int {
            // Personal planets (0-4) before outer (5+)
            $pa = $a['body_a'] <= 4 ? 0 : 1;
            $pb = $b['body_a'] <= 4 ? 0 : 1;
            if ($pa !== $pb) return $pa <=> $pb;
            // Within group: sort by body_a ID (Sun→Moon→Mercury→Venus→Mars, then Jupiter→...)
            return $a['body_a'] <=> $b['body_a'];
            // Within same body_a: keep weight-desc / orb-asc from calculator
        };

        $aspectsAtoB = array_values(array_filter($allAspects, fn($a) => $a['body_a'] !== $a['body_b']));
        usort($aspectsAtoB, $sortByPersonal);

        $aspectsBtoA = array_values(array_filter(
            $synastry->calculate($chartB, $chartA),
            fn($a) => $a['body_a'] !== $a['body_b']
        ));
        usort($aspectsBtoA, $sortByPersonal);

        // Symmetric: ALL same-planet pairs regardless of orb
        $symmetricPairs = $this->computeSymmetricPairs(
            $chartA->planets ?? [],
            $chartB->planets ?? []
        );

        $nameA      = $profileA->name ?? ('Profile ' . $profileA->id);
        $nameB      = $profileB->name ?? ('Profile ' . $profileB->id);
        $firstNameA = $profileA->first_name ?? $nameA;
        $firstNameB = $profileB->first_name ?? $nameB;

        // Tier 3: both profiles have birth time (ascendant available)
        $hasTier3 = $chartA->ascendant !== null && $chartB->ascendant !== null;

        $planetsA = collect($chartA->planets ?? [])->keyBy('body');
        $planetsB = collect($chartB->planets ?? [])->keyBy('body');
        $housesA  = $chartA->houses ?? [];
        $housesB  = $chartB->houses ?? [];

        $this->newLine();

        // ── 1. Header ─────────────────────────────────────────────────
        $typeLabel = self::TYPE_ICONS[$type] . ' ' . ui_trans('synastry.types.' . $type);

        $this->put($this->top());
        $this->put($this->row($this->spread(
            '  ♡ ' . ui_trans('synastry.title') . ' — ' . $nameA . ' & ' . $nameB,
            '[' . $typeLabel . ']  '
        )));
        $this->put($this->row('  ' . ui_trans('synastry.subtitle')));
        $this->put($this->divider());

        // ── 2. Person chips ───────────────────────────────────────────
        $this->put($this->row('  ' . $this->personLine('A', $profileA, $chartA)));
        $this->put($this->row('  ' . $this->personLine('B', $profileB, $chartB)));
        $this->put($this->divider());

        // ── 3. Bi-wheel placeholder ───────────────────────────────────
        $this->put($this->row(''));
        $this->put($this->row($this->centerStr(ui_trans('synastry.biwheel_label'))));
        $this->put($this->row(''));
        $this->put($this->divider());

        // ── 4. Positions table ────────────────────────────────────────
        $this->put($this->row('  ' . ui_trans('synastry.positions_title')));
        $this->put($this->row(''));

        $hdrLeft  = '  ' . $this->mbPad('Body', 10) . $this->mbPad('A  ' . $nameA, 24);
        $hdrRight = 'B  ' . $nameB . '  ';
        $this->put($this->row($this->spread($hdrLeft, $hdrRight)));
        $this->put($this->row('  ' . str_repeat('·', self::IW - 2)));

        foreach (self::POSITIONS_ORDER as $bodyId) {
            $pA    = $planetsA->get($bodyId);
            $pB    = $planetsB->get($bodyId);
            $glyph = self::BODY_GLYPHS[$bodyId] ?? '?';
            $bName = PlanetaryPosition::BODY_NAMES[$bodyId] ?? '?';

            $colA = $pA ? $this->positionCell($pA, $housesA) : '—';
            $colB = $pB ? $this->positionCell($pB, $housesB) : '—';

            // label: glyph(1) + space(1) + name(8 padded) = 10 visible chars
            $label = $glyph . ' ' . $this->mbPad($bName, 8);
            $left  = '  ' . $label . $this->mbPad($colA, 24);
            $this->put($this->row($this->spread($left, $colB . '  ')));
        }

        // ASC row (only when available)
        if ($hasTier3) {
            $ascACellLabel = $this->formatAscSign($chartA->ascendant);
            $ascBCellLabel = $this->formatAscSign($chartB->ascendant);
            $label = $this->mbPad('ASC', 10);
            $left  = '  ' . $label . $this->mbPad($ascACellLabel, 24);
            $this->put($this->row($this->spread($left, $ascBCellLabel . '  ')));
        } else {
            $label = $this->mbPad('ASC', 10);
            $this->put($this->row('  ' . $label . '🔒 add birth time & place'));
        }

        $this->put($this->row(''));
        $this->put($this->divider());

        // ── 5+6. Relationships = type navigation + scores ────────────
        $this->put($this->row('  ' . ui_trans('synastry.categories_title')));
        $this->put($this->row(''));

        // Horizontal tabs — 2 rows of 5 (10 types total)
        $allTypes    = SynastryScorer::types();
        $buildTabRow = function (array $row) use ($type): string {
            $tabs = '  ';
            foreach ($row as $t) {
                $icon  = self::TYPE_ICONS[$t];
                $label = self::TAB_LABELS[$t] ?? ui_trans('synastry.types.' . $t);
                $tabs .= $t === $type
                    ? '[' . $icon . ' ' . $label . ']  '
                    : $icon . ' ' . $label . '  ';
            }
            return rtrim($tabs);
        };
        $this->put($this->row($buildTabRow(array_slice($allTypes, 0, 5))));
        $this->put($this->row($buildTabRow(array_slice($allTypes, 5))));
        $this->put($this->row(''));

        // Vertical scored list — all 10 types
        foreach ($allTypes as $t) {
            $stars = str_repeat('★', $typeScores[$t]) . str_repeat('☆', 5 - $typeScores[$t]);
            $icon  = self::TYPE_ICONS[$t];
            $label = ui_trans('synastry.types.' . $t);
            $this->put($this->row($this->spread(
                '  ' . $icon . '  ' . $label,
                $stars . '  '
            )));
        }

        // Description for selected type — shared text, null gender
        $typeBlock = TextBlock::pick($type, $sec('synastry_type'), 1, 'en', null);
        if ($typeBlock) {
            $this->put($this->row(''));
            foreach ($this->wrap(strip_tags($typeBlock->text), self::IW - 4) as $line) {
                $this->put($this->row('  ' . $line));
            }
        }
        $this->put($this->row(''));
        $this->put($this->divider());

        // ── 7. Intro / Synthesis (mutually exclusive) ────────────────
        $sunA     = $planetsA->get(PlanetaryPosition::SUN);
        $sunB     = $planetsB->get(PlanetaryPosition::SUN);
        $introKey = $this->introKey($sunA['sign'] ?? 0, $sunB['sign'] ?? 0);

        if (! $ai) {
            $this->put($this->row('  ' . ui_trans('synastry.intro_title')));
            $introBlock = TextBlock::pick($introKey, $sec('synastry_intro'), 1, 'en', null);
            if ($introBlock) {
                foreach ($this->wrap(strip_tags($introBlock->text), self::IW - 4) as $line) {
                    $this->put($this->row('  ' . $line));
                }
            } else {
                $this->put($this->row('  ' . ui_trans('synastry.intro_text_tag')));
            }
            $this->put($this->row(''));
            $this->put($this->row('  ' . str_repeat('─', 40) . ui_trans('synastry.ai_divider')));
            $this->put($this->row('  ' . ui_trans('synastry.synthesis_title')));
        } else {
            $this->put($this->row('  ' . ui_trans('synastry.synthesis_title')));
        }

        if ($ai) {
            /** @var \App\Services\Ai\HoroscopeSynthesisService $synthesisService */
            $synthesisService = app(\App\Services\Ai\HoroscopeSynthesisService::class);
            $aiResponse = $synthesisService->synastry(
                nameA:      $firstNameA,
                planetsA:   $chartA->planets ?? [],
                nameB:      $firstNameB,
                planetsB:   $chartB->planets ?? [],
                type:       $type,
                typeScores: $typeScores,
                aspects:    $allAspects,
                simplified: $simplified,
                profileIdA: $profileA->id,
                profileIdB: $profileB->id,
                language:   'en',
            );
            if ($aiResponse) {
                $first = true;
                foreach (preg_split('/\n{2,}/', trim($aiResponse->text)) as $para) {
                    if (! $first) {
                        $this->put($this->row(''));
                    }
                    foreach ($this->wrap(trim($para), self::IW - 4) as $line) {
                        $this->put($this->row('  ' . $line));
                    }
                    $first = false;
                }
            } else {
                $this->put($this->row('  ' . ui_trans('synastry.synthesis_text_tag')));
            }
        } else {
            $this->put($this->row('  ' . ui_trans('synastry.synthesis_text_tag')));
        }

        $this->put($this->row(''));
        $this->put($this->divider());

        // ── 7.5. Partner Archetypes (romantic, M+F only) ──────────────
        $this->renderPartnerArchetypes(
            $type, $profileA, $profileB,
            $planetsA, $planetsB,
            $housesA, $housesB,
            $hasTier3, $firstNameA, $firstNameB,
            $sec, $genderA, $genderB,
        );

        // ── 8a. Sun/Moon cross-chart placements (tier 3 only) ─────────
        if ($hasTier3) {
            $this->put($this->row('  ' . ui_trans('synastry.cross_placements_title')));
            $this->put($this->row(''));

            $bodyKeys = [PlanetaryPosition::SUN => 'sun', PlanetaryPosition::MOON => 'moon'];

            foreach ([PlanetaryPosition::SUN, PlanetaryPosition::MOON] as $bodyId) {
                $pA = $planetsA->get($bodyId);
                if ($pA) {
                    $g     = self::BODY_GLYPHS[$bodyId];
                    $bName = PlanetaryPosition::BODY_NAMES[$bodyId];
                    $sign  = PlanetaryPosition::SIGN_NAMES[$pA['sign']] ?? '?';
                    $hInB  = $housesB ? $this->houseOf($pA['longitude'], $housesB) : '?';
                    $this->put($this->row('  ' . ui_trans('synastry.planet_in_house', null, null, [
                        'glyph' => $g, 'name' => $bName, 'label' => 'A',
                        'sign'  => $sign, 'house' => $hInB, 'other' => $nameB,
                    ])));
                    $phBlock = TextBlock::pick($bodyKeys[$bodyId] . '_house_' . $hInB, $sec('synastry_planet_house'), 1, 'en', $genderA);
                    if ($phBlock) {
                        $resolved = $this->resolveText(strip_tags($phBlock->text), $firstNameA, $firstNameB);
                        foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                            $this->put($this->row('  ' . $line));
                        }
                    }
                    $this->put($this->row(''));
                }
            }

            foreach ([PlanetaryPosition::SUN, PlanetaryPosition::MOON] as $bodyId) {
                $pB = $planetsB->get($bodyId);
                if ($pB) {
                    $g     = self::BODY_GLYPHS[$bodyId];
                    $bName = PlanetaryPosition::BODY_NAMES[$bodyId];
                    $sign  = PlanetaryPosition::SIGN_NAMES[$pB['sign']] ?? '?';
                    $hInA  = $housesA ? $this->houseOf($pB['longitude'], $housesA) : '?';
                    $this->put($this->row('  ' . ui_trans('synastry.planet_in_house', null, null, [
                        'glyph' => $g, 'name' => $bName, 'label' => 'B',
                        'sign'  => $sign, 'house' => $hInA, 'other' => $nameA,
                    ])));
                    $phBlock = TextBlock::pick($bodyKeys[$bodyId] . '_house_' . $hInA, $sec('synastry_planet_house'), 1, 'en', $genderB);
                    if ($phBlock) {
                        $resolved = $this->resolveText(strip_tags($phBlock->text), $firstNameB, $firstNameA);
                        foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                            $this->put($this->row('  ' . $line));
                        }
                    }
                    $this->put($this->row(''));
                }
            }

            $this->put($this->divider());

            // ── 8b. ASC cross-placements ──────────────────────────────
            $this->put($this->row('  ' . ui_trans('synastry.asc_placements_title')));
            $this->put($this->row(''));

            $ascALong = $chartA->ascendant;
            $ascBLong = $chartB->ascendant;
            $ascASign = PlanetaryPosition::SIGN_NAMES[(int) floor($ascALong / 30) % 12] ?? '?';
            $ascBSign = PlanetaryPosition::SIGN_NAMES[(int) floor($ascBLong / 30) % 12] ?? '?';
            $ascAInB  = $housesB ? $this->houseOf($ascALong, $housesB) : '?';
            $ascBInA  = $housesA ? $this->houseOf($ascBLong, $housesA) : '?';

            $this->put($this->row('  ' . ui_trans('synastry.asc_falls_in', null, null, [
                'label' => 'A', 'name' => $nameA, 'sign' => $ascASign,
                'house' => $ascAInB, 'other' => $nameB,
            ])));
            $ascABlock = TextBlock::pick('asc_house_' . $ascAInB, $sec('synastry_asc_house'), 1, 'en', $genderA);
            if ($ascABlock) {
                $resolved = $this->resolveText(strip_tags($ascABlock->text), $firstNameA, $firstNameB);
                foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                    $this->put($this->row('  ' . $line));
                }
            }
            $this->put($this->row(''));

            $this->put($this->row('  ' . ui_trans('synastry.asc_falls_in', null, null, [
                'label' => 'B', 'name' => $nameB, 'sign' => $ascBSign,
                'house' => $ascBInA, 'other' => $nameA,
            ])));
            $ascBBlock = TextBlock::pick('asc_house_' . $ascBInA, $sec('synastry_asc_house'), 1, 'en', $genderB);
            if ($ascBBlock) {
                $resolved = $this->resolveText(strip_tags($ascBBlock->text), $firstNameB, $firstNameA);
                foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                    $this->put($this->row('  ' . $line));
                }
            }
            $this->put($this->row(''));
            $this->put($this->divider());
        } else {
            $this->put($this->row('  🔒 Cross-chart placements & ASC — add birth time & place to both profiles'));
            $this->put($this->row(''));
            $this->put($this->divider());
        }

        // ── 9. Aspects — three directional sections ───────────────────

        // 9a. A → B
        $this->put($this->row($this->spread(
            '  ' . ui_trans('synastry.a_to_b', null, null, ['name_a' => $nameA, 'name_b' => $nameB]),
            '(' . count($aspectsAtoB) . ')  '
        )));
        $this->put($this->row(''));
        foreach ($aspectsAtoB as $asp) {
            $this->put($this->row($this->aspectLine($asp)));
            $block = TextBlock::pick($this->aspectKey($asp), $sec('synastry_aspect'), 1, 'en', null);
            if ($block) {
                // :owner = person whose planet has the lower body ID (min body in key)
                $ownerName = $asp['body_a'] <= $asp['body_b'] ? $firstNameA : $firstNameB;
                $otherName = $asp['body_a'] <= $asp['body_b'] ? $firstNameB : $firstNameA;
                $resolved  = $this->resolveText(strip_tags($block->text), $ownerName, $otherName);
                foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                    $this->put($this->row('  ' . $line));
                }
                $this->put($this->row(''));
            }
        }
        if (empty($aspectsAtoB)) {
            $this->put($this->row('  ' . ui_trans('synastry.no_aspects')));
        }
        $this->put($this->row(''));
        $this->put($this->divider());

        // 9b. B → A
        $this->put($this->row($this->spread(
            '  ' . ui_trans('synastry.b_to_a', null, null, ['name_a' => $nameA, 'name_b' => $nameB]),
            '(' . count($aspectsBtoA) . ')  '
        )));
        $this->put($this->row(''));
        foreach ($aspectsBtoA as $asp) {
            $this->put($this->row($this->aspectLine($asp, 'B', 'A')));
            $block = TextBlock::pick($this->aspectKey($asp), $sec('synastry_aspect'), 1, 'en', null);
            if ($block) {
                // body_a is B's planet here; :owner = person with min body ID
                $ownerName = $asp['body_a'] <= $asp['body_b'] ? $firstNameB : $firstNameA;
                $otherName = $asp['body_a'] <= $asp['body_b'] ? $firstNameA : $firstNameB;
                $resolved  = $this->resolveText(strip_tags($block->text), $ownerName, $otherName);
                foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                    $this->put($this->row('  ' . $line));
                }
                $this->put($this->row(''));
            }
        }
        if (empty($aspectsBtoA)) {
            $this->put($this->row('  ' . ui_trans('synastry.no_aspects')));
        }
        $this->put($this->row(''));
        $this->put($this->divider());

        // 9c. Symmetric — only pairs that actually form an aspect
        $symmetricWithAspect = array_values(array_filter($symmetricPairs, fn($p) => $p['aspect'] !== null));
        $this->put($this->row($this->spread(
            '  ' . ui_trans('synastry.mutual'),
            '(' . count($symmetricWithAspect) . ')  '
        )));
        $this->put($this->row(''));
        foreach ($symmetricWithAspect as $pair) {
            $this->put($this->row($this->symmetricLine($pair)));
            $name  = strtolower(PlanetaryPosition::BODY_NAMES[$pair['body']] ?? '');
            $block = TextBlock::pick($name . '_' . $pair['aspect'] . '_' . $name, $sec('synastry_aspect'), 1, 'en', null);
            if ($block) {
                $resolved = $this->resolveText(strip_tags($block->text), $firstNameA, $firstNameB);
                foreach ($this->wrap($resolved, self::IW - 4) as $line) {
                    $this->put($this->row('  ' . $line));
                }
                $this->put($this->row(''));
            }
        }
        if (empty($symmetricWithAspect)) {
            $this->put($this->row('  ' . ui_trans('synastry.no_mutual_aspects')));
        }

        $this->put($this->row(''));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Aspect line helper ────────────────────────────────────────────

    private function aspectLine(array $asp, string $labelA = 'A', string $labelB = 'B'): string
    {
        $glyphA    = self::BODY_GLYPHS[$asp['body_a']] ?? '?';
        $glyphB    = self::BODY_GLYPHS[$asp['body_b']] ?? '?';
        $aspGlyph  = self::ASPECT_GLYPHS[$asp['aspect']] ?? $asp['aspect'];
        $nameBodyA = PlanetaryPosition::BODY_NAMES[$asp['body_a']] ?? '?';
        $nameBodyB = PlanetaryPosition::BODY_NAMES[$asp['body_b']] ?? '?';
        $signA     = substr(PlanetaryPosition::SIGN_NAMES[$asp['sign_a']] ?? '?', 0, 3);
        $signB     = substr(PlanetaryPosition::SIGN_NAMES[$asp['sign_b']] ?? '?', 0, 3);
        $aspName   = ucfirst(str_replace('_', ' ', $asp['aspect']));
        $orb       = number_format($asp['orb'], 1) . '°';

        return sprintf(
            '  %s%s %s %s%s  %s %s %s  ·  %s/%s  %s',
            $glyphA, $labelA, $aspGlyph, $glyphB, $labelB,
            $nameBodyA, $aspName, $nameBodyB,
            $signA, $signB, $orb
        );
    }

    // ── Symmetric pair computation ────────────────────────────────────

    /**
     * All same-planet pairs (body_a === body_b) regardless of orb.
     * Includes aspect info if within orb, otherwise just separation.
     */
    private function computeSymmetricPairs(array $planetsA, array $planetsB): array
    {
        $aspectConfig = config('astrology.aspects', []);
        $sunOrb       = config('astrology.sun_orb', 5.0);
        $moonOrb      = config('astrology.moon_orb', 4.0);

        $indexA = collect($planetsA)->keyBy('body');
        $indexB = collect($planetsB)->keyBy('body');
        $pairs  = [];

        foreach (self::POSITIONS_ORDER as $bodyId) {
            $pA = $indexA->get($bodyId);
            $pB = $indexB->get($bodyId);
            if (! $pA || ! $pB) {
                continue;
            }

            $diff = abs($pA['longitude'] - $pB['longitude']);
            if ($diff > 180) {
                $diff = 360 - $diff;
            }

            $orbLimit   = match ($bodyId) {
                PlanetaryPosition::SUN  => $sunOrb,
                PlanetaryPosition::MOON => $moonOrb,
                default                 => null,
            };

            $bestAspect = null;
            $bestOrb    = PHP_FLOAT_MAX;
            foreach ($aspectConfig as $name => $def) {
                $effectiveOrb = $orbLimit ?? $def['orb'];
                $deviation    = abs($diff - $def['angle']);
                if ($deviation <= $effectiveOrb && $deviation < $bestOrb) {
                    $bestOrb    = $deviation;
                    $bestAspect = $name;
                }
            }

            $pairs[] = [
                'body'       => $bodyId,
                'sign_a'     => $pA['sign'],
                'sign_b'     => $pB['sign'],
                'separation' => round($diff, 1),
                'aspect'     => $bestAspect,
                'orb'        => $bestAspect ? round($bestOrb, 1) : null,
            ];
        }

        return $pairs;
    }

    private function symmetricLine(array $pair): string
    {
        $glyph = self::BODY_GLYPHS[$pair['body']] ?? '?';
        $bName = PlanetaryPosition::BODY_NAMES[$pair['body']] ?? '?';
        $signA = substr(PlanetaryPosition::SIGN_NAMES[$pair['sign_a']] ?? '?', 0, 3);
        $signB = substr(PlanetaryPosition::SIGN_NAMES[$pair['sign_b']] ?? '?', 0, 3);

        if ($pair['aspect']) {
            $aspGlyph = self::ASPECT_GLYPHS[$pair['aspect']] ?? $pair['aspect'];
            $aspName  = ucfirst(str_replace('_', ' ', $pair['aspect']));
            $orb      = number_format($pair['orb'], 1) . '°';
            return sprintf(
                '  %sA %s %sB  %s %s  ·  %s/%s  %s',
                $glyph, $aspGlyph, $glyph,
                $bName, $aspName,
                $signA, $signB, $orb
            );
        }

        $sep = number_format($pair['separation'], 1) . '°';
        return sprintf(
            '  %sA · %sB  %s  ·  %s/%s  %s separation',
            $glyph, $glyph,
            $bName,
            $signA, $signB, $sep
        );
    }

    // ── Position cell helper ──────────────────────────────────────────

    private function positionCell(array $planet, array $houses): string
    {
        $sign = PlanetaryPosition::SIGN_NAMES[$planet['sign']] ?? '?';
        if (! empty($houses)) {
            $h = $this->houseOf($planet['longitude'], $houses);
            return $sign . '  H' . $h;
        }
        return $sign;
    }

    private function formatAscSign(float $longitude): string
    {
        return PlanetaryPosition::SIGN_NAMES[(int) floor($longitude / 30) % 12] ?? '?';
    }

    // ── House lookup ──────────────────────────────────────────────────

    /**
     * Return the 1-based house number for the given longitude
     * given an array of 12 house cusp longitudes (0°–360°).
     */
    private function houseOf(float $longitude, array $houses): int
    {
        if (empty($houses)) {
            return 1;
        }
        $longitude = fmod($longitude + 360, 360);
        $count     = count($houses);

        for ($i = 0; $i < $count; $i++) {
            $cusp     = fmod($houses[$i] + 360, 360);
            $nextCusp = fmod($houses[($i + 1) % $count] + 360, 360);

            if ($cusp <= $nextCusp) {
                if ($longitude >= $cusp && $longitude < $nextCusp) {
                    return $i + 1;
                }
            } else {
                // Wraps past 0°
                if ($longitude >= $cusp || $longitude < $nextCusp) {
                    return $i + 1;
                }
            }
        }

        return 1;
    }

    // ── Person chip line ──────────────────────────────────────────────

    private function personLine(string $label, Profile $profile, \App\Models\NatalChart $chart): string
    {
        $name      = $profile->name ?? ('Profile ' . $profile->id);
        $sunPlanet = collect($chart->planets ?? [])->firstWhere('body', PlanetaryPosition::SUN);
        $sunSign   = isset($sunPlanet['sign']) ? (PlanetaryPosition::SIGN_NAMES[$sunPlanet['sign']] ?? '') : '';
        $ascSign   = '';

        if ($chart->ascendant !== null) {
            $idx     = (int) floor($chart->ascendant / 30) % 12;
            $ascSign = PlanetaryPosition::SIGN_NAMES[$idx] ?? '';
        }

        $parts = [$label . '  ' . $name];
        if ($sunSign) $parts[] = '☉ ' . $sunSign;
        if ($ascSign) $parts[] = 'ASC ' . $ascSign;

        return implode('  ·  ', $parts);
    }

    // ── Box-drawing helpers ───────────────────────────────────────────

    private function top(): string
    {
        return '┌' . str_repeat('─', self::W - 2) . '┐';
    }

    private function bottom(): string
    {
        return '└' . str_repeat('─', self::W - 2) . '┘';
    }

    private function divider(): string
    {
        return '├' . str_repeat('─', self::W - 2) . '┤';
    }

    private function row(string $content): string
    {
        return '│ ' . $this->mbPad($content, self::IW) . ' │';
    }

    private function spread(string $left, string $right, int $width = self::IW): string
    {
        $gap = max(1, $width - mb_strlen($left) - mb_strlen($right));
        return $left . str_repeat(' ', $gap) . $right;
    }

    private function centerStr(string $str, int $width = self::IW): string
    {
        $len = mb_strlen($str);
        if ($len >= $width) {
            return $str;
        }
        $pad = (int) floor(($width - $len) / 2);
        return str_repeat(' ', $pad) . $str;
    }

    private function mbPad(string $str, int $width): string
    {
        $len = mb_strlen($str);
        if ($len >= $width) {
            return mb_substr($str, 0, $width);
        }
        return $str . str_repeat(' ', $width - $len);
    }

    private function put(string $line): void
    {
        $this->line($line);
    }

    // ── Text helpers ──────────────────────────────────────────────────

    private function resolveText(string $text, string $ownerName, string $otherName): string
    {
        return str_replace(
            [":owner's", ":other's", ':owner', ':other', "Owner's", "Other's", 'Owner', 'Other'],
            [$ownerName . "'s", $otherName . "'s", $ownerName, $otherName, $ownerName . "'s", $otherName . "'s", $ownerName, $otherName],
            $text
        );
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

    private function aspectKey(array $asp): string
    {
        $minBody = min($asp['body_a'], $asp['body_b']);
        $maxBody = max($asp['body_a'], $asp['body_b']);
        $nameMin = strtolower(PlanetaryPosition::BODY_NAMES[$minBody] ?? '');
        $nameMax = strtolower(PlanetaryPosition::BODY_NAMES[$maxBody] ?? '');
        return $nameMin . '_' . $asp['aspect'] . '_' . $nameMax;
    }

    private function signKey(int $idx): string
    {
        return strtolower(PlanetaryPosition::SIGN_NAMES[$idx] ?? 'aries');
    }

    private function introKey(int $signA, int $signB): string
    {
        [$lo, $hi] = $signA <= $signB ? [$signA, $signB] : [$signB, $signA];
        return $this->signKey($lo) . '_' . $this->signKey($hi);
    }

    // ── Partner Archetypes (romantic · all gender combos except Other) ───

    private function renderPartnerArchetypes(
        string $type,
        Profile $profileA, Profile $profileB,
        \Illuminate\Support\Collection $planetsA, \Illuminate\Support\Collection $planetsB,
        array $housesA, array $housesB,
        bool $hasTier3,
        string $firstNameA, string $firstNameB,
        \Closure $sec,
        ?string $genderA = null,
        ?string $genderB = null,
    ): void {
        if ($type !== 'romantic') {
            return;
        }

        $enumGenderA = $profileA->gender ?? null;
        $enumGenderB = $profileB->gender ?? null;

        if ($enumGenderA === null || $enumGenderB === null) return;
        if ($enumGenderA === Gender::Other || $enumGenderB === Gender::Other) return;

        $sameGender  = ($enumGenderA === $enumGenderB);
        $showPlanet  = ! $sameGender;

        $maleConfig = [
            'section' => $sec('synastry_partner_male'),
            'planets' => [
                [PlanetaryPosition::VENUS, '♀', 'Venus', 'venus', ui_trans('synastry.male_venus_label')],
                [PlanetaryPosition::MOON,  '☽', 'Moon',  'moon',  ui_trans('synastry.male_moon_label')],
            ],
        ];
        $femaleConfig = [
            'section' => $sec('synastry_partner_female'),
            'planets' => [
                [PlanetaryPosition::MARS, '♂', 'Mars', 'mars', ui_trans('synastry.female_mars_label')],
                [PlanetaryPosition::SUN,  '☉', 'Sun',  'sun',  ui_trans('synastry.female_sun_label')],
            ],
        ];
        $maleSameConfig = [
            'section' => $sec('synastry_partner_male_same'),
            'planets' => $maleConfig['planets'],
        ];
        $femaleSameConfig = [
            'section' => $sec('synastry_partner_female_same'),
            'planets' => $femaleConfig['planets'],
        ];

        if ($sameGender) {
            $cfg   = $enumGenderA === Gender::Male ? $maleSameConfig : $femaleSameConfig;
            $sides = [
                [$planetsA, $housesA, $firstNameA, $cfg, $genderA],
                [$planetsB, $housesB, $firstNameB, $cfg, $genderB],
            ];
        } elseif ($enumGenderA === Gender::Male) {
            $sides = [
                [$planetsA, $housesA, $firstNameA, $maleConfig, $genderA],
                [$planetsB, $housesB, $firstNameB, $femaleConfig, $genderB],
            ];
        } else {
            $sides = [
                [$planetsB, $housesB, $firstNameB, $maleConfig, $genderB],
                [$planetsA, $housesA, $firstNameA, $femaleConfig, $genderA],
            ];
        }

        $this->put($this->row('  ' . ui_trans('synastry.partner_archetypes_title')));
        $this->put($this->row(''));

        foreach ($sides as $i => [$planets, $houses, $name, $cfg, $sideGender]) {
            if ($i > 0) {
                $this->put($this->divider());
            }

            $this->put($this->row('  ' . $name));
            $this->put($this->row(''));

            foreach ($cfg['planets'] as [$bodyId, $glyph, $bName, $key, $label]) {
                $planet = $planets->get($bodyId);
                if (! $planet) continue;

                $signName = PlanetaryPosition::SIGN_NAMES[$planet['sign']] ?? '?';
                $hStr     = ! empty($houses)
                    ? '  H' . $this->houseOf($planet['longitude'], $houses)
                    : '';
                $prefix = $showPlanet ? $glyph . ' ' . $bName . ' in ' : '';
                $this->put($this->row('  ' . $prefix . $signName . $hStr));

                $block = TextBlock::pick($key . '_' . strtolower($signName), $sec($cfg['section']), 1, 'en', $sideGender);
                if ($block) {
                    foreach ($this->wrap(strip_tags($block->text), self::IW - 4) as $line) {
                        $this->put($this->row('  ' . $line));
                    }
                } else {
                    $this->put($this->row('  [text: ' . $sec($cfg['section']) . ' / ' . $key . '_' . strtolower($signName) . ']'));
                }
                $this->put($this->row(''));
            }

            if ($hasTier3 && ! empty($houses)) {
                $this->renderSeventhLord($houses, $planets, $sec, $sideGender);
            } else {
                $this->put($this->row('  🔒 H7 ruler — add birth time & place for partner type analysis'));
                $this->put($this->row(''));
            }
        }

        $this->put($this->row(''));
        $this->put($this->divider());
    }

    private function renderSeventhLord(
        array $houses,
        \Illuminate\Support\Collection $planets,
        \Closure $sec,
        ?string $gender = null,
    ): void {
        // 7th house cusp = index 6 (0-based)
        $cuspLong    = $houses[6] ?? null;
        if ($cuspLong === null) return;

        $cuspSignIdx  = (int) floor(fmod($cuspLong + 360, 360) / 30) % 12;
        $cuspSignName = PlanetaryPosition::SIGN_NAMES[$cuspSignIdx] ?? '?';
        $lordBodyId   = AreasOfLifeScorer::SIGN_RULERS[$cuspSignIdx] ?? null;
        if ($lordBodyId === null) return;

        $lordPlanet = $planets->get($lordBodyId);
        if (! $lordPlanet) return;

        $lordGlyph    = self::BODY_GLYPHS[$lordBodyId] ?? '?';
        $lordName     = PlanetaryPosition::BODY_NAMES[$lordBodyId] ?? '?';
        $lordSignName = PlanetaryPosition::SIGN_NAMES[$lordPlanet['sign']] ?? '?';
        $lordHouse    = $this->houseOf($lordPlanet['longitude'], $houses);

        $this->put($this->row(sprintf(
            '  %s %s rules H7 (%s cusp)  ·  in %s  H%d  ·  %s',
            $lordGlyph, $lordName, $cuspSignName,
            $lordSignName, $lordHouse,
            ui_trans('synastry.seventh_lord_label')
        )));

        $signBlock = TextBlock::pick(strtolower($lordSignName), $sec('synastry_seventh_lord_sign'), 1, 'en', $gender);
        if ($signBlock) {
            foreach ($this->wrap(strip_tags($signBlock->text), self::IW - 4) as $line) {
                $this->put($this->row('  ' . $line));
            }
        } else {
            $this->put($this->row('  [text: synastry_seventh_lord_sign / ' . strtolower($lordSignName) . ']'));
        }

        $houseBlock = TextBlock::pick('h' . $lordHouse, $sec('synastry_seventh_lord_house'), 1, 'en', $gender);
        if ($houseBlock) {
            foreach ($this->wrap(strip_tags($houseBlock->text), self::IW - 4) as $line) {
                $this->put($this->row('  ' . $line));
            }
        } else {
            $this->put($this->row('  [text: synastry_seventh_lord_house / h' . $lordHouse . ']'));
        }

        $this->put($this->row(''));
    }
}
