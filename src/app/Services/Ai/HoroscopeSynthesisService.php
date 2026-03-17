<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\DataTransfer\AiResponse;
use App\DataTransfer\Horoscope\SolarReturnDTO;
use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Carbon\Carbon;

class HoroscopeSynthesisService
{
    public function __construct(
        private readonly AiProvider $ai,
    ) {}

    // ── Public methods ────────────────────────────────────────────────────

    public function daily(
        array $transitNatalAspects,
        array $retrogrades,
        array $areasOfLife,
        array $natalPlanets,
        Carbon $date,
        string $moonSignName,
        string $moonPhaseName,
        int $lunarDay,
        bool $simplified = false,
        int $profileId = 0,
        string $language = 'en',
        ?string $gender = null,
    ): ?AiResponse {
        $cacheKey = 'daily_' . $profileId . '_' . $date->toDateString() . ($simplified ? '_short' : '') . ($gender ? '_' . $gender : '');
        $cached   = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();

        if ($cached) {
            return new AiResponse(text: $cached->text, inputTokens: 0, outputTokens: 0, costUsd: 0.0);
        }

        $natalLines = [];
        foreach ($natalPlanets as $np) {
            if (! in_array($np['body'] ?? -1, [0, 1])) {
                continue;
            }
            $name = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$np['sign']] ?? '';
            $natalLines[] = "natal {$name} in {$sign}";
        }

        $prompt  = "Date: {$date->format('l, j F Y')}\n";
        $prompt .= "Moon: {$moonSignName}, {$moonPhaseName}, lunar day {$lunarDay}\n";
        if ($natalLines) {
            $prompt .= "Natal context: " . implode(', ', $natalLines) . "\n";
        }
        if ($transitNatalAspects) {
            $prompt .= "\nActive transits:\n";
            foreach ($transitNatalAspects as $asp) {
                $aspLabel = ucfirst(str_replace('_', ' ', $asp->aspect));
                $prompt  .= "  Transit {$asp->transitName} {$aspLabel} natal {$asp->natalName}\n";
            }
        }

        if ($retrogrades) {
            $rxNames = array_map(fn ($rx) => "{$rx->name} Rx in {$rx->signName}", $retrogrades);
            $prompt .= "\nRetrogrades: " . implode(', ', $rxNames) . "\n";
        }

        if ($areasOfLife) {
            $prompt .= "\nAreas of life:\n";
            foreach ($areasOfLife as $area) {
                $stars   = $area->rating === 0
                    ? '⚠ wait'
                    : str_repeat('★', $area->rating) . str_repeat('☆', $area->maxRating - $area->rating);
                $prompt .= "  {$area->name}: {$stars}\n";
            }
        }

        $prompt .= $simplified
            ? "\n\nWrite exactly 1 paragraph (2–3 sentences) as a short daily horoscope intro based on this astrological data."
            : "\n\nWrite exactly 2 paragraphs as a daily horoscope intro based on this astrological data.";

