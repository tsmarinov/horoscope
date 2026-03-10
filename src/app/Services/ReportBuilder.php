<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Contracts\HoroscopeSubject;
use App\DataTransfer\NatalReport as NatalReportDTO;
use App\DataTransfer\NatalReportSection as NatalReportSectionDTO;
use App\Enums\ReportMode;
use App\Models\NatalChart;
use App\Models\NatalReport;
use App\Models\PlanetaryPosition;
use App\Models\TextBlock;

class ReportBuilder
{
    public function __construct(
        private readonly AspectCalculator $aspectCalculator,
        private readonly VariantPicker    $variantPicker,
        private readonly ?AiProvider      $aiProvider,
    ) {}

    /**
     * Build a natal report DTO for the given subject.
     * Guests are never cached — they always get fresh organic mode.
     */
    public function buildNatalReport(
        HoroscopeSubject $subject,
        ReportMode       $mode = ReportMode::Organic,
        string           $language = 'en',
    ): NatalReportDTO {
        // Guests are always organic — no caching
        if ($subject->isGuest()) {
            $mode = ReportMode::Organic;
        }

        $chart = $this->aspectCalculator->calculate($subject);

        // Return cached report for persisted profiles
        if ($subject->exists) {
            $cached = $this->findCached($subject, $chart, $mode, $language);
            if ($cached) {
                return $this->hydrateFromModel($cached, $chart);
            }
        }

        $dto = match ($mode) {
            ReportMode::Organic, ReportMode::Simplified => $this->buildOrganic($subject, $chart, $mode, $language),
            ReportMode::AiL1                            => $this->buildAiL1($subject, $chart, $language),
            ReportMode::AiL1Haiku                       => $this->buildAiL1Haiku($subject, $chart, $language),
        };

        // Persist for saved profiles
        if ($subject->exists) {
            $this->persist($subject, $dto);
        }

        return $dto;
    }

    // -----------------------------------------------------------------------
    // Organic / Simplified
    // -----------------------------------------------------------------------

    private function buildOrganic(
        HoroscopeSubject $subject,
        NatalChart       $chart,
        ReportMode       $mode,
        string           $language,
    ): NatalReportDTO {
        $sections = [];
        $position = 0;

        $isSimplified   = $mode === ReportMode::Simplified;
        $aspectSection  = $isSimplified ? 'natal_short' : 'natal';

        foreach ($chart->aspects as $aspect) {
            $key   = $this->aspectKey($aspect);
            $block = $this->pickTextBlock($key, $aspectSection, $subject, $chart, $language, $isSimplified);

            if ($block === null) {
                continue;
            }

            $sections[] = new NatalReportSectionDTO(
                key:         $key,
                section:     $aspectSection,
                title:       $this->humanTitle($key),
                text:        $block->text,
                tone:        $block->tone,
                textBlockId: $block->id,
            );

            $position++;
        }

        // Planet Positions (ASC + Sun/Moon/Mercury/Venus/Mars in sign+house)
        foreach ($this->positionKeys($chart) as [$posKey, $posSection]) {
            $section = $isSimplified ? $posSection . '_short' : $posSection;
            $block = $this->pickTextBlock($posKey, $section, $subject, $chart, $language, $isSimplified);
            if ($block === null) {
                continue;
            }

            $sections[] = new NatalReportSectionDTO(
                key:         $posKey,
                section:     $section,
                title:       $this->humanTitle($posKey),
                text:        $block->text,
                tone:        $block->tone,
                textBlockId: $block->id,
            );
        }

        // House Lords (pre-generated)
        foreach ($this->houseLordSections($chart, $isSimplified, $language) as $section) {
            $sections[] = $section;
        }

        return new NatalReportDTO(
            chart:    $chart,
            sections: $sections,
            mode:     $mode,
            language: $language,
        );
    }

    // -----------------------------------------------------------------------
    // AI Level 1 — TextBlocks + AI intro/transitions/conclusion
    // -----------------------------------------------------------------------

