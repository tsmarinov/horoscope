<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored lunar calendar text blocks — variant 1.
 *
 * Sections:
 *   lunar_day        — Moon transiting a sign (~2 days), 2 sentences, impersonal
 *   lunar_day_short  — same, 1 sentence (simplified / haiku mode)
 *   lunation_sign        — New/Full Moon in sign tagline, ≤10 words, plain text
 *   lunation_sign_short  — same, 2–4 words
 *
 * Style rules (lunar_day):
 *   - Impersonal — no "you/your"
 *   - No sign or planet names in text (shown in header)
 *   - HTML: <strong> for key behavioural trait; <em> for planet/sign names
 *   - Exactly 2 sentences (short: 1)
 *   - Varied sentence openings
 *   - Forbidden: journey, path, soul, essence, force, pull, tension, dance, dissolves
 */
class LunarTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'language'   => 'en',
            'variant'    => 1,
            'tone'       => 'neutral',
            'tokens_in'  => 0,
            'tokens_out' => 0,
            'cost_usd'   => 0.0,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $this->blocks());

        // Insert in chunks; skip duplicates (key+section+language+variant are unique)
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('text_blocks')->upsert(
                $chunk,
                ['key', 'section', 'language', 'variant'],
                ['text', 'tone', 'updated_at']
            );
        }

        $this->command->info('Lunar texts seeded — ' . count($rows) . ' blocks.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function blocks(): array
    {
        return [

            // ── lunar_day — Moon in sign, 2 sentences, impersonal ─────────────

            ['section' => 'lunar_day', 'key' => 'moon_in_aries', 'text' =>
                '<strong>Impatience with delays</strong> rises noticeably right now, and people act on whatever they want without much advance thinking. '
                . 'Short tempers and blunt reactions are more common for the next 48 hours.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_taurus', 'text' =>
                '<strong>The pace slows considerably</strong> during this transit as comfort and familiar surroundings take priority over efficiency. '
                . 'Resistance to change hardens, and any disruption to routine tends to provoke stubborn pushback.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_gemini', 'text' =>
                '<strong>Social activity picks up</strong> for the next day or two, with conversations starting easily but rarely staying on one topic. '
                . 'A general restlessness makes it harder to finish tasks or sit with one idea long enough to go deep.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_cancer', 'text' =>
                'Sensitivity to tone and atmosphere runs higher than usual over the next 48 hours, making small remarks feel more significant than intended. '
                . '<strong>The draw toward home, familiar food, and trusted people</strong> dominates most decisions.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_leo', 'text' =>
                '<strong>The social atmosphere becomes more expressive</strong> these days, with people more willing to share opinions and put themselves forward. '
                . 'The need to feel appreciated surfaces in small ways — a slower response to criticism, a stronger preference for being noticed.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_virgo', 'text' =>
                'Attention sharpens on what is not working right now — small errors and inefficiencies become harder to ignore than usual. '
                . '<strong>The tendency to organize, correct, and refine</strong> runs stronger for the next day or two, sometimes at the cost of the bigger picture.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_libra', 'text' =>
                '<strong>The social atmosphere becomes more diplomatic</strong> during this transit, with more care taken to avoid unnecessary friction. '
                . 'Decisions slow down as multiple perspectives get weighed, and outright conflict feels harder to initiate than usual.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_scorpio', 'text' =>
                '<strong>Psychological intensity rises</strong> for the next 48 hours, and surface-level talk becomes noticeably less satisfying. '
                . 'People read into what others leave unsaid more than usual, and private matters feel more pressing.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_sagittarius', 'text' =>
                '<strong>Restlessness with routine increases</strong> during this transit, and the desire to break out of familiar patterns grows. '
                . 'Opinions get expressed more freely and with less filtering for the next day or two.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_capricorn', 'text' =>
                '<strong>Focus narrows toward responsibilities</strong> and unfinished obligations during this transit, with less tolerance for distraction or small talk. '
                . 'The emotional tone becomes more reserved and measured for the next 48 hours.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_aquarius', 'text' =>
                '<strong>Emotional detachment increases</strong> these days, making abstract discussions and group concerns feel more engaging than personal ones. '
                . 'People respond better to logic than to feeling right now, and the need for independence runs noticeably higher.'],

            ['section' => 'lunar_day', 'key' => 'moon_in_pisces', 'text' =>
                '<strong>The boundary between personal and environmental moods blurs</strong> during this transit, making it easy to absorb the emotional state of whoever is nearby. '
                . 'Imagination and sensitivity both run higher, while clarity of thought and decision-making run lower for the next 48 hours.'],

            // ── lunar_day_short — 1 sentence ─────────────────────────────────

            ['section' => 'lunar_day_short', 'key' => 'moon_in_aries', 'text' =>
                '<strong>Impatience and directness</strong> dominate for the next 48 hours, with people acting quickly and tolerating delays poorly.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_taurus', 'text' =>
                '<strong>A slower, comfort-oriented pace</strong> sets in during this transit, with strong resistance to anything that disrupts routine.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_gemini', 'text' =>
                '<strong>Restless communication</strong> picks up over the next day or two — lots of starting conversations, less finishing of tasks.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_cancer', 'text' =>
                'Emotional sensitivity runs noticeably higher for the next 48 hours, with attention drawn toward <strong>familiar people and home</strong>.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_leo', 'text' =>
                '<strong>The need to be recognized</strong> surfaces more visibly these days, making people more expressive and quicker to react to being overlooked.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_virgo', 'text' =>
                '<strong>Critical attention to detail</strong> sharpens during this transit, bringing a stronger-than-usual focus on fixing what is not working.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_libra', 'text' =>
                '<strong>Diplomatic instincts strengthen</strong> over the next day or two, with a clear preference for avoiding confrontation and finding common ground.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_scorpio', 'text' =>
                '<strong>Psychological intensity</strong> is elevated for the next 48 hours, with more attention on hidden motives and what is left unsaid.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_sagittarius', 'text' =>
                '<strong>Optimism and restlessness</strong> combine over the next day or two, pushing people toward bigger ideas and away from daily routine.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_capricorn', 'text' =>
                '<strong>Discipline and seriousness</strong> take hold during this transit, with attention moving firmly toward obligations and practical outcomes.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_aquarius', 'text' =>
                '<strong>Social detachment</strong> rises for the next 48 hours, with ideas and group dynamics taking priority over personal emotional concerns.'],

            ['section' => 'lunar_day_short', 'key' => 'moon_in_pisces', 'text' =>
                '<strong>Sensitivity and imagination</strong> run higher than usual during this transit, making emotional boundaries harder to maintain.'],

            // ── lunation_sign — New Moon taglines ≤10 words, plain text ───────

            ['section' => 'lunation_sign', 'key' => 'new_moon_in_aries',       'text' => 'fresh start, personal initiative, bold new direction'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_taurus',      'text' => 'material foundations, slow build, stability'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_gemini',      'text' => 'new ideas, curiosity, short-term connections'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_cancer',      'text' => 'emotional reset, home, inner security'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_leo',         'text' => 'creative spark, self-expression, new confidence'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_virgo',       'text' => 'practical renewal, health habits, daily routines'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_libra',       'text' => 'relationship reset, balance, new partnerships'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_scorpio',     'text' => 'deep intentions, transformation, inner power'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_sagittarius', 'text' => 'new beliefs, expansion, broader horizons'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_capricorn',   'text' => 'long-term goals, ambition, structural reset'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_aquarius',    'text' => 'innovation, social ideals, future direction'],
            ['section' => 'lunation_sign', 'key' => 'new_moon_in_pisces',      'text' => 'intuitive reset, release, spiritual renewal'],

            // ── lunation_sign — Full Moon taglines ────────────────────────────

            ['section' => 'lunation_sign', 'key' => 'full_moon_in_aries',       'text' => 'confrontation, personal peak, energy released'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_taurus',      'text' => 'material results, values tested, comfort vs change'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_gemini',      'text' => 'information peak, scattered focus, mental overload'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_cancer',      'text' => 'emotional culmination, family matters, inner needs surface'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_leo',         'text' => 'recognition, drama, creative culmination'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_virgo',       'text' => 'work results, health review, critical peak'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_libra',       'text' => 'relationship peak, fairness, decision point'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_scorpio',     'text' => 'hidden truths, emotional intensity, power shift'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_sagittarius', 'text' => 'beliefs tested, freedom vs commitment, peak optimism'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_capricorn',   'text' => 'career results, ambition tested, authority reviewed'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_aquarius',    'text' => 'social awakening, group dynamics, collective peak'],
            ['section' => 'lunation_sign', 'key' => 'full_moon_in_pisces',      'text' => 'emotional release, endings, heightened sensitivity'],

            // ── lunation_sign_short — 2–4 words ──────────────────────────────

            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_aries',       'text' => 'bold fresh start'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_taurus',      'text' => 'stable foundations'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_gemini',      'text' => 'new ideas, curiosity'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_cancer',      'text' => 'emotional new beginning'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_leo',         'text' => 'creative confidence'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_virgo',       'text' => 'practical daily reset'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_libra',       'text' => 'new relationship balance'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_scorpio',     'text' => 'deep inner reset'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_sagittarius', 'text' => 'new horizons open'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_capricorn',   'text' => 'structured ambition reset'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_aquarius',    'text' => 'innovative future vision'],
            ['section' => 'lunation_sign_short', 'key' => 'new_moon_in_pisces',      'text' => 'inner intuitive renewal'],

            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_aries',       'text' => 'personal energy peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_taurus',      'text' => 'material values peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_gemini',      'text' => 'mental information peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_cancer',      'text' => 'emotional family peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_leo',         'text' => 'creative recognition peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_virgo',       'text' => 'health and work peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_libra',       'text' => 'relationship decision peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_scorpio',     'text' => 'deep truth revealed'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_sagittarius', 'text' => 'belief freedom peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_capricorn',   'text' => 'career ambition peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_aquarius',    'text' => 'social collective peak'],
            ['section' => 'lunation_sign_short', 'key' => 'full_moon_in_pisces',      'text' => 'emotional release peak'],

        ];
    }
}
