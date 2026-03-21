<?php

namespace App\Services;

use Anthropic\Client as AnthropicClient;
use App\Models\PlanetaryPosition;
use App\Models\SunSignHoroscope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SunSignHoroscopeService
{
    public const SIGNS = [
        'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo',
        'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces',
    ];

    private const MODEL = 'claude-haiku-4-5-20251001';

    /** Midpoint of each sign (15° into sign = representative Sun position) */
    private const SIGN_SUN = [
        'aries' => 15, 'taurus' => 45, 'gemini' => 75, 'cancer' => 105,
        'leo' => 135, 'virgo' => 165, 'libra' => 195, 'scorpio' => 225,
        'sagittarius' => 255, 'capricorn' => 285, 'aquarius' => 315, 'pisces' => 345,
    ];

    private const PLANET_NAMES = [
        0 => 'Sun', 1 => 'Moon', 2 => 'Mercury', 3 => 'Venus', 4 => 'Mars',
        5 => 'Jupiter', 6 => 'Saturn', 7 => 'Uranus', 8 => 'Neptune', 9 => 'Pluto',
        11 => 'North Node', 15 => 'Chiron',
    ];

    private const SIGN_NAMES = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    private const ASPECTS = [
        'conjunction' => ['angle' => 0,   'orb' => 8],
        'sextile'     => ['angle' => 60,  'orb' => 6],
        'square'      => ['angle' => 90,  'orb' => 7],
        'trine'       => ['angle' => 120, 'orb' => 7],
        'opposition'  => ['angle' => 180, 'orb' => 8],
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Return horoscopes for all 12 signs for a given date.
     * Reads from DB; generates (with real transit aspects) only what's missing.
     */
    /** Read-only: returns whatever is in DB, never calls the AI. */
    public function getForDate(Carbon $date): array
    {
        $existing = SunSignHoroscope::whereDate('date', $date->toDateString())
            ->get()->keyBy('sign')->map(fn ($r) => $r->body)->toArray();

        $ordered = [];
        foreach (self::SIGNS as $sign) {
            $ordered[$sign] = $existing[$sign] ?? '';
        }
        return $ordered;
    }

    /** Force-regenerate all 12 signs for a date, overwriting DB. */
    public function regenerateForDate(Carbon $date): array
    {
        $dateStr   = $date->toDateString();
        $transits  = $this->loadTransits($date);
        $generated = $this->generateSigns(self::SIGNS, $date, $transits);

        foreach ($generated as $sign => $body) {
            SunSignHoroscope::updateOrCreate(
                ['sign' => $sign, 'date' => $dateStr],
                ['body' => $body]
            );
        }

        return $generated;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function loadTransits(Carbon $date): array
    {
        return PlanetaryPosition::whereDate('date', $date->toDateString())
            ->get()
            ->keyBy('body')
            ->map(fn ($p) => [
                'longitude'    => (float) $p->longitude,
                'is_retrograde' => (bool) $p->is_retrograde,
            ])
            ->toArray();
    }

    private function signName(float $longitude): string
    {
        $idx = (int) floor(fmod($longitude + 360, 360) / 30);
        return self::SIGN_NAMES[$idx] ?? '';
    }

    private function angleDiff(float $a, float $b): float
    {
        $d = abs(fmod($a + 360, 360) - fmod($b + 360, 360));
        return $d > 180 ? 360 - $d : $d;
    }

    /**
     * Build a concise list of transit aspects to the sign's Sun position.
     * Returns a one-line summary for the AI prompt.
     */
    private function buildTransitContext(string $sign, array $transits): string
    {
        $sunLon  = self::SIGN_SUN[$sign];
        $results = [];

        foreach ($transits as $body => $t) {
            $name = self::PLANET_NAMES[$body] ?? null;
            if (!$name) continue;

            $diff = $this->angleDiff($sunLon, $t['longitude']);

            foreach (self::ASPECTS as $aspName => $asp) {
                $orb = abs($diff - $asp['angle']);
                if ($orb <= $asp['orb']) {
                    $rx       = $t['is_retrograde'] ? ' Rx' : '';
                    $inSign   = $this->signName($t['longitude']);
                    $results[] = sprintf('%s %s (orb %.1f°, in %s%s)', $name, $aspName, $orb, $inSign, $rx);
                    break;
                }
            }
        }

        return empty($results)
            ? 'no major transit aspects to the Sun today'
            : implode('; ', $results);
    }

    private function generateSigns(array $signs, Carbon $date, array $transits): array
    {
        $dateStr = $date->format('F j, Y');

        // Build per-sign context lines
        $contextLines = [];
        foreach ($signs as $sign) {
            $sunDeg = self::SIGN_SUN[$sign] % 30;
            $aspects = $this->buildTransitContext($sign, $transits);
            $contextLines[] = sprintf('  %s (Sun ☉ %d° %s): %s',
                ucfirst($sign), $sunDeg, ucfirst($sign), $aspects);
        }
        $contextBlock = implode("\n", $contextLines);

        $template = json_encode(array_fill_keys($signs, '...'), JSON_PRETTY_PRINT);

        $system = <<<EOT
You are a skilled astrologer writing daily sun-sign horoscopes for social media.

Style guidelines:
- Mix second person ("you", "your") with third-person narrative ("those who...", "people born under this sign...", "native of this sign...")
- Vary sentence structure — short punchy sentences, then longer descriptive ones
- Mention specific life scenarios: relationships, work, money, health, travel, decisions
- Reference the planets from the transit data and what they bring (Jupiter = expansion/luck, Saturn = discipline/delay, Mars = energy/conflict, Venus = harmony/pleasure, Mercury = communication/travel, Moon = emotions/intuition)
- Do NOT start consecutive sentences with "You" or "Your"
- Use HTML formatting throughout: <strong> on 3–5 key words or short phrases per horoscope (emotions, actions, outcomes — not just nouns), <em> on every planet and sign name
- Make <strong> feel natural — bold the words that carry the most weight or surprise in each sentence
- 5–6 sentences total
EOT;

        $prompt = <<<EOT
Write daily sun-sign horoscopes for {$dateStr}.

Astrological basis — active transit aspects to each sign's natal Sun today:
{$contextBlock}

Use these aspects as the foundation for each horoscope. Be specific to the planets involved.
5–6 sentences. Format rules — apply to every horoscope:
- <strong> on 3–5 impactful words or short phrases (not just the planet name — bold the feeling, the action, the result)
- <em> on every planet name and every sign name

Respond ONLY with valid JSON — no markdown fences, no extra text:
{$template}
EOT;

        try {
            $client   = new AnthropicClient(apiKey: config('services.anthropic.key'));
            $response = $client->messages->create(
                maxTokens: 4000,
                messages:  [['role' => 'user', 'content' => $prompt]],
                model:     self::MODEL,
                system:    $system,
                temperature: 1.0,
            );

            $text = $response->content[0]->text ?? '';

            if (preg_match('/\{[\s\S]+\}/u', $text, $m)) {
                $data = json_decode($m[0], true);
                if (is_array($data)) {
                    return array_intersect_key($data, array_flip($signs));
                }
            }

            Log::warning('SunSignHoroscopeService: unexpected JSON', ['text' => substr($text, 0, 300)]);
        } catch (\Throwable $e) {
            Log::error('SunSignHoroscopeService: ' . $e->getMessage());
        }

        return [];
    }
}
