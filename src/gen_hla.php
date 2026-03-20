<?php
/**
 * Continue generating natal_house_lord_aspects texts.
 * Skips existing keys — does NOT delete anything.
 * 12 houses × 10 lord planets × 7 aspects × 9 other planets = 7560 keys total.
 * Run: docker exec horo_php php /var/www/html/gen_hla.php
 */

require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;

$client    = new AnthropicClient(apiKey: config('services.anthropic.key'));
$model     = 'claude-haiku-4-5-20251001';
$batchSize = 8;

$planets = ['sun','moon','mercury','venus','mars','jupiter','saturn','uranus','neptune','pluto'];
$aspects = ['conjunction','opposition','trine','square','sextile','quincunx','semi_sextile'];

$planetLabels = [
    'sun'=>'Sun','moon'=>'Moon','mercury'=>'Mercury','venus'=>'Venus','mars'=>'Mars',
    'jupiter'=>'Jupiter','saturn'=>'Saturn','uranus'=>'Uranus','neptune'=>'Neptune','pluto'=>'Pluto',
];
$aspectLabels = [
    'conjunction'=>'conjunction','opposition'=>'opposition','trine'=>'trine',
    'square'=>'square','sextile'=>'sextile','quincunx'=>'quincunx (150°)','semi_sextile'=>'semi-sextile (30°)',
];
$toneHints = [
    'trine'=>'Tone: positive.',  'sextile'=>'Tone: positive.',
    'square'=>'Tone: challenging.', 'opposition'=>'Tone: challenging.',
    'conjunction'=>'Tone: neutral.',
    'quincunx'=>'Tone: neutral.',
    'semi_sextile'=>'Tone: neutral.',
];
$toneMap = [
    'positive'=>'positive','negative'=>'negative','neutral'=>'neutral',
    'challenging'=>'negative','difficult'=>'negative','mixed'=>'neutral',
];

$houseLabels = [
    1=>'1st House (Self & Identity)',2=>'2nd House (Money & Resources)',
    3=>'3rd House (Communication & Short Travel)',4=>'4th House (Home & Family)',
    5=>'5th House (Creativity & Romance)',6=>'6th House (Work & Health)',
    7=>'7th House (Partnerships)',8=>'8th House (Transformation & Shared Resources)',
    9=>'9th House (Philosophy & Long Travel)',10=>'10th House (Career & Public Life)',
    11=>'11th House (Friends & Aspirations)',12=>'12th House (Hidden Matters & Solitude)',
];

$prIn  = 0.80 / 1_000_000;
$prOut = 4.00 / 1_000_000;

// Build all 7560 keys
$allKeys = [];
for ($house = 1; $house <= 12; $house++) {
    foreach ($planets as $lord) {
        foreach ($aspects as $asp) {
            foreach ($planets as $other) {
                if ($lord === $other) continue;
                $allKeys[] = "house_{$house}_lord_{$lord}_{$asp}_{$other}";
            }
        }
    }
}

// Skip existing
$existing = TextBlock::where('section','natal_house_lord_aspects')->where('language','en')->whereNull('gender')->pluck('key')->toArray();
$existingMap = array_flip($existing);
$keys = array_values(array_filter($allKeys, fn($k) => !isset($existingMap[$k])));

echo count($existing) . " already exist, " . count($keys) . " to generate.\n";

$system = <<<PROMPT
You are writing natal chart house lord aspect descriptions for a horoscope application.

Each text describes what happens when the ruler (lord) of a specific house makes an aspect to another planet in the natal chart. This reveals how that life area (house) plays out based on the interaction between the house lord and another planet.