    private function buildAiL1(
        HoroscopeSubject $subject,
        NatalChart       $chart,
        string           $language,
    ): NatalReportDTO {
        if ($this->aiProvider === null) {
            // Graceful fallback to organic if no AI provider configured
            return $this->buildOrganic($subject, $chart, ReportMode::Organic, $language);
        }

        // 1. Assemble organic sections (text blocks, not sent to AI)
        $organic = $this->buildOrganic($subject, $chart, ReportMode::Organic, $language);

        // 2. AI generates only the portrait introduction from chart data
        $chartSummary    = $this->chartSummary($chart);
        $houseLordsSummary = $this->houseLordsSummary($chart);
        $system          = $this->aiSystemPrompt($language);

        $name   = $subject->name ?? 'you';
        $prompt = <<<PROMPT
        You are an astrologer writing a natal chart portrait for {$name}. Below is the exact chart data.

        **Chart Data:**
        {$chartSummary}

        **House Lords (whole sign houses):**
        {$houseLordsSummary}

        Write a portrait introduction (exactly 5 paragraphs). Rules:
        - Paragraphs 1-4: 4-5 sentences each
        - Paragraph 5: 6-8 sentences — a synthesis that pulls the whole chart together, how the patterns interact, what kind of person emerges from all of this
        - Address the person directly as "you/your", never as "this person" or "the native"
        - Reference specific planets, signs, and aspects from the data — name them explicitly
        - Describe what these placements produce in real behaviour and daily life — no abstract themes
        - Do NOT mention degrees, orb numbers, or technical astrology terms like "stellium", "mutual reception", "trine", "square" — translate everything into lived experience
        - Avoid generic phrases ("bridge-builder", "creative tension", "old soul", "rare gift", "profound depth")
        - Write like a psychologist giving honest feedback after reviewing someone's profile — not an astrologer
        - Short sentences. Plain words. Zero poetic language.
        - Forbidden: metaphors, "fire", "waves", "dissolves", "tension", "standoff", "dance", "lifetime", "journey", "path", "depth", "pull", "force"
        - Instead of "Neptune blurs boundaries" → write "you sometimes commit to things before checking if they're actually what you assumed"
        - Instead of "Mars in tension with Pluto" → write "when someone pushes back on a decision you've made, you dig in rather than reconsider"
        - Describe what the person actually does in real situations with real people
        - Paragraph 1: the most striking planetary pattern and what it makes you actually do or feel
        - Paragraph 2: the main inner conflict between two forces and how it shows up in your life
        - Paragraph 3: a specific behavioural pattern or blind spot that follows from this chart
        - Paragraph 4: something most people around you probably misunderstand about you
        - Paragraph 5: synthesis — how all of this adds up, what kind of person emerges from this chart taken as a whole

        After the 5-paragraph portrait, write the house lords section. For each house from 2nd through 12th:
        - Name the house ruler (lord) and where it sits (sign + house) using the House Lords data above
        - Describe in 3-4 sentences what this means in real daily behaviour
        - Address as "you/your", plain words, no jargon, no metaphors
        - Do NOT mention degrees or technical astrology terms

        Return valid JSON with exactly two keys:
        - introduction: string with exactly 5 <p> tags
        - house_lords: array of exactly 11 objects, one per house (2nd through 12th), each with:
            - house: integer (2 through 12)
            - text: string of 3-4 sentences (plain text, no HTML tags)
        Use <strong> for key qualities, <em> for planet/sign names in introduction only.
        PROMPT;

        $response = $this->aiProvider->generate($prompt, $system, 6000);
        $data     = $this->parseJsonResponse($response->text);

        $houseLordsRaw = $data['house_lords'] ?? null;
        $houseLordsJson = is_array($houseLordsRaw) ? json_encode($houseLordsRaw) : $houseLordsRaw;

        return new NatalReportDTO(
            chart:        $chart,
            sections:     $organic->sections,
            mode:         ReportMode::AiL1,
            language:     $language,
            introduction: $data['introduction'] ?? null,
            houseLords:   $houseLordsJson,
            aiTokensIn:   $response->inputTokens,
            aiTokensOut:  $response->outputTokens,
            aiCostUsd:    $response->costUsd,
        );
    }

    // -----------------------------------------------------------------------
    // AI Level 1 Haiku — Simplified text blocks + 3-sentence compact portrait
    // -----------------------------------------------------------------------

