<?php
/**
 * Regenerate natal_angles texts WITH proper HTML formatting.
 * Deletes existing and regenerates all 140 keys (10 planets × 7 aspects × 2 angles).
 * Run: docker exec horo_php php /var/www/html/gen_angles2.php
 */

require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;

$client    = new AnthropicClient(apiKey: config('services.anthropic.key'));
$model     = 'claude-haiku-4-5-20251001';
$batchSize = 10;

$planets = ['sun','moon','mercury','venus','mars','jupiter','saturn','uranus','neptune','pluto'];
$aspects = ['conjunction','opposition','trine','square','sextile','quincunx','semi_sextile'];
$angles  = ['asc','mc'];

$planetLabels = [
    'sun'=>'Sun','moon'=>'Moon','mercury'=>'Mercury','venus'=>'Venus','mars'=>'Mars',
    'jupiter'=>'Jupiter','saturn'=>'Saturn','uranus'=>'Uranus','neptune'=>'Neptune','pluto'=>'Pluto',
];
$aspectLabels = [
    'conjunction'=>'conjunction','opposition'=>'opposition','trine'=>'trine',
    'square'=>'square','sextile'=>'sextile','quincunx'=>'quincunx (150°)','semi_sextile'=>'semi-sextile (30°)',
];
$angleLabels = [
    'asc' => 'Ascendant (self-image, identity, and physical appearance)',
    'mc'  => 'Midheaven (career, public reputation, and life ambitions)',
];
$toneHints = [
    'trine'=>'Tone: positive.',  'sextile'=>'Tone: positive.',
    'square'=>'Tone: challenging.', 'opposition'=>'Tone: challenging.',
    'conjunction'=>'Tone: neutral (intensity depends on planets).',
    'quincunx'=>'Tone: neutral (subtle adjustment needed).',
    'semi_sextile'=>'Tone: neutral (subtle adjustment needed).',
];
$toneMap = [
    'positive'   => 'positive',
    'negative'   => 'negative',
    'neutral'    => 'neutral',
    'challenging'=> 'negative',
    'difficult'  => 'negative',
    'mixed'      => 'neutral',
];

$prIn  = 0.80 / 1_000_000;
$prOut = 4.00 / 1_000_000;

// Build all keys
$allKeys = [];
foreach ($planets as $planet) {
    foreach ($aspects as $asp) {
        foreach ($angles as $angle) {
            $allKeys[] = "{$planet}_{$asp}_{$angle}";
        }
    }
}

// Delete existing and regenerate all
echo "Deleting existing natal_angles and natal_angles_short texts...\n";
$deleted = TextBlock::whereIn('section', ['natal_angles','natal_angles_short'])->delete();
echo "Deleted {$deleted} rows. Regenerating all " . count($allKeys) . " keys...\n";

$system = <<<PROMPT
You are writing natal chart angle aspect descriptions for a horoscope application.

Each key requires TWO versions: a full paragraph ("full") and a one-sentence summary ("short").

The two angles are:
- ASC (Ascendant): self-image, physical appearance, first impressions, personal identity.
- MC (Midheaven): career direction, public reputation, achievements, life ambitions.

FULL version rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral)
- 3–4 sentences. Short, simple sentences — one idea per sentence, no dashes, no semicolons
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- Describe what the person actually does in real situations. Concrete behaviour only
- Do NOT start with "This aspect...", "With [planet]...", or "[Planet] [aspect] [angle] means..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language
- MANDATORY HTML — you MUST include both tags in every full text:
  * Wrap 1–3 key behavioural traits in <strong>...</strong>
  * Wrap EVERY planet name in <em>...</em> — every single one, no exceptions
  Example: "You tend to <strong>take charge in social situations</strong>. <em>Mars</em> gives you a direct manner that others notice immediately."

SHORT version rules:
- Exactly 1 sentence — no more
- Write impersonally — no "you", no "your", no direct address
- Describe the key behavioural trait as a fact
- Plain everyday words only
- Maximum 20 words. Cut every unnecessary word
- NEVER mention planet names in the text — they are already shown in the UI label
- MANDATORY HTML: wrap the key trait in <strong>...</strong> only
  Example: "<strong>Strong public presence</strong> makes this person stand out in professional settings."
PROMPT;

$chunks    = array_chunk($allKeys, $batchSize);
$done      = 0;
$total     = count($allKeys);
$totalCost = 0.0;

