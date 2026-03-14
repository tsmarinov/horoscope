<?php

namespace App\Console\Commands\Generate;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class GenerateTexts extends Command
{
    protected $signature = 'horoscope:generate-texts
                            {--section=natal : Section (natal, natal_short, natal_synthesis, singleton, singleton_short)}
                            {--variants=3 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate text blocks for horoscope sections using Anthropic API';

    private array $aspectLabels = [
        'conjunction'  => 'conjunction',
        'opposition'   => 'opposition',
        'trine'        => 'trine',
        'square'       => 'square',
        'sextile'      => 'sextile',
        'quincunx'     => 'quincunx (150°)',
        'semi_sextile' => 'semi-sextile (30°)',
    ];

    private array $aspectDynamics = [
        'conjunction'  => 'fusion and intensification — the two energies merge and amplify each other, for better or worse depending on the planets',
        'opposition'   => 'tension between two opposing poles — awareness through contrast; tends to play out through other people as projection',
        'trine'        => 'natural harmony and ease — a flowing talent that requires little effort to access; the person may take this gift for granted',
        'square'       => 'friction and challenge — drives action and growth through difficulty; the most motivating of all aspects once the tension is harnessed',
        'sextile'      => 'opportunity and gentle support — the talent is available but needs to be actively used; less automatic than a trine',
        'quincunx'     => 'constant adjustment required — the two energies speak different languages and never fully integrate; produces restless fine-tuning',
        'semi_sextile' => 'subtle friction between adjacent signs — mild incompatibility that creates low-level awareness and minor adjustment',
    ];

    private array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    private array $ordinals = [
        1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th', 6 => '6th',
        7 => '7th', 8 => '8th', 9 => '9th', 10 => '10th', 11 => '11th', 12 => '12th',
    ];

    private array $planetMeanings = [
        'sun'        => 'core identity, vitality, conscious self-expression, life purpose, where you seek recognition',
        'moon'       => 'emotions, instincts, subconscious habits, inner security, how you nurture and need to be nurtured',
        'mercury'    => 'thinking style, communication, how you process and exchange information, wit, adaptability',
        'venus'      => 'love, beauty, values, aesthetics, relationships, what you desire and attract',
        'mars'       => 'drive, ambition, assertion, physical energy, how you pursue what you want, anger and initiative',
        'jupiter'    => 'expansion, growth, optimism, philosophy, where you seek meaning and abundance',
        'saturn'     => 'discipline, structure, responsibility, earned mastery, where life demands sustained effort',
        'uranus'     => 'rebellion, sudden change, originality, freedom, where you resist convention and seek liberation',
        'neptune'    => 'dreams, spirituality, compassion, illusion, where you idealize, dissolve, or transcend',
        'pluto'      => 'transformation, power, depth, what cannot be avoided — the slow pressure that changes everything',
        'chiron'     => 'the wound that becomes the teacher — where you feel inadequate but develop the capacity to heal others',
        'north node' => 'karmic direction and soul growth — the unfamiliar territory that feels challenging but feeds evolution',
        'lilith'     => 'raw instinct, the rejected or suppressed self — where you resist being controlled and assert your wildest nature',
    ];

    private array $signMeanings = [
        'aries'       => 'cardinal fire — initiative, courage, directness, impatience, self-assertion, the pioneer',
        'taurus'      => 'fixed earth — persistence, sensuality, stability, material security, resistance to change, the builder',
        'gemini'      => 'mutable air — curiosity, adaptability, duality, communication, gathering information, the connector',
        'cancer'      => 'cardinal water — nurturing, emotional sensitivity, home and roots, memory, protectiveness',
        'leo'         => 'fixed fire — self-expression, creativity, generosity, pride, the desire for recognition, the performer',
        'virgo'       => 'mutable earth — analysis, precision, service, health consciousness, perfectionism, the craftsperson',
        'libra'       => 'cardinal air — balance, harmony, relationships, diplomacy, aesthetics, the urge to weigh all sides',
        'scorpio'     => 'fixed water — intensity, transformation, depth, power, secrets, emotional extremes, the investigator',
        'sagittarius' => 'mutable fire — freedom, philosophy, expansion, optimism, truth-seeking, restlessness, the seeker',
        'capricorn'   => 'cardinal earth — discipline, structure, ambition, practicality, authority, long-term thinking',
        'aquarius'    => 'fixed air — individuality, idealism, community, innovation, detachment, the reformer',
        'pisces'      => 'mutable water — compassion, imagination, spirituality, dissolution, empathy, the mystic',
    ];

    private array $houseMeanings = [
        1  => 'physical self and first impressions — how you project yourself into the world, the face you show',
        2  => 'personal resources, money, possessions, self-worth and what you value',
        3  => 'day-to-day communication, thinking, local environment, siblings, short journeys, learning',
        4  => 'home, roots, family of origin, private life, inner foundation, the past',
        5  => 'creativity, self-expression, romance, play, children, joy and risk-taking',
        6  => 'daily work and routine, health habits, service, practical skills, refinement',
        7  => 'partnerships, marriage, open enemies, what you project onto others and attract',
        8  => 'transformation, shared resources, sexuality, death and rebirth, the hidden and taboo',
        9  => 'philosophy, higher education, long-distance travel, beliefs, expanding your worldview',
        10 => 'career, public life, reputation, authority, what you build and are known for',
        11 => 'community, friendships, social ideals, collective goals, belonging to something larger',
        12 => 'solitude, the unconscious, hidden strengths, spiritual retreat, what you surrender or hide',
    ];

    private array $elementMeanings = [
        'fire'  => 'initiative, courage, enthusiasm, drive, confidence, spontaneous action, inspiration',
        'earth' => 'practicality, material stability, patience, reliability, physical grounding, tangible results',
        'air'   => 'intellect, communication, social connection, abstract thinking, objectivity, exchange of ideas',
        'water' => 'emotion, intuition, empathy, psychological depth, sensitivity, the inner world',
    ];

    public function handle(): int
    {
        $section  = $this->option('section');
        $variants = (int) $this->option('variants');
        $dryRun   = $this->option('dry-run');
        $model    = $this->option('model');
        $onlyKey  = $this->option('key');
        $fromKey  = $this->option('from-key');

        $client = new AnthropicClient(apiKey: config('services.anthropic.key'));

        $keys = $this->buildKeys($section);

        if (empty($keys)) {
            return self::FAILURE;
        }

        if ($onlyKey) {
            $keys = array_filter($keys, fn($k) => $k === $onlyKey);
            if (empty($keys)) {
                $this->error("Key not found: {$onlyKey}");
                return self::FAILURE;
            }
        }

        if ($fromKey) {
            $found = false;
            $keys  = array_filter($keys, function ($k) use ($fromKey, &$found) {
                if ($k === $fromKey) $found = true;
                return $found;
            });
        }

        $keys  = array_values($keys);
        $total = count($keys);
        $this->info("Section: {$section} | Keys: {$total} | Variants: {$variants} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        foreach ($keys as $i => $key) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] {$key}");

            if (! $dryRun && ! $onlyKey) {
                $existing = TextBlock::where('key', $key)
                    ->where('section', $section)
                    ->where('language', 'en')
                    ->count();

                if ($existing >= $variants) {
                    $this->line("  → already exists, skipping");
                    continue;
                }
            }

            $prompt = $this->buildPrompt($key, $section, $variants);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 1024,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $this->systemPrompt($section),
                );

                $raw  = $response->content[0]->text ?? '';
                $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
                $json = preg_replace('/```\s*$/m', '', $json);

                if ($dryRun) {
                    $this->line("  RESPONSE:\n" . $json);
                    return self::SUCCESS;
                }

                $blocks = json_decode($json, true);

                if (! is_array($blocks)) {
                    $this->error("  Invalid JSON response for {$key}");
                    continue;
                }

                foreach ($blocks as $block) {
                    TextBlock::updateOrCreate(
                        [
                            'key'      => $key,
                            'section'  => $section,
                            'language' => 'en',
                            'variant'  => $block['variant'],
                        ],
                        [
                            'text' => $block['text'],
                            'tone' => $block['tone'] ?? 'neutral',
                        ]
                    );
                }

                $this->line("  → saved {$variants} variants");

            } catch (\Exception $e) {
                $this->error("  ERROR: " . $e->getMessage());
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    // ─── System prompt ────────────────────────────────────────────────────────

    private function systemPrompt(string $section): string
    {
        $isShort = str_ends_with($section, '_short');

        if ($isShort) {
            return <<<SYS
You are an experienced astrologer writing compact one-sentence horoscope interpretations.

Style rules:
- Write exactly ONE sentence per variant (15-25 words)
- Capture the single most important quality or lived experience
- Direct and memorable — no subordinate clauses, no jargon
- Address the person as "you" or use their quality directly
- Each variant emphasises a different dimension of the same theme
SYS;
        }

        return <<<SYS
You are an experienced astrologer writing natal chart interpretations for a horoscope application.

Style rules:
- Write organic narrative paragraphs — not bullet lists, not catalogs of facts
- Address the person as "you" (gender-neutral, no he/she/his/her)
- Each text is 3–5 sentences (~80-100 words)
- Each variant takes a different angle (different imagery, different life area, different emphasis)
- Conversational but meaningful — as if spoken during a real consultation
- Do NOT start with "This placement...", "With [planet] in [sign]...", or "[Planet] in [sign] means..."
- Write directly about the person's lived experience, not about the symbols
- Use <strong>...</strong> for one key insight per block (the single most important phrase)
- Use <em>...</em> sparingly for planet/sign names embedded in flowing text
SYS;
    }

    // ─── Prompt router ────────────────────────────────────────────────────────

    private function buildPrompt(string $key, string $section, int $variants): string
    {
        return match($section) {
            'natal'           => $this->natalAspectPrompt($key, $variants, short: false),
            'natal_short'     => $this->natalAspectPrompt($key, $variants, short: true),
            'natal_synthesis' => $this->natalSynthesisPrompt($key, $variants),
            'singleton'       => $this->singletonPrompt($key, $variants, short: false),
            'singleton_short' => $this->singletonPrompt($key, $variants, short: true),
            default           => $this->unsupportedSection($section) ?? '',
        };
    }

    // ─── Natal aspects ────────────────────────────────────────────────────────

    private function natalAspectPrompt(string $key, int $variants, bool $short): string
    {
        ['bodyA' => $bodyA, 'bodyB' => $bodyB, 'aspect' => $aspect] = $this->parseAspectKey($key);

        $nameA       = PlanetaryPosition::BODY_NAMES[$bodyA] ?? $bodyA;
        $nameB       = PlanetaryPosition::BODY_NAMES[$bodyB] ?? $bodyB;
        $aspectLabel = $this->aspectLabels[$aspect] ?? $aspect;
        $toneHint    = $this->toneHint($aspect);
        $meaningA    = $this->planetMeanings[strtolower($nameA)] ?? '';
        $meaningB    = $this->planetMeanings[strtolower($nameB)] ?? '';
        $dynamic     = $this->aspectDynamics[$aspect] ?? '';

        if ($short) {
            return $this->jsonPrompt(
                "Write {$variants} one-sentence variants for natal aspect: {$nameA} {$aspectLabel} {$nameB}.\n"
                . "- {$nameA}: {$meaningA}\n"
                . "- {$nameB}: {$meaningB}\n"
                . "{$toneHint}",
                $variants
            );
        }

        return <<<PROMPT
Write {$variants} variants for natal aspect: {$nameA} {$aspectLabel} {$nameB}

Planets:
- {$nameA}: {$meaningA}
- {$nameB}: {$meaningB}

Aspect — {$aspectLabel}: {$dynamic}

{$toneHint}

Write about the lived experience: what this person naturally does or struggles with, how others perceive this quality in them, where it shows up in daily life. Include the shadow side (even for positive aspects) in at least one variant.

{$this->jsonTemplate($variants)}
PROMPT;
    }

    private function toneHint(string $aspect): string
    {
        return match($aspect) {
            'trine', 'sextile'         => 'Tone: positive — flowing, natural talent; explore both the gift and what gets taken for granted.',
            'square', 'opposition'     => 'Tone: challenging — tension that drives growth; show the friction and the transformation it produces.',
            'conjunction'              => 'Tone: neutral — intense fusion; the quality depends heavily on the specific planets involved.',
            'quincunx', 'semi_sextile' => 'Tone: neutral — subtle, requires constant adjustment; write about the restless fine-tuning.',
            default                    => 'Tone: neutral',
        };
    }

    private function parseAspectKey(string $key): array
    {
        $bodies  = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
        $aspects = array_keys($this->aspectLabels);

        foreach ($aspects as $asp) {
            $pattern = '_' . $asp . '_';
            if (str_contains($key, $pattern)) {
                [$rawA, $rawB] = explode($pattern, $key, 2);
                return [
                    'bodyA'  => $bodies[$rawA] ?? $rawA,
                    'bodyB'  => $bodies[$rawB] ?? $rawB,
                    'aspect' => $asp,
                ];
            }
        }

        return ['bodyA' => '?', 'bodyB' => '?', 'aspect' => '?'];
    }

    // ─── Natal synthesis ──────────────────────────────────────────────────────

    private function natalSynthesisPrompt(string $key, int $variants): string
    {
        // ascendant_in_aries
        if (str_starts_with($key, 'ascendant_in_')) {
            $sign        = str_replace('ascendant_in_', '', $key);
            $signLabel   = ucfirst($sign);
            $signMeaning = $this->signMeanings[$sign] ?? '';
            return $this->jsonPrompt(
                "Write {$variants} variants describing what it means to have {$signLabel} as the Ascendant (rising sign).\n\n"
                . "{$signLabel}: {$signMeaning}\n\n"
                . "The Ascendant is the mask and doorway — how you instinctively present yourself, your physical presence, first impressions, and the style through which you approach new situations. "
                . "It is not who you are deep down, but the energy you lead with.\n\n"
                . "Write about: how this person comes across to strangers, their instinctive style of moving through the world, physical mannerisms or presence, and the gap (if any) between first impression and inner reality.",
                $variants
            );
        }

        // dominant_earth / dominant_fire / dominant_water / dominant_air
        if (str_starts_with($key, 'dominant_')) {
            $element        = str_replace('dominant_', '', $key);
            $elementLabel   = ucfirst($element);
            $elementMeaning = $this->elementMeanings[$element] ?? '';
            return $this->jsonPrompt(
                "Write {$variants} variants describing a natal chart where {$elementLabel} is the dominant element.\n\n"
                . "{$elementLabel}: {$elementMeaning}\n\n"
                . "Dominant element means: most planets fall in {$elementLabel} signs — this element's qualities saturate the personality and colour nearly every area of life. "
                . "Write about the strengths this dominance brings, the blind spots or excesses it can create, and how others experience this person.",
                $variants
            );
        }

        // sun_in_aries_house_1
        if (preg_match('/^(\w+)_in_(\w+)_house_(\d+)$/', $key, $m)) {
            $bodies      = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
            $planet      = PlanetaryPosition::BODY_NAMES[$bodies[$m[1]] ?? -1] ?? ucfirst($m[1]);
            $sign        = $m[2];
            $signLabel   = ucfirst($sign);
            $houseNum    = (int) $m[3];
            $houseOrd    = $this->ordinals[$houseNum] ?? $m[3];
            $planetMean  = $this->planetMeanings[strtolower($planet)] ?? '';
            $signMean    = $this->signMeanings[$sign] ?? '';
            $houseMean   = $this->houseMeanings[$houseNum] ?? '';

            return $this->jsonPrompt(
                "Write {$variants} variants for {$planet} in {$signLabel} in the {$houseOrd} house.\n\n"
                . "{$planet}: {$planetMean}\n"
                . "{$signLabel} ({$signMean})\n"
                . "{$houseOrd} house: {$houseMean}\n\n"
                . "How these combine: the planet's energy is expressed through the sign's style and focused on the house's life domain. "
                . "Write about how this person experiences this area of life — their natural tendencies, gifts, and the challenges this placement brings. "
                . "Tone: neutral — describe both strengths and difficulties without judgment.",
                $variants
            );
        }

        return $this->jsonPrompt("Write {$variants} variants for: {$key}", $variants);
    }

    // ─── Singleton / Missing element ──────────────────────────────────────────

    private function singletonPrompt(string $key, int $variants, bool $short): string
    {
        $isSingleton    = str_starts_with($key, 'singleton_');
        $element        = str_replace(['singleton_', 'missing_'], '', $key);
        $elementLabel   = ucfirst($element);
        $elementMeaning = $this->elementMeanings[$element] ?? '';

        if ($short) {
            $instruction = $isSingleton
                ? "Write {$variants} one-sentence variants for: a person with only one planet in {$elementLabel} signs in their natal chart ({$elementMeaning})."
                : "Write {$variants} one-sentence variants for: a person with NO planets in {$elementLabel} signs in their natal chart — they compensate through effort and conscious development.";
            return $this->jsonPrompt($instruction, $variants);
        }

        if ($isSingleton) {
            return $this->jsonPrompt(
                "Write {$variants} variants for a natal chart with only ONE planet in {$elementLabel} signs.\n\n"
                . "{$elementLabel} element: {$elementMeaning}\n\n"
                . "What this means:\n"
                . "- This single planet carries the entire weight of {$elementLabel} energy for this person\n"
                . "- When that planet is strong or well-aspected, {$elementLabel} qualities emerge with unusual concentration and intensity\n"
                . "- When that planet is under pressure, stressed, or retrograde, the entire {$elementLabel} dimension of their life is affected at once\n"
                . "- The person tends to experience {$elementLabel} energy in concentrated bursts rather than as a steady, reliable flow\n"
                . "- This creates a distinctive pattern: either very effective in this element's domain, or suddenly without access to it\n\n"
                . "Write about the lived experience of this concentration. Use <strong>...</strong> to bold the single most important insight about this pattern.",
                $variants
            );
        }

        // Missing element
        return $this->jsonPrompt(
            "Write {$variants} variants for a natal chart with NO planets in {$elementLabel} signs.\n\n"
            . "{$elementLabel} element: {$elementMeaning}\n\n"
            . "What this means:\n"
            . "- These qualities do not arise naturally — they must be consciously built\n"
            . "- The person may feel a genuine absence in this area, or may overcompensate in specific ways\n"
            . "- They often develop compensatory strategies: relying on external structure, specific people, or deliberate practice\n"
            . "- This is not a deficiency — the effort required often produces unique discipline or insight in this domain\n"
            . "- The compensation pattern is typically more interesting and nuanced than the natural expression would be\n\n"
            . "Write about the compensatory strategies and lived experience. Name a specific compensation pattern in each variant. "
            . "Use <strong>...</strong> to bold the key compensatory insight.",
            $variants
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function jsonPrompt(string $instruction, int $variants): string
    {
        return $instruction . "\n\n" . $this->jsonTemplate($variants);
    }

    private function jsonTemplate(int $variants): string
    {
        $lines = '';
        for ($i = 1; $i <= $variants; $i++) {
            $lines .= "  {\"variant\": {$i}, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}";
            if ($i < $variants) $lines .= ",\n";
        }
        return "Return only a JSON array, no extra text:\n[\n{$lines}\n]";
    }

    // ─── Key builders ─────────────────────────────────────────────────────────

    private function buildKeys(string $section): array
    {
        return match($section) {
            'natal', 'natal_short'           => $this->natalAspectKeys(),
            'natal_synthesis'                => $this->natalSynthesisKeys(),
            'singleton', 'singleton_short'   => $this->singletonKeys(),
            default                          => $this->unsupportedSection($section),
        };
    }

    private function natalAspectKeys(): array
    {
        $bodies  = array_keys(PlanetaryPosition::BODY_NAMES);
        $aspects = array_keys($this->aspectLabels);
        $lilith  = 12;
        $keys    = [];

        foreach ($bodies as $a) {
            foreach ($bodies as $b) {
                if ($b <= $a) continue;
                foreach ($aspects as $asp) {
                    if (($a === $lilith || $b === $lilith) && $asp !== 'conjunction') continue;
                    $nameA  = strtolower(PlanetaryPosition::BODY_NAMES[$a]);
                    $nameB  = strtolower(PlanetaryPosition::BODY_NAMES[$b]);
                    $keys[] = "{$nameA}_{$asp}_{$nameB}";
                }
            }
        }

        return $keys;
    }

    private function natalSynthesisKeys(): array
    {
        $keys = [];

        foreach ($this->signNames as $sign) {
            $keys[] = 'ascendant_in_' . strtolower($sign);
        }

        foreach (['fire', 'earth', 'air', 'water'] as $element) {
            $keys[] = 'dominant_' . $element;
        }

        foreach (PlanetaryPosition::BODY_NAMES as $bodyId => $planet) {
            foreach ($this->signNames as $sign) {
                for ($house = 1; $house <= 12; $house++) {
                    $keys[] = strtolower($planet) . '_in_' . strtolower($sign) . '_house_' . $house;
                }
            }
        }

        return $keys;
    }

    private function singletonKeys(): array
    {
        $keys = [];
        foreach (['fire', 'earth', 'air', 'water'] as $element) {
            $keys[] = 'singleton_' . $element;
            $keys[] = 'missing_' . $element;
        }
        return $keys;
    }

    private function unsupportedSection(string $section): array
    {
        $supported = 'natal, natal_short, natal_synthesis, singleton, singleton_short';
        $this->error("Section '{$section}' not supported. Supported: {$supported}");
        return [];
    }
}