    private function buildAiL1Haiku(
        HoroscopeSubject $subject,
        NatalChart       $chart,
        string           $language,
    ): NatalReportDTO {
        if ($this->aiProvider === null) {
            return $this->buildOrganic($subject, $chart, ReportMode::Simplified, $language);
        }

        $organic      = $this->buildOrganic($subject, $chart, ReportMode::Simplified, $language);
        $chartSummary = $this->chartSummary($chart);
        $system       = $this->aiSystemPrompt($language);
        $name         = $subject->name ?? 'you';

        $prompt = <<<PROMPT
        You are writing a natal chart portrait for {$name}. Below is the chart data.

        **Chart Data:**
        {$chartSummary}

        Write exactly 1 paragraph of 5 short sentences. Rules:
        - Each sentence max 12 words
        - Address as "you/your" — but vary sentence structure so "you" doesn't start every sentence
        - Reference specific placements from the data — translate into real behaviour, not astrology terms
        - No planet names, no sign names, no aspect names — describe what the person actually does
        - Plain everyday words. No jargon, no metaphors, no poetic language.
        - Sentence 1: the most defining trait visible to others
        - Sentence 2: the main inner tension or conflict
        - Sentence 3: a specific behavioural pattern or blind spot
        - Sentence 4: what most people misunderstand about you
        - Sentence 5: what ties it all together — the core of who you are

        Return valid JSON with a single key: introduction (string with 1 <p> tag).
        Use <strong> for key qualities.
        PROMPT;

        $response = $this->aiProvider->generate($prompt, $system, 500);
        $data     = $this->parseJsonResponse($response->text);

        return new NatalReportDTO(
            chart:        $chart,
            sections:     $organic->sections,
            mode:         ReportMode::AiL1Haiku,
            language:     $language,
            introduction: $data['introduction'] ?? null,
            aiTokensIn:   $response->inputTokens,
            aiTokensOut:  $response->outputTokens,
            aiCostUsd:    $response->costUsd,
        );
    }

    /**
     * Return a cached report DTO for the subject, or null if none exists.
     * Does not generate anything — safe to call without AI cost.
     */
    public function loadCached(HoroscopeSubject $subject, ReportMode $mode, string $language): ?NatalReportDTO
    {
        if (!$subject->exists) {
            return null;
        }

        $chart  = $this->aspectCalculator->calculate($subject);
        $cached = $this->findCached($subject, $chart, $mode, $language);

        return $cached ? $this->hydrateFromModel($cached, $chart) : null;
    }

    // -----------------------------------------------------------------------
    // Persistence
    // -----------------------------------------------------------------------

    private function findCached(HoroscopeSubject $subject, NatalChart $chart, ReportMode $mode, string $language): ?NatalReport
    {
        return NatalReport::where('profile_id', $subject->id)
            ->where('natal_chart_id', $chart->id)
            ->where('mode', $mode->value)
            ->where('language', $language)
            ->first();
    }

    private function persist(HoroscopeSubject $subject, NatalReportDTO $dto): void
    {
        $report = NatalReport::create([
            'profile_id'     => $subject->id,
            'natal_chart_id' => $dto->chart->id,
            'mode'           => $dto->mode->value,
            'language'       => $dto->language,
        ]);

        // Store AI intro/conclusion — cost goes on the ai_texts row
        if ($dto->introduction !== null) {
            $intro = $report->aiTexts()->create([
                'type'       => 'introduction',
                'text'       => $dto->introduction,
                'tokens_in'  => $dto->aiTokensIn,
                'tokens_out' => $dto->aiTokensOut,
                'cost_usd'   => $dto->aiCostUsd,
            ]);
            $report->update(['introduction_ai_text_id' => $intro->id]);
        }

        if ($dto->houseLords !== null) {
            $hl = $report->aiTexts()->create(['type' => 'house_lords', 'text' => $dto->houseLords]);
            $report->update(['house_lords_ai_text_id' => $hl->id]);
        }

        if ($dto->conclusion !== null) {
            $concl = $report->aiTexts()->create(['type' => 'conclusion', 'text' => $dto->conclusion]);
            $report->update(['conclusion_ai_text_id' => $concl->id]);
        }

        foreach ($dto->sections as $pos => $section) {
            $sectionRow = [
                'natal_report_id' => $report->id,
                'position'        => $pos,
                'key'             => $section->key,
                'section'         => $section->section,
                'text_block_id'   => $section->textBlockId,
            ];

            if ($section->transition !== null) {
                $transRow = $report->aiTexts()->create(['type' => 'transition', 'text' => $section->transition]);
                $sectionRow['transition_ai_text_id'] = $transRow->id;
            }

            $report->sections()->create($sectionRow);
        }
    }

