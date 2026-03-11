<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored daily tip short text blocks — 1 sentence each.
 *
 * Section: daily_tip_short
 * Key:     {weekday}_moon_in_{sign}  (84 keys = 7 days × 12 signs)
 * Style:   1 sentence, "you/your", weekday ruler energy × Moon sign
 */
class DailyTipShortSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'daily_tip_short',
            'language'   => 'en',
            'variant'    => 1,
            'tone'       => 'neutral',
            'tokens_in'  => 0,
            'tokens_out' => 0,
            'cost_usd'   => 0.0,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $this->blocks());

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('text_blocks')->upsert(
                $chunk,
                ['key', 'section', 'language', 'variant'],
                ['text', 'tone', 'updated_at']
            );
        }

        $this->command->info('DailyTipShortSeeder: ' . count($rows) . ' blocks seeded.');
    }

    private function blocks(): array
    {
        return [
            // ── Monday (Moon) ─────────────────────────────────────────────
            ['key' => 'monday_moon_in_aries',     'text' => 'Act on gut feelings for short tasks today, but watch for irritability toward people close to you.'],
            ['key' => 'monday_moon_in_taurus',    'text' => 'Monday settles comfortably into Taurus — lean into home care and slow, tangible tasks rather than disrupting your routine.'],
            ['key' => 'monday_moon_in_gemini',    'text' => 'Short conversations work well today, but avoid making emotional decisions amid scattered energy.'],
            ['key' => 'monday_moon_in_cancer',    'text' => 'The Moon rules both the day and the sign — lean into home, close relationships, and self-care for peak intuition.'],
            ['key' => 'monday_moon_in_leo',       'text' => 'Small creative acts or genuine appreciation from someone close will satisfy both Monday\'s reflective tone and Leo\'s need for expression.'],
            ['key' => 'monday_moon_in_virgo',     'text' => 'Direct your attention to health routines or careful tasks — emotional satisfaction today comes from useful, purposeful action.'],
            ['key' => 'monday_moon_in_libra',     'text' => 'Relational sensitivity peaks today — gentle honesty in conversations works better than sitting with unresolved tension.'],
            ['key' => 'monday_moon_in_scorpio',   'text' => 'Honor the need for quiet and honest reflection today rather than pushing through social obligations.'],
            ['key' => 'monday_moon_in_sagittarius', 'text' => 'A short walk or change of scenery helps discharge the tension between Monday\'s reflective pull and Sagittarius\'s restlessness.'],
            ['key' => 'monday_moon_in_capricorn', 'text' => 'Tackle domestic responsibilities or quiet administrative tasks — completing something concrete reduces Monday\'s emotional weight.'],
            ['key' => 'monday_moon_in_aquarius',  'text' => 'Emotional detachment rises today — let logic rather than mood guide any decisions.'],
            ['key' => 'monday_moon_in_pisces',    'text' => 'Protect your environment carefully today — you may absorb the moods of whoever is nearby without realizing it.'],

            // ── Tuesday (Mars) ────────────────────────────────────────────
            ['key' => 'tuesday_moon_in_aries',     'text' => 'Mars rules the day and Aries hosts the Moon — channel the sharp energy into physical effort or decisive action.'],
            ['key' => 'tuesday_moon_in_taurus',    'text' => 'Tasks requiring sustained physical effort work well today — avoid forcing decisions that need more time to settle.'],
            ['key' => 'tuesday_moon_in_gemini',    'text' => 'Best for short, varied tasks and direct conversations — long-term planning is harder when attention keeps splitting.'],
            ['key' => 'tuesday_moon_in_cancer',    'text' => 'Protect what matters to you today rather than going on offense — defensiveness is natural but unnecessary conflict is easily avoided.'],
            ['key' => 'tuesday_moon_in_leo',       'text' => 'Put yourself forward in situations that call for leadership or initiative — Mars and Leo together reward courage.'],
            ['key' => 'tuesday_moon_in_virgo',     'text' => 'Excellent for work requiring focus or physical skill — avoid harsh criticism, as the analytical energy tips into sharpness easily.'],
            ['key' => 'tuesday_moon_in_libra',     'text' => 'Choose your battles carefully today — not every conflict is worth the energy it would cost.'],
            ['key' => 'tuesday_moon_in_scorpio',   'text' => 'Excellent for research or resolving something avoided — determination is strong but so is the risk of fixation.'],
            ['key' => 'tuesday_moon_in_sagittarius', 'text' => 'Good for physical activity or tackling something postponed — directness works better than diplomacy today.'],
            ['key' => 'tuesday_moon_in_capricorn', 'text' => 'Tackle demanding, high-effort tasks with confidence — ambition and stamina are both fully available today.'],
            ['key' => 'tuesday_moon_in_aquarius',  'text' => 'Work on something that benefits others as much as yourself and momentum follows more easily today.'],
            ['key' => 'tuesday_moon_in_pisces',    'text' => 'Quiet determination works better than force today — artistic or compassionate actions are more effective than aggressive ones.'],

            // ── Wednesday (Mercury) ───────────────────────────────────────
            ['key' => 'wednesday_moon_in_aries',     'text' => 'Words come fast and conclusions form quickly — good for brainstorming, but avoid sending messages written in anger.'],
            ['key' => 'wednesday_moon_in_taurus',    'text' => 'Thinking is more careful and deliberate than usual — good for reviewing or any decision that benefits from patience over speed.'],
            ['key' => 'wednesday_moon_in_gemini',    'text' => 'Mercury rules the day and Gemini amplifies it — write, negotiate, or learn, but be aware follow-through may lag behind starting.'],
            ['key' => 'wednesday_moon_in_cancer',    'text' => 'Intuitive communication works especially well today — good for conversations where empathy matters more than data.'],
            ['key' => 'wednesday_moon_in_leo',       'text' => 'Ideas come out more boldly and persuasively than usual — good for presentations or any situation where confident delivery matters.'],
            ['key' => 'wednesday_moon_in_virgo',     'text' => 'Mercury rules the day and Virgo sharpens analysis further — excellent for editing or detail work, but avoid over-analysis.'],
            ['key' => 'wednesday_moon_in_libra',     'text' => 'Perfect for negotiations or conversations requiring tact — decisions may slow as multiple perspectives keep presenting themselves.'],
            ['key' => 'wednesday_moon_in_scorpio',   'text' => 'Words carry more weight than usual today — excellent for research or honest conversations, as others are reading between the lines.'],
            ['key' => 'wednesday_moon_in_sagittarius', 'text' => 'Excellent for teaching or planning on a larger scale — watch for overpromising or glossing over details in favor of broad strokes.'],
            ['key' => 'wednesday_moon_in_capricorn', 'text' => 'Good for business writing or structured planning — communications today tend toward the serious and purposeful.'],
            ['key' => 'wednesday_moon_in_aquarius',  'text' => 'Strong day for innovative ideas or group discussions — personal and emotional topics may feel less accessible than usual.'],
            ['key' => 'wednesday_moon_in_pisces',    'text' => 'Intuition and imagination are stronger than logical precision today — good for creative writing, but avoid important contracts.'],

            // ── Thursday (Jupiter) ────────────────────────────────────────
            ['key' => 'thursday_moon_in_aries',     'text' => 'Confidence and initiative are both strong today — act on a plan you have been holding back.'],
            ['key' => 'thursday_moon_in_taurus',    'text' => 'Good for financial planning or quality time with people you value — patience and consistency are rewarded today.'],
            ['key' => 'thursday_moon_in_gemini',    'text' => 'Excellent for learning or gathering information — just avoid overcommitting to too many ideas without a concrete next step.'],
            ['key' => 'thursday_moon_in_cancer',    'text' => 'A strongly nurturing combination — good for family gatherings or any context where emotional support is genuinely needed.'],
            ['key' => 'thursday_moon_in_leo',       'text' => 'One of the most expressive and optimistic combinations of the week — excellent for leadership, celebrations, or public-facing activity.'],
            ['key' => 'thursday_moon_in_virgo',     'text' => 'Good for planning with realistic detail — watch for overloading your schedule in the belief that everything will work out on its own.'],
            ['key' => 'thursday_moon_in_libra',     'text' => 'Excellent for partnerships or agreements — just ensure optimism does not lead to commitments that stretch beyond what is realistic.'],
            ['key' => 'thursday_moon_in_scorpio',   'text' => 'Insight comes more easily than usual — trust what surfaces today and act on it while the clarity is available.'],
            ['key' => 'thursday_moon_in_sagittarius', 'text' => 'Jupiter rules Thursday and Sagittarius hosts the Moon — strong day for travel, teaching, or any bold act of faith in your direction.'],
            ['key' => 'thursday_moon_in_capricorn', 'text' => 'Excellent for long-term planning — progress made today has more staying power than on most other days.'],
            ['key' => 'thursday_moon_in_aquarius',  'text' => 'Strong day for collaborative projects or innovative planning — ideas generated today tend to be genuinely useful beyond your immediate situation.'],
            ['key' => 'thursday_moon_in_pisces',    'text' => 'A deeply intuitive and compassionate combination — good for creative work or any context where empathy and vision outweigh logic.'],

            // ── Friday (Venus) ────────────────────────────────────────────
            ['key' => 'friday_moon_in_aries',     'text' => 'Act on social impulses without overthinking — direct expressions of interest or appreciation land well today.'],
            ['key' => 'friday_moon_in_taurus',    'text' => 'Venus rules the day and Taurus hosts the Moon — sensory pleasure and appreciation for quality peak together today.'],
            ['key' => 'friday_moon_in_gemini',    'text' => 'Light, enjoyable interactions come naturally — good for networking or any social context where wit carries more weight than depth.'],
            ['key' => 'friday_moon_in_cancer',    'text' => 'Small gestures of appreciation for family or close friends carry unusual weight today.'],
            ['key' => 'friday_moon_in_leo',       'text' => 'Venus and Leo combine for peak social confidence — dress well, show up fully, and let yourself enjoy the attention.'],
            ['key' => 'friday_moon_in_virgo',     'text' => 'Excellent for aesthetic refinement or creative work requiring careful execution — social gatherings feel more enjoyable with a purposeful quality.'],
            ['key' => 'friday_moon_in_libra',     'text' => 'Venus rules the day and Libra is its home — harmony, beauty, and social grace are all at their peak today.'],
            ['key' => 'friday_moon_in_scorpio',   'text' => 'Surface-level interactions feel less satisfying than usual — good for deep one-on-one conversations or emotionally honest creative work.'],
            ['key' => 'friday_moon_in_sagittarius', 'text' => 'Keep plans flexible today — spontaneous social plans or outdoor activities are especially enjoyable and the best moments may not be the scheduled ones.'],
            ['key' => 'friday_moon_in_capricorn', 'text' => 'Good for establishing or deepening professional relationships — practical acts of care carry lasting value today.'],
            ['key' => 'friday_moon_in_aquarius',  'text' => 'Community events or collaborative creative work feel especially rewarding — one-on-one romance may feel slightly cooler than broader social pleasure.'],
            ['key' => 'friday_moon_in_pisces',    'text' => 'Venus and Pisces combine for exceptional sensitivity to beauty and emotional atmosphere — creative work or romantic gestures with genuine feeling shine today.'],

            // ── Saturday (Saturn) ─────────────────────────────────────────
            ['key' => 'saturday_moon_in_aries',     'text' => 'Channel Aries energy into starting something you will actually maintain rather than something that burns bright and fades.'],
            ['key' => 'saturday_moon_in_taurus',    'text' => 'Saturn and Taurus both favor slow, steady progress — one of the most productive Saturday combinations for sustained, durable effort.'],
            ['key' => 'saturday_moon_in_gemini',    'text' => 'Choose one or two tasks and stay with them — short, varied work sessions are more realistic than long concentrated blocks today.'],
            ['key' => 'saturday_moon_in_cancer',    'text' => 'Good for domestic responsibilities — attending to emotional duties directly reduces the weight rather than adding to it.'],
            ['key' => 'saturday_moon_in_leo',       'text' => 'Good for behind-the-scenes creative work or preparing something that will be shown publicly later.'],
            ['key' => 'saturday_moon_in_virgo',     'text' => 'Saturn and Virgo complement each other strongly — excellent for administrative tasks or any work requiring careful organization and follow-through.'],
            ['key' => 'saturday_moon_in_libra',     'text' => 'Good for reviewing agreements or addressing long-standing imbalances — progress today requires honesty more than diplomacy.'],
            ['key' => 'saturday_moon_in_scorpio',   'text' => 'Excellent for research or working through something requiring patience — what you resolve today tends to stay resolved.'],
            ['key' => 'saturday_moon_in_sagittarius', 'text' => 'Big ideas benefit from being tested against practical constraints — good for strategic planning that connects ambition with realistic timelines.'],
            ['key' => 'saturday_moon_in_capricorn', 'text' => 'Saturn rules the day and Capricorn hosts the Moon — one of the most productive Saturdays for long-term goals.'],
            ['key' => 'saturday_moon_in_aquarius',  'text' => 'Good for planning or contributing to group projects — individual emotional needs take a back seat today and that is appropriate.'],
            ['key' => 'saturday_moon_in_pisces',    'text' => 'Good for practices requiring both regularity and inner openness, like writing, meditation, or music — focus may be harder than usual.'],

            // ── Sunday (Sun) ──────────────────────────────────────────────
            ['key' => 'sunday_moon_in_aries',     'text' => 'Energy is high and self-expression comes easily — good for outdoor activity, personal projects, or anything requiring initiative.'],
            ['key' => 'sunday_moon_in_taurus',    'text' => 'Sunday\'s ease meets Taurus\'s love of comfort — allow yourself to slow down, enjoy quality food, or engage in creative pleasure with no deadline.'],
            ['key' => 'sunday_moon_in_gemini',    'text' => 'A social Sunday with light conversation and mental stimulation — deep concentration is harder than usual, so embrace the variety.'],
            ['key' => 'sunday_moon_in_cancer',    'text' => 'A day for family time, meaningful meals, or personal creative work in a private setting — recognition feels most satisfying from people you are genuinely close to.'],
            ['key' => 'sunday_moon_in_leo',       'text' => 'The Sun rules the day and Leo amplifies everything — dress boldly, take initiative in social situations, and let yourself occupy space fully.'],
            ['key' => 'sunday_moon_in_virgo',     'text' => 'Rest and purpose feel most satisfying today when combined — gentle organization or health habits suit the energy well.'],
            ['key' => 'sunday_moon_in_libra',     'text' => 'Harmony in relationships feels more accessible than usual — excellent for social events or time with someone whose company you genuinely enjoy.'],
            ['key' => 'sunday_moon_in_scorpio',   'text' => 'Meaningful one-on-one time or honest self-reflection suits this combination better than large social gatherings.'],
            ['key' => 'sunday_moon_in_sagittarius', 'text' => 'One of the most expansive, enthusiastic combinations of the week — travel, outdoor adventure, or learning something new fits the energy perfectly.'],
            ['key' => 'sunday_moon_in_capricorn', 'text' => 'A productive Sunday for personal goals without weekday pressures — progress made today has a quiet satisfaction that lasts.'],
            ['key' => 'sunday_moon_in_aquarius',  'text' => 'Social gatherings or collaborative creative projects feel especially rewarding — individual recognition matters less than a sense of collective contribution.'],
            ['key' => 'sunday_moon_in_pisces',    'text' => 'Rest without guilt today — gentle creative expression, spiritual practice, or compassionate connection suit the energy well.'],
        ];
    }
}