foreach ($chunks as $chunk) {
    $lines = [];
    foreach ($chunk as $key) {
        // Parse planet, aspect, angle from key
        // Keys: planet_aspect_asc or planet_aspect_mc
        // Handle semi_sextile: planet_semi_sextile_asc/mc
        if (str_ends_with($key, '_asc')) {
            $angle = 'asc';
            $remainder = substr($key, 0, -4); // remove _asc
        } else {
            $angle = 'mc';
            $remainder = substr($key, 0, -3); // remove _mc
        }
        // remainder = planet_aspect
        $underscorePos = strpos($remainder, '_');
        $planet = substr($remainder, 0, $underscorePos);
        $asp    = substr($remainder, $underscorePos + 1);

        $pLabel   = $planetLabels[$planet] ?? ucfirst($planet);
        $aspLabel = $aspectLabels[$asp]    ?? $asp;
        $angLabel = $angleLabels[$angle]   ?? $angle;
        $toneHint = $toneHints[$asp]       ?? 'Tone: neutral.';
        $lines[]  = "\"{$key}\": {$pLabel} {$aspLabel} {$angLabel}. {$toneHint}";
    }

    $htmlExample = 'IMPORTANT: HTML is REQUIRED. Every "full" text MUST contain <em>PlanetName</em> and <strong>key trait</strong>. Every "short" text MUST contain <strong>key trait</strong>. Texts without these tags are WRONG.';

    $prompt = "Generate texts for these angle aspects. For EACH key return both \"full\" (paragraph) and \"short\" (1 sentence).\n\n{$htmlExample}\n\nAspects:\n" . implode("\n", $lines) . "\n\nReturn ONLY a valid JSON object:\n{\n  \"key_name\": {\n    \"full\":  [{\"variant\": 1, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}],\n    \"short\": [{\"variant\": 1, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}]\n  }\n}";

    try {
        $response = $client->messages->create(
            maxTokens:   min(8192, 700 * count($chunk)),
            messages:    [['role' => 'user', 'content' => $prompt]],
            model:       $model,
            system:      $system,
            temperature: 1.0,
        );

        $raw  = $response->content[0]->text ?? '';
        $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $json = preg_replace('/```\s*$/m', '', $json);
        $result = json_decode($json, true);

        if (!is_array($result)) {
            echo "  Invalid JSON for chunk starting at {$chunk[0]}\n";
            continue;
        }

        $in   = $response->usage->inputTokens  ?? 0;
        $out  = $response->usage->outputTokens ?? 0;
        $cost = $in * $prIn + $out * $prOut;
        $totalCost += $cost;
        $saved = 0;

        foreach ($chunk as $key) {
            $entry = $result[$key] ?? null;
            if (!is_array($entry)) { echo "  Missing: {$key}\n"; continue; }

            foreach ($entry['full'] ?? [] as $block) {
                $tone = $toneMap[strtolower($block['tone'] ?? 'neutral')] ?? 'neutral';
                TextBlock::updateOrCreate(
                    ['key' => $key, 'section' => 'natal_angles', 'language' => 'en', 'variant' => $block['variant'], 'gender' => null],
                    ['text' => $block['text'], 'tone' => $tone,
                     'tokens_in' => (int)round($in/count($chunk)), 'tokens_out' => (int)round($out/count($chunk)),
                     'cost_usd' => round($cost/count($chunk), 8)]
                );
            }
            foreach ($entry['short'] ?? [] as $block) {
                $tone = $toneMap[strtolower($block['tone'] ?? 'neutral')] ?? 'neutral';
                TextBlock::updateOrCreate(
                    ['key' => $key, 'section' => 'natal_angles_short', 'language' => 'en', 'variant' => $block['variant'], 'gender' => null],
                    ['text' => $block['text'], 'tone' => $tone,
                     'tokens_in' => (int)round($in/count($chunk)), 'tokens_out' => (int)round($out/count($chunk)),
                     'cost_usd' => round($cost/count($chunk), 8)]
                );
            }
            $saved++;
        }
        $done += $saved;
        echo sprintf('[%d/%d] saved %d | $%.4f | total $%.4f', $done, $total, $saved, $cost, $totalCost) . "\n";

    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo sprintf("Done. Total cost: $%.4f\n", $totalCost);