    private function hydrateFromModel(NatalReport $model, NatalChart $chart): NatalReportDTO
    {
        $model->load(['sections.textBlock', 'sections.aiText', 'sections.transitionAiText', 'introductionAiText', 'houseLordsAiText', 'conclusionAiText']);

        $sections = $model->sections->map(function ($row) {
            $text = $row->textBlock?->text ?? $row->aiText?->text ?? '';
            $tone = $row->textBlock?->tone ?? 'neutral';

            return new NatalReportSectionDTO(
                key:        $row->key,
                section:    $row->section ?? 'natal',
                title:      $this->humanTitle($row->key),
                text:       $text,
                tone:       $tone,
                transition: $row->transitionAiText?->text,
            );
        })->all();

        return new NatalReportDTO(
            chart:        $chart,
            sections:     $sections,
            mode:         $model->mode,
            language:     $model->language,
            introduction: $model->introductionAiText?->text,
            houseLords:   $model->houseLordsAiText?->text,
            conclusion:   $model->conclusionAiText?->text,
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function pickTextBlock(
        string           $key,
        string           $section,
        HoroscopeSubject $subject,
        NatalChart       $chart,
        string           $language,
        bool             $simplified,
    ): ?TextBlock {
        $totalVariants = TextBlock::forKey($key, $section, $language)->count();

        if ($totalVariants === 0) {
            return null;
        }

        // Simplified mode always uses variant 1 — text is condensed and definitive,
        // so rotation across variants makes no sense. One concise text per key is enough.
        if ($simplified) {
            return TextBlock::pick($key, $section, 1, $language);
        }

        $subjectId = $subject->exists ? $subject->id : 'guest';
        $variant   = $this->variantPicker->pick(
            (string) $subjectId,
            $chart->calculated_at?->toDateString() ?? now()->toDateString(),
            $key,
            $totalVariants,
        );

        return TextBlock::pick($key, $section, $variant, $language);
    }

    private function aspectKey(array $aspect): string
    {
        $body1 = strtolower(PlanetaryPosition::BODY_NAMES[$aspect['body_a']] ?? '');
        $type  = strtolower($aspect['aspect'] ?? '');
        $body2 = strtolower(PlanetaryPosition::BODY_NAMES[$aspect['body_b']] ?? '');

        return "{$body1}_{$type}_{$body2}";
    }

    private function humanTitle(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Keys and sections for Planet Positions (natal_ascendant + natal_positions).
     *
     * Returns array of [$key, $section] pairs, ordered:
     *   1. natal_ascendant — "ascendant_in_{sign}" (when ASC is known)
     *   2. natal_positions — Sun, Moon, Mercury, Venus, Mars in sign+house (or sign only)
     *
     * Jupiter/Saturn/outer planets are covered by the aspect sections only.
     */
    /**
     * Modern rulership map: sign index → body id.
     */
    private const ASC_LORD_MAP = [
        0  => 4,  // Aries → Mars
        1  => 3,  // Taurus → Venus
        2  => 2,  // Gemini → Mercury
        3  => 1,  // Cancer → Moon
        4  => 0,  // Leo → Sun
        5  => 2,  // Virgo → Mercury
        6  => 3,  // Libra → Venus
        7  => 9,  // Scorpio → Pluto
        8  => 5,  // Sagittarius → Jupiter
        9  => 6,  // Capricorn → Saturn
        10 => 7,  // Aquarius → Uranus
        11 => 8,  // Pisces → Neptune
    ];

    /**
     * Build the ASC lord key: "asc_in_{asc_sign}_lord_in_{lord_sign}_house_{lord_house}".
     * Returns null if ASC is unknown or lord planet not found in chart.
     */
    private function ascLordKey(NatalChart $chart): ?string
    {
        if ($chart->ascendant === null) {
            return null;
        }

        $ascSignIdx = (int) floor($chart->ascendant / 30);
        $ascSign    = strtolower(PlanetaryPosition::SIGN_NAMES[$ascSignIdx] ?? '');
        if ($ascSign === '') {
            return null;
        }

        $lordBodyId = self::ASC_LORD_MAP[$ascSignIdx] ?? null;
        if ($lordBodyId === null) {
            return null;
        }

        $lordPlanet = collect($chart->planets ?? [])->firstWhere('body', $lordBodyId);
        if ($lordPlanet === null) {
            return null;
        }

        $lordSign  = strtolower(PlanetaryPosition::SIGN_NAMES[$lordPlanet['sign']] ?? '');
        $lordHouse = $lordPlanet['house'] ?? null;

        if ($lordSign === '' || $lordHouse === null) {
            return null;
        }

        return "asc_in_{$ascSign}_lord_in_{$lordSign}_house_{$lordHouse}";
    }

    private function positionKeys(NatalChart $chart): array
    {
        $pairs     = [];
        $hasHouses = $chart->ascendant !== null;

        // Ascendant first (natal_ascendant section)
        if ($hasHouses) {
            $ascSign = strtolower(PlanetaryPosition::SIGN_NAMES[(int) floor($chart->ascendant / 30)] ?? '');
            if ($ascSign) {
                $pairs[] = ["ascendant_in_{$ascSign}", 'natal_ascendant'];
            }
        }

        // Sun, Moon, Mercury, Venus, Mars (natal_positions section)
        // Jupiter/Saturn and outer planets are excluded — covered by aspects.
        $positionBodies = [0, 1, 2, 3, 4];

        foreach ($chart->planets ?? [] as $planet) {
            if (!in_array($planet['body'], $positionBodies, true)) {
                continue;
            }

            $body  = strtolower(PlanetaryPosition::BODY_NAMES[$planet['body']] ?? '');
            $sign  = strtolower(PlanetaryPosition::SIGN_NAMES[$planet['sign']] ?? '');
            $house = $planet['house'] ?? null;

            if (!$body || !$sign) {
                continue;
            }

            $key     = $house ? "{$body}_in_{$sign}_house_{$house}" : "{$body}_in_{$sign}";
            $pairs[] = [$key, 'natal_positions'];
        }

        return $pairs;
    }

    /**
     * Load pre-generated house lord text blocks for houses 1-12.
     * Key format: house_{house}_cusp_{cusp_sign}_lord_in_{lord_sign}_house_{lord_house}
     */
    private function houseLordSections(NatalChart $chart, bool $isSimplified, string $language): array
    {
        if ($chart->ascendant === null) {
            return [];
        }

        $ascSignIdx = (int) floor($chart->ascendant / 30);
        $planets    = collect($chart->planets ?? []);
        $section    = $isSimplified ? 'natal_house_lords_short' : 'natal_house_lords';
        $result     = [];

        for ($house = 1; $house <= 12; $house++) {
            $cuspSignIdx = ($ascSignIdx + $house - 1) % 12;
            $cuspSign    = strtolower(PlanetaryPosition::SIGN_NAMES[$cuspSignIdx] ?? '');
            if ($cuspSign === '') {
                continue;
            }

            $lordBodyId = self::ASC_LORD_MAP[$cuspSignIdx] ?? null;
            if ($lordBodyId === null) {
                continue;
            }

            $lordPlanet = $planets->firstWhere('body', $lordBodyId);
            if ($lordPlanet === null) {
                continue;
            }

            $lordSign  = strtolower(PlanetaryPosition::SIGN_NAMES[$lordPlanet['sign']] ?? '');
            $lordHouse = $lordPlanet['house'] ?? null;

            if ($lordSign === '' || $lordHouse === null) {
                continue;
            }

            $key   = "house_{$house}_cusp_{$cuspSign}_lord_in_{$lordSign}_house_{$lordHouse}";
            $block = TextBlock::pick($key, $section, 1, $language);

            if ($block === null) {
                continue;
            }

            $result[] = new NatalReportSectionDTO(
                key:         $key,
                section:     $section,
                title:       $this->humanTitle($key),
                text:        $block->text,
                tone:        $block->tone,
                textBlockId: $block->id,
            );
        }

        return $result;
    }

    /**
     * Build a house lords summary for houses 2-12 using whole sign houses.
     * House 1 = ASC sign, house 2 = next sign, etc.
     *
     * Returns lines like:
     *   House 2: Taurus → Venus in Aries, house 9
     */
    private function houseLordsSummary(NatalChart $chart): string
    {
        if ($chart->ascendant === null) {
            return 'House data unavailable (no ASC).';
        }

        $ascSignIdx = (int) floor($chart->ascendant / 30);
        $planets    = collect($chart->planets ?? []);
        $lines      = [];

        for ($house = 2; $house <= 12; $house++) {
            $signIdx  = ($ascSignIdx + $house - 1) % 12;
            $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? $signIdx;

            $lordBodyId = self::ASC_LORD_MAP[$signIdx] ?? null;
            if ($lordBodyId === null) {
                $lines[] = "House {$house}: {$signName} → ruler unknown";
                continue;
            }

            $lordName   = PlanetaryPosition::BODY_NAMES[$lordBodyId] ?? $lordBodyId;
            $lordPlanet = $planets->firstWhere('body', $lordBodyId);

            if ($lordPlanet === null) {
                $lines[] = "House {$house}: {$signName} → {$lordName} (position unknown)";
                continue;
            }

            $lordSign  = PlanetaryPosition::SIGN_NAMES[$lordPlanet['sign']] ?? $lordPlanet['sign'];
            $lordHouse = $lordPlanet['house'] ?? null;
            $houseStr  = $lordHouse ? ", house {$lordHouse}" : '';

            $lines[] = "House {$house}: {$signName} → {$lordName} in {$lordSign}{$houseStr}";
        }

        return implode("\n", $lines);
    }

    private function chartSummary(NatalChart $chart): string
    {
        $lines = [];

        // ASC line
        if ($chart->ascendant !== null) {
            $ascSignIdx = (int) floor($chart->ascendant / 30);
            $ascSign    = PlanetaryPosition::SIGN_NAMES[$ascSignIdx] ?? $ascSignIdx;
            $lines[]    = "ASC: {$ascSign}";

            // ASC Lord
            $lordBodyId = self::ASC_LORD_MAP[$ascSignIdx] ?? null;
            if ($lordBodyId !== null) {
                $lordPlanet = collect($chart->planets ?? [])->firstWhere('body', $lordBodyId);
                if ($lordPlanet !== null) {
                    $lordName  = PlanetaryPosition::BODY_NAMES[$lordBodyId] ?? $lordBodyId;
                    $lordSign  = PlanetaryPosition::SIGN_NAMES[$lordPlanet['sign']] ?? $lordPlanet['sign'];
                    $lordHouse = $lordPlanet['house'] ?? null;
                    $houseStr  = $lordHouse ? ", house {$lordHouse}" : '';
                    $lines[]   = "ASC Lord: {$lordName} in {$lordSign}{$houseStr}";
                }
            }
        }

        foreach ($chart->planets ?? [] as $p) {
            $body  = PlanetaryPosition::BODY_NAMES[$p['body']] ?? $p['body'];
            $sign  = PlanetaryPosition::SIGN_NAMES[$p['sign']] ?? $p['sign'];
            $deg   = round($p['degree'], 2);
            $retro = ($p['is_retrograde'] ?? false) ? ' Rx' : '';
            $house = isset($p['house']) ? ", house {$p['house']}" : '';
            $lines[] = "{$body}: {$sign} {$deg}°{$retro}{$house}";
        }

        foreach ($chart->aspects ?? [] as $a) {
            $b1    = PlanetaryPosition::BODY_NAMES[$a['body_a']] ?? $a['body_a'];
            $b2    = PlanetaryPosition::BODY_NAMES[$a['body_b']] ?? $a['body_b'];
            $type  = $a['aspect'] ?? '';
            $orb   = round($a['orb'], 2);
            $lines[] = "{$b1} {$type} {$b2} (orb: {$orb}°)";
        }

        return implode("\n", $lines);
    }

    private function aiSystemPrompt(string $language): string
    {
        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';

        return "You are a professional astrologer writing insightful, warm, and empowering natal chart interpretations. {$langNote} Always use HTML formatting: <strong> for key qualities and themes, <em> for planet and sign names. Return only valid JSON as instructed.";
    }

    private function parseJsonResponse(string $raw): array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/```\s*$/m', '', $clean ?? $raw);
        $clean = trim($clean ?? $raw);

        return json_decode($clean, true) ?? [];
    }
}