FULL version rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral)
- 3–4 sentences. Short, simple sentences — one idea per sentence, no dashes, no semicolons
- Plain everyday words only — no spiritual or psychological jargon
- Describe what the person actually does in real situations. Concrete behaviour only
- Do NOT start with "This aspect...", "With [planet]...", or "The lord of..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language
- MANDATORY HTML — you MUST include both tags in every text:
  * Wrap 1–3 key behavioural traits in <strong>...</strong>
  * Wrap EVERY planet name in <em>...</em> — every single occurrence, no exceptions
  Example: "You <strong>take your home life seriously</strong>. <em>Saturn</em> in this position makes you reliable but sometimes rigid."

SHORT version rules:
- Exactly 1 sentence — no more
- Write impersonally — no "you", no direct address
- Describe the key behavioural trait as a fact
- Plain everyday words only. Maximum 20 words
- NEVER mention planet names in short text — they are already in the UI label
- MANDATORY HTML: wrap the key trait in <strong>...</strong>
  Example: "<strong>Reliable and serious about domestic responsibilities,</strong> often taking on more than expected."
PROMPT;

$chunks    = array_chunk($keys, $batchSize);
$done      = 0;
$total     = count($keys);
$totalCost = 0.0;

foreach ($chunks as $chunk) {
    $lines = [];
    foreach ($chunk as $key) {
        // Parse: house_{N}_lord_{lord}_{asp}_{other}
        if (!preg_match('/^house_(\d+)_lord_(\w+?)_(conjunction|opposition|trine|square|sextile|quincunx|semi_sextile)_(\w+)$/', $key, $m)) {
            echo "  Skip bad key: {$key}\n"; continue;
        }
        [,$houseNum,$lord,$asp,$other] = $m;
        $houseLabel = $houseLabels[(int)$houseNum] ?? "House {$houseNum}";
        $lordLabel  = $planetLabels[$lord]  ?? ucfirst($lord);
        $otherLabel = $planetLabels[$other] ?? ucfirst($other);
        $aspLabel   = $aspectLabels[$asp]   ?? $asp;
        $toneHint   = $toneHints[$asp]      ?? 'Tone: neutral.';
        $lines[] = "\"{$key}\": {$lordLabel} (lord of {$houseLabel}) {$aspLabel} {$otherLabel}. {$toneHint}";
    }

    if (empty($lines)) continue;

    $htmlNote = 'IMPORTANT: HTML is REQUIRED in every text. "full" texts MUST have <em>PlanetName</em> and <strong>key trait</strong>. "short" texts MUST have <strong>key trait</strong>.';
    $prompt = "Generate texts for these house lord aspects. For EACH key return both \"full\" (paragraph) and \"short\" (1 sentence).\n\n{$htmlNote}\n\nAspects:\n" . implode("\n", $lines) . "\n\nReturn ONLY a valid JSON object:\n{\n  \"key_name\": {\n    \"full\":  [{\"variant\": 1, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}],\n    \"short\": [{\"variant\": 1, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}]\n  }\n}";

    try {
        $response = $client->messages->create(
            maxTokens:   min(8192, 600 * count($chunk)),
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
                    ['key'=>$key,'section'=>'natal_house_lord_aspects','language'=>'en','variant'=>$block['variant'],'gender'=>null],
                    ['text'=>$block['text'],'tone'=>$tone,
                     'tokens_in'=>(int)round($in/count($chunk)),'tokens_out'=>(int)round($out/count($chunk)),
                     'cost_usd'=>round($cost/count($chunk),8)]
                );
            }
            foreach ($entry['short'] ?? [] as $block) {
                $tone = $toneMap[strtolower($block['tone'] ?? 'neutral')] ?? 'neutral';
                TextBlock::updateOrCreate(
                    ['key'=>$key,'section'=>'natal_house_lord_aspects_short','language'=>'en','variant'=>$block['variant'],'gender'=>null],
                    ['text'=>$block['text'],'tone'=>$tone,
                     'tokens_in'=>(int)round($in/count($chunk)),'tokens_out'=>(int)round($out/count($chunk)),
                     'cost_usd'=>round($cost/count($chunk),8)]
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