        $paragraphRule = $simplified
            ? '- 1 paragraph only — 2–3 sentences total'
            : "- Exactly 2 paragraphs separated by a blank line — no headers, no bullets, no lists\n- Each paragraph: 3–4 sentences";

        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
        $genderNote = ($gender && $language !== 'en') ? "\nAddress the person using " . ($gender === 'female' ? 'feminine' : 'masculine') . " grammatical forms in the target language." : '';
        $system = "{$langNote}{$genderNote}\n\nYou are writing a personalized daily horoscope intro for a single person.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "- CRITICAL: Vary sentence openings — combined, fewer than 20% may start with \"You\" or \"Your\". Never two consecutive sentences starting with \"You\" or \"Your\". Use: planet names, situation openers (\"When\", \"At work\", \"In relationships\", \"Under pressure\"), observations (\"Most people\", \"What others see is\", \"The pattern is\"), framing devices (\"Behind that\", \"The real issue is\", \"Over time\", \"Privately\", \"The problem is\", \"In practice\")\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . ($simplified ? "- Cut all filler: no \"The good news is\", \"This is a day for\", \"At the same time\", \"which means\", \"so if you\" — every sentence states a fact or concrete action\n" : '')
            . "- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real situations — concrete behaviour only\n"
            . ($simplified ? '' : "- First paragraph: the overall tone of the day based on the sky (moon, transits, retrogrades)\n- Second paragraph: the personal angle — what these transits activate for this specific person today\n")
            . "- Do NOT start with \"Today is...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $this->ai->generate($prompt, $system, maxTokens: 500);

            if ($profileId > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1, 'gender' => $gender],
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

            return $response;
        } catch (\Exception $e) {
            \Log::warning('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    public function weekly(
        array $transitNatalAspects,
        array $retrogrades,
        array $areasOfLife,
        array $natalPlanets,
        Carbon $monday,
        Carbon $sunday,
        string $moonSignName,
        bool $simplified = false,
        int $profileId = 0,
        string $language = 'en',
        ?string $gender = null,
    ): ?AiResponse {
        $cacheKey = 'weekly_' . $profileId . '_' . $monday->toDateString() . ($simplified ? '_short' : '') . ($gender ? '_' . $gender : '');
        $cached   = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();

        if ($cached) {
            return new AiResponse(text: $cached->text, inputTokens: 0, outputTokens: 0, costUsd: 0.0);
        }

        $natalLines = [];
        foreach ($natalPlanets as $np) {
            if (! in_array($np['body'] ?? -1, [0, 1])) {
                continue;
            }
            $name = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$np['sign']] ?? '';
            $natalLines[] = "natal {$name} in {$sign}";
        }

        $prompt  = "Week: {$monday->format('j F')} – {$sunday->format('j F Y')}\n";
        $prompt .= "Moon sign at week start: {$moonSignName}\n";
        if ($natalLines) {
            $prompt .= "Natal context: " . implode(', ', $natalLines) . "\n";
        }
        if ($transitNatalAspects) {
            $prompt .= "\nActive transits:\n";
            foreach ($transitNatalAspects as $asp) {
                $aspLabel = ucfirst(str_replace('_', ' ', $asp->aspect));
                $prompt  .= "  Transit {$asp->transitName} {$aspLabel} natal {$asp->natalName}\n";
            }
        }

        if ($retrogrades) {
            $rxNames = array_map(fn ($rx) => "{$rx->name} Rx in {$rx->signName}", $retrogrades);
            $prompt .= "\nRetrogrades: " . implode(', ', $rxNames) . "\n";
        }

        if ($areasOfLife) {
            $prompt .= "\nAreas of life:\n";
            foreach ($areasOfLife as $area) {
                $stars   = $area->rating === 0
                    ? '⚠ wait'
                    : str_repeat('★', $area->rating) . str_repeat('☆', $area->maxRating - $area->rating);
                $prompt .= "  {$area->name}: {$stars}\n";
            }
        }

        $prompt .= $simplified
            ? "\n\nWrite exactly 1 paragraph (2–3 sentences) as a short weekly horoscope intro based on this astrological data."
            : "\n\nWrite exactly 2 paragraphs as a weekly horoscope intro based on this astrological data.";

        $paragraphRule = $simplified
            ? '- 1 paragraph only — 2–3 sentences total'
            : "- Exactly 2 paragraphs separated by a blank line — no headers, no bullets, no lists\n- Each paragraph: 3–4 sentences";

        $langNoteW = $language !== 'en' ? "Write in language code: {$language}.\n" : '';
        $genderNoteW = ($gender && $language !== 'en') ? "Address the person using " . ($gender === 'female' ? 'feminine' : 'masculine') . " grammatical forms in the target language.\n" : '';
        $system = "{$langNoteW}{$genderNoteW}You are writing a personalized weekly horoscope intro for a single person.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "- CRITICAL: Vary sentence openings — combined, fewer than 20% may start with \"You\" or \"Your\". Never two consecutive sentences starting with \"You\" or \"Your\". Use: planet names, situation openers (\"When\", \"At work\", \"In relationships\", \"Under pressure\"), observations (\"Most people\", \"What others see is\", \"The pattern is\"), framing devices (\"Behind that\", \"The real issue is\", \"Over time\", \"Privately\", \"The problem is\", \"In practice\")\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . ($simplified ? "- Cut all filler: no \"The good news is\", \"This week is about\", \"At the same time\" — every sentence states a fact or concrete action\n" : '')
            . "- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real situations — concrete behaviour only\n"
            . ($simplified ? '' : "- First paragraph: the overall theme of the week based on the sky\n- Second paragraph: the personal angle — what these transits activate for this specific person\n")
            . "- Do NOT start with \"This week...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $this->ai->generate($prompt, $system, maxTokens: 500);

            if ($profileId > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1, 'gender' => $gender],
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

            return $response;
        } catch (\Exception $e) {
            \Log::warning('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    public function monthly(
        array $transitNatalAspects,
        array $retrogrades,
        array $areasOfLife,
        array $natalPlanets,
        Carbon $monthStart,
        Carbon $monthEnd,
        string $moonSignName,
        bool $simplified = false,
        int $profileId = 0,
        string $language = 'en',
        ?string $gender = null,
    ): ?AiResponse {
        $cacheKey = 'monthly_' . $profileId . '_' . $monthStart->format('Y-m') . ($simplified ? '_short' : '') . ($gender ? '_' . $gender : '');
        $cached   = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();

        if ($cached) {
            return new AiResponse(text: $cached->text, inputTokens: 0, outputTokens: 0, costUsd: 0.0);
        }

        $natalLines = [];
        foreach ($natalPlanets as $np) {
            if (! in_array($np['body'] ?? -1, [0, 1], true)) {
                continue;
            }
            $name = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$np['sign']] ?? '';
            $natalLines[] = "natal {$name} in {$sign}";
        }

        $prompt  = "Month: {$monthStart->format('F Y')}\n";
        $prompt .= "Moon sign at month start: {$moonSignName}\n";
        if ($natalLines) {
            $prompt .= "Natal context: " . implode(', ', $natalLines) . "\n";
        }
        if ($transitNatalAspects) {
            $prompt .= "\nActive transits:\n";
            foreach ($transitNatalAspects as $asp) {
                $aspLabel = ucfirst(str_replace('_', ' ', $asp->aspect));
                $prompt  .= "  Transit {$asp->transitName} {$aspLabel} natal {$asp->natalName}\n";
            }
        }

        if ($retrogrades) {
            $rxNames = array_map(fn ($rx) => "{$rx->name} Rx in {$rx->signName}", $retrogrades);
            $prompt .= "\nRetrogrades: " . implode(', ', $rxNames) . "\n";
        }

        if ($areasOfLife) {
            $prompt .= "\nAreas of life:\n";
            foreach ($areasOfLife as $area) {
                $stars   = $area->rating === 0
                    ? '⚠ wait'
                    : str_repeat('★', $area->rating) . str_repeat('☆', $area->maxRating - $area->rating);
                $prompt .= "  {$area->name}: {$stars}\n";
            }
        }

        $prompt .= $simplified
            ? "\n\nWrite exactly 1 paragraph (2–3 sentences) as a short monthly horoscope intro based on this astrological data."
            : "\n\nWrite exactly 3 paragraphs as a monthly horoscope intro based on this astrological data.";

        $paragraphRule = $simplified
            ? '- 1 paragraph only — 2–3 sentences total'
            : "- Exactly 3 paragraphs separated by blank lines — no headers, no bullets, no lists\n- Each paragraph: 3–4 sentences";

        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
        $genderNoteM = ($gender && $language !== 'en') ? "\nAddress the person using " . ($gender === 'female' ? 'feminine' : 'masculine') . " grammatical forms in the target language." : '';
        $system = "{$langNote}{$genderNoteM}\n\nYou are writing a personalized monthly horoscope intro for a single person.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "- CRITICAL: Vary sentence openings — combined, fewer than 20% may start with \"You\" or \"Your\". Never two consecutive sentences starting with \"You\" or \"Your\". Use: planet names, situation openers (\"When\", \"At work\", \"In relationships\", \"Under pressure\"), observations (\"Most people\", \"What others see is\", \"The pattern is\"), framing devices (\"Behind that\", \"The real issue is\", \"Over time\", \"Privately\", \"The problem is\", \"In practice\")\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . ($simplified ? "- Cut all filler — every sentence states a fact or concrete action\n" : '')
            . "- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real situations — concrete behaviour only\n"
            . ($simplified ? '' : "- First paragraph: the overall theme of the month based on the sky\n- Second paragraph: the personal angle — what these transits activate for this specific person\n- Third paragraph: practical focus — key period or what to pay attention to this month\n")
            . "- Do NOT start with \"This month...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $this->ai->generate($prompt, $system, maxTokens: 800);

            if ($profileId > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1, 'gender' => $gender],
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

            return $response;
        } catch (\Exception $e) {
            \Log::warning('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    public function solar(
        SolarReturnDTO $dto,
        array $natalPlanets,
        array $natalHouses,
        int $year,
        bool $simplified = false,
        int $profileId = 0,
        string $language = 'en',
        ?string $gender = null,
    ): ?AiResponse {
        $cacheKey = 'solar_' . $profileId . '_' . $year . ($simplified ? '_short' : '') . ($gender ? '_' . $gender : '');
        $cached   = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', $language)
            ->where('gender', $gender)
            ->first();

        if ($cached) {
            return new AiResponse(text: $cached->text, inputTokens: 0, outputTokens: 0, costUsd: 0.0);
        }

        // ── Build chart data prompt ───────────────────────────────────────
        $prompt  = "Solar Return Year: {$year}  \u{00B7}  City: {$dto->cityName}\n\n";

        // Solar return angles
        $prompt .= "=== SOLAR RETURN CHART ===\n";
        $prompt .= "ASC: {$dto->solarAscSignName}  \u{00B7}  MC: {$dto->solarMcSignName}\n\n";

        // Solar return planets with house
        if (! empty($dto->solarPlanets)) {
            $prompt .= "Solar Return Planets:\n";
            foreach ($dto->solarPlanets as $sp) {
                $house  = $this->findHouseForLon($sp->longitude, $dto->solarHouses) ?? 1;
                $rx     = $sp->isRetrograde ? ' Rx' : '';
                $deg    = (int) $sp->degreeInSign;
                $min    = (int) round(($sp->degreeInSign - $deg) * 60);
                $glyph  = self::BODY_GLYPHS[$sp->body] ?? '';
                $prompt .= sprintf("  %s %-8s  %-11s  %2d\u{00B0}%02d'%s  H%d\n",
                    $glyph, $sp->name, $sp->signName, $deg, $min, $rx, $house);
            }
            $prompt .= "\n";
        }

        // Natal planets with sign and house
        if (! empty($natalPlanets)) {
            $prompt .= "Natal Planets:\n";
            foreach ($natalPlanets as $np) {
                $body    = (int) ($np['body'] ?? -1);
                $lon     = (float) ($np['longitude'] ?? 0);
                $rx      = ! empty($np['is_retrograde']) ? ' Rx' : '';
                $house   = (int) ($np['house'] ?? $this->findHouseForLon($lon, $natalHouses) ?? 1);
                $signIdx = (int) floor($lon / 30);
                $signNm  = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
                $name    = PlanetaryPosition::BODY_NAMES[$body] ?? '';
                $glyph   = self::BODY_GLYPHS[$body] ?? '';
                $deg     = (int) fmod($lon, 30);
                $min     = (int) round((fmod($lon, 30) - $deg) * 60);
                $prompt .= sprintf("  %s %-8s  %-11s  %2d\u{00B0}%02d'%s  H%d\n",
                    $glyph, $name, $signNm, $deg, $min, $rx, $house);
            }
            $prompt .= "\n";
        }

        // Solar -> natal aspects sorted by orb (tightest first, top 20)
        if (! empty($dto->solarNatalAspects)) {
            $aspects = $dto->solarNatalAspects;
            usort($aspects, fn ($a, $b) => $a->orb <=> $b->orb);
            $prompt .= "=== SOLAR \u{2192} NATAL ASPECTS (tightest first) ===\n";
            foreach (array_slice($aspects, 0, 20) as $asp) {
                $aspWord = ucfirst(str_replace('_', ' ', $asp->aspect));
                $prompt .= sprintf("  Solar %-8s  %-14s  natal %-8s  %.1f\u{00B0}\n",
                    $asp->transitName, $aspWord, $asp->natalName, $asp->orb);
            }
            $prompt .= "\n";
        }

        // Progressions & Solar Arc directions
        $prompt .= "=== PROGRESSIONS & DIRECTIONS ===\n";
        $pmSign  = $dto->progressedMoon['signName'] ?? '';
        $pmHouse = $dto->progressedMoon['houseIndex'] ?? null;
        $psSign  = $dto->progressedSun['signName'] ?? '';
        $psHouse = $dto->progressedSun['houseIndex'] ?? null;
        $prompt .= "Progressed Moon: {$pmSign}" . ($pmHouse ? " H{$pmHouse}" : '') . "\n";
        $prompt .= "Progressed Sun:  {$psSign}" . ($psHouse ? " H{$psHouse}" : '') . "\n";

        foreach ($dto->solarArcDirections as $dir) {
            $aspWord = ucfirst(str_replace('_', ' ', $dir->aspect));
            $prompt .= "Solar Arc {$dir->directedName} {$aspWord} natal {$dir->natalTargetName}\n";
        }
        $prompt .= "\n";

        // Key lunations
        if (! empty($dto->lunations)) {
            $prompt .= "=== KEY LUNATIONS ===\n";
            foreach (array_slice($dto->lunations, 0, 8) as $lun) {
                $type  = $lun->type === 'new_moon' ? 'New Moon' : 'Full Moon';
                $house = $lun->house ? " H{$lun->house}" : '';
                $prompt .= "  {$lun->date}  {$type} {$lun->signName}{$house}\n";
            }
            $prompt .= "\n";
        }

        // Retrogrades active this year
        if (! empty($dto->retrogrades)) {
            $retroNames = array_map(fn ($r) => "{$r->name} in {$r->signName}", $dto->retrogrades);
            $prompt .= "Retrogrades this year: " . implode(', ', $retroNames) . "\n\n";
        }

        // Singleton / Missing element (solar planets body 0-9)
        $elGroups = ['fire' => [], 'earth' => [], 'air' => [], 'water' => []];
        foreach ($dto->solarPlanets as $sp) {
            if ($sp->body > 9) {
                continue;
            }
            $el = self::SIGN_ELEMENTS[$sp->signIndex] ?? null;
            if ($el) {
                $elGroups[$el][] = $sp->name;
            }
        }
        $elLines = [];
        foreach ($elGroups as $el => $names) {
            $label  = self::ELEMENT_LABELS[$el];
            $count  = count($names);
            $listed = implode(', ', $names);
            if ($count === 0) {
                $elLines[] = "  Missing {$label} (0 planets)";
            } elseif ($count === 1) {
                $elLines[] = "  Singleton {$label}: {$listed} only";
            } else {
                $elLines[] = "  {$label}: {$count} planets ({$listed})";
            }
        }
        if ($elLines) {
            $prompt .= "=== ELEMENT DISTRIBUTION (solar planets) ===\n";
            $prompt .= implode("\n", $elLines) . "\n\n";
        }

        // Writing instruction
        if ($simplified) {
            $prompt       .= "Write exactly 1 paragraph of 5 short sentences as a compact yearly horoscope overview.";
            $paragraphRule = "- 1 paragraph only — exactly 5 sentences, max 12 words each";
            $maxTokens     = 200;
        } else {
            $prompt       .= "Write exactly 5 paragraphs as a yearly horoscope portrait. Synthesize the chart — do not list placements.";
            $paragraphRule = "- Exactly 5 paragraphs separated by blank lines — no headers, no bullets\n"
                . "- Paragraphs 1-4: 4-5 sentences each\n"
                . "- Paragraph 5: 6-8 sentences — pull the whole year together as a synthesis";
            $maxTokens     = 900;
        }

        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
        $genderNoteS = ($gender && $language !== 'en') ? "\nAddress the person using " . ($gender === 'female' ? 'feminine' : 'masculine') . " grammatical forms in the target language." : '';
        $system   = "{$langNote}{$genderNoteS}\n\n"
            . "You are writing a personalized yearly horoscope portrait for a single person.\n"
            . "You receive the full solar return chart: planets, aspects, lunations, progressions.\n"
            . "Your job is to synthesize this data into a cohesive portrait — draw conclusions, do not list facts.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest, grounded feedback — not an astrologer reciting placements\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "- CRITICAL: Vary sentence openings — combined, fewer than 20% may start with \"You\" or \"Your\". Never two consecutive sentences starting with \"You\" or \"Your\". Use: planet names, situation openers (\"When\", \"At work\", \"In relationships\", \"Under pressure\"), observations (\"Most people\", \"What others see is\", \"The pattern is\"), framing devices (\"Behind that\", \"The real issue is\", \"Over time\", \"Privately\", \"The problem is\", \"In practice\")\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . "- Plain everyday words only — no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real life — concrete behaviour only\n"
            . "- Do NOT start with \"This year...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $this->ai->generate($prompt, $system, maxTokens: $maxTokens);

            if ($profileId > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1, 'gender' => $gender],
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

            return $response;
        } catch (\Exception $e) {
            \Log::warning('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    public function synastry(
        string $nameA,
        array $planetsA,
        string $nameB,
        array $planetsB,
        string $type,
        array $typeScores,
        array $aspects,
        bool $simplified = false,
        int $profileIdA = 0,
        int $profileIdB = 0,
        string $language = 'en',
    ): ?AiResponse {
        $idMin    = min($profileIdA, $profileIdB);
        $idMax    = max($profileIdA, $profileIdB);
        $cacheKey = 'synastry_' . $idMin . '_' . $idMax . '_' . $type . ($simplified ? '_short' : '');

        $cached = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', $language)
            ->whereNull('gender')
            ->first();

        if ($cached) {
            return new AiResponse(text: $cached->text, inputTokens: 0, outputTokens: 0, costUsd: 0.0);
        }

        // Build prompt
        $typeLabel = ucfirst($type);
        $prompt    = "Relationship type: {$typeLabel}\n\n";

        // Person A planets
        $prompt .= "=== {$nameA} ===\n";
        foreach ($planetsA as $p) {
            $body = (int) ($p['body'] ?? -1);
            if ($body > 9) continue;
            $name = PlanetaryPosition::BODY_NAMES[$body] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$p['sign'] ?? 0] ?? '';
            $rx   = ! empty($p['is_retrograde']) ? ' Rx' : '';
            $prompt .= "  {$name} in {$sign}{$rx}\n";
        }
        $prompt .= "\n";

        // Person B planets
        $prompt .= "=== {$nameB} ===\n";
        foreach ($planetsB as $p) {
            $body = (int) ($p['body'] ?? -1);
            if ($body > 9) continue;
            $name = PlanetaryPosition::BODY_NAMES[$body] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$p['sign'] ?? 0] ?? '';
            $rx   = ! empty($p['is_retrograde']) ? ' Rx' : '';
            $prompt .= "  {$name} in {$sign}{$rx}\n";
        }
        $prompt .= "\n";

        // Top cross-aspects (personal planets first, max 12)
        $topAspects = array_filter($aspects, fn ($a) => ($a['body_a'] ?? 99) <= 6 && ($a['body_b'] ?? 99) <= 6);
        usort($topAspects, fn ($a, $b) => ($a['orb'] ?? 99) <=> ($b['orb'] ?? 99));
        $topAspects = array_slice(array_values($topAspects), 0, 12);

        if ($topAspects) {
            $prompt .= "=== KEY CROSS-ASPECTS ===\n";
            foreach ($topAspects as $asp) {
                $bodyAName = PlanetaryPosition::BODY_NAMES[$asp['body_a'] ?? -1] ?? '?';
                $bodyBName = PlanetaryPosition::BODY_NAMES[$asp['body_b'] ?? -1] ?? '?';
                $aspWord   = ucfirst(str_replace('_', ' ', $asp['aspect'] ?? ''));
                $orb       = number_format($asp['orb'] ?? 0, 1);
                $prompt   .= "  {$nameA} {$bodyAName} {$aspWord} {$nameB} {$bodyBName}  {$orb}°\n";
            }
            $prompt .= "\n";
        }

        // Compatibility scores
        $prompt .= "=== COMPATIBILITY SCORES (★ out of 5) ===\n";
        foreach ($typeScores as $t => $stars) {
            if ($t === 'general') continue;
            $prompt .= '  ' . ucfirst($t) . ': ' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . "\n";
        }
        $prompt .= "\n";

        if ($simplified) {
            $prompt       .= "Write exactly 2 paragraphs as a compact synastry overview.";
            $paragraphRule = "- Exactly 2 paragraphs — 3–4 sentences each";
            $maxTokens     = 300;
        } elseif ($type === 'general') {
            $prompt       .= "Write exactly 6 paragraphs as a full synastry compatibility portrait.";
            $paragraphRule = "- Exactly 6 paragraphs separated by blank lines — no headers, no bullets\n"
                . "- Paragraph 1: the overall dynamic and first impression these two create together\n"
                . "- Paragraph 2: how they communicate and share ideas\n"
                . "- Paragraph 3: emotional connection and how they handle feelings\n"
                . "- Paragraph 4: where they genuinely support and strengthen each other\n"
                . "- Paragraph 5: recurring friction points and what triggers them\n"
                . "- Paragraph 6: the long-term picture — what makes or breaks this relationship";
            $maxTokens     = 900;
        } else {
            $prompt       .= "Write exactly 4 paragraphs as a synastry compatibility overview focused on the {$type} dimension.";
            $paragraphRule = "- Exactly 4 paragraphs separated by blank lines — no headers, no bullets\n"
                . "- Paragraph 1: the overall dynamic between these two people\n"
                . "- Paragraph 2: what works well in the {$type} dimension specifically\n"
                . "- Paragraph 3: the friction points and recurring tensions\n"
                . "- Paragraph 4: practical summary — what needs to happen for this to work";
            $maxTokens     = 600;
        }

        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
        $system   = "{$langNote}\n\n"
            . "You are writing a personalized synastry compatibility overview for two people.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address both people by name — do not use \"you\"\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . "- Plain everyday words only — no spiritual or psychological jargon\n"
            . "- Describe what these two people actually notice or do together — concrete behaviour only\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $this->ai->generate($prompt, $system, maxTokens: $maxTokens);

            if ($profileIdA > 0 && $profileIdB > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1, 'gender' => null],
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

            return $response;
        } catch (\Exception $e) {
            \Log::warning('AI synastry synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────

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

    // ── Constants ─────────────────────────────────────────────────────────

    private const BODY_GLYPHS = [
        0 => "\u{2609}", 1 => "\u{263D}",  2 => "\u{263F}", 3 => "\u{2640}",  4 => "\u{2642}",
        5 => "\u{2643}", 6 => "\u{2644}",  7 => "\u{2645}", 8 => "\u{2646}",  9 => "\u{2647}",
       10 => "\u{26B7}", 11 => "\u{260A}", 12 => "\u{26B8}",
    ];

    private const SIGN_ELEMENTS = [
        0 => 'fire',  1 => 'earth', 2 => 'air',   3 => 'water',
        4 => 'fire',  5 => 'earth', 6 => 'air',   7 => 'water',
        8 => 'fire',  9 => 'earth', 10 => 'air',  11 => 'water',
    ];

    private const ELEMENT_LABELS = [
        'fire' => 'Fire', 'earth' => 'Earth', 'air' => 'Air', 'water' => 'Water',
    ];
}
