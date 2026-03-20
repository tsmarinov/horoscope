<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored daily tip text blocks.
 *
 * Section: daily_tip
 * Key format: {weekday}_moon_in_{sign}  (e.g. tuesday_moon_in_sagittarius)
 * 84 blocks: 7 weekdays × 12 Moon signs
 *
 * Style rules:
 *   - 2–3 sentences, actionable
 *   - Address as "you/your"
 *   - Combine weekday planetary ruler energy with Moon sign energy
 *   - Plain text (no HTML)
 *   - Varied sentence openings
 *   - Forbidden: journey, path, soul, essence, force, pull, tension, dance, dissolves
 */
class DailyTipSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'daily_tip',
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

        $this->command->info('Daily tips seeded — ' . count($rows) . ' blocks.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function blocks(): array
    {
        return [

            // ── Monday (Moon) ─────────────────────────────────────────────

            ['key' => 'monday_moon_in_aries', 'text' =>
                'Act on gut feelings for short-burst tasks today — impatience fires quickly under this combination. '
                . 'Watch for irritability toward family or close people, since Monday already heightens emotional sensitivity.'],

            ['key' => 'monday_moon_in_taurus', 'text' =>
                'Monday\'s reflective quality settles comfortably into Taurus today, favoring rest, familiar food, and slow tangible tasks. '
                . 'Disrupting your routine will feel disproportionately difficult — lean into home care and quiet productivity instead.'],

            ['key' => 'monday_moon_in_gemini', 'text' =>
                'Short conversations and quick mental tasks work well today, but avoid making emotional decisions amid scattered energy. '
                . 'Monday\'s inward pull and Gemini\'s restlessness compete — give yourself permission to move between tasks without forcing depth.'],

            ['key' => 'monday_moon_in_cancer', 'text' =>
                'The Moon rules both the day and the sign today, amplifying emotional sensitivity to its peak. '
                . 'Lean into home, close relationships, and self-care — this is one of the most intuitive combinations of any week.'],

            ['key' => 'monday_moon_in_leo', 'text' =>
                'You may feel a pull between wanting to withdraw and wanting to be seen — both are valid today. '
                . 'Small creative acts or genuine appreciation from someone close will satisfy both Monday\'s reflective tone and Leo\'s need for expression.'],

            ['key' => 'monday_moon_in_virgo', 'text' =>
                'Direct your attention to health routines, household organization, or any task requiring careful attention. '
                . 'Emotional satisfaction comes from useful action today — Monday\'s sensitivity pairs well with Virgo\'s focus on what needs fixing.'],

            ['key' => 'monday_moon_in_libra', 'text' =>
                'Relational sensitivity is especially strong today, with both Monday and Libra emphasizing connection and care. '
                . 'Good for honest, gentle conversations — avoid sitting with unresolved tension, as it will feel heavier than usual.'],

            ['key' => 'monday_moon_in_scorpio', 'text' =>
                'Feelings run unusually strong and private matters feel pressing under this combination. '
                . 'Honor the need for quiet and honest reflection today rather than pushing through social obligations.'],

            ['key' => 'monday_moon_in_sagittarius', 'text' =>
                'Monday\'s reflective quality is at odds with Sagittarius\'s restlessness — you may feel emotionally unsettled rather than restful. '
                . 'A short walk, change of scenery, or reading something expansive helps discharge the tension productively.'],

            ['key' => 'monday_moon_in_capricorn', 'text' =>
                'Monday\'s emotional sensitivity gets grounded by Capricorn\'s practicality today. '
                . 'Tackle domestic responsibilities or quiet administrative tasks — there is satisfaction in completing something concrete even on a heavy emotional day.'],

            ['key' => 'monday_moon_in_aquarius', 'text' =>
                'Emotional detachment rises today, making Monday\'s usual inward pull feel less intense than normal. '
                . 'Group conversations or community matters feel more engaging than personal ones — let logic guide any decisions rather than mood.'],

            ['key' => 'monday_moon_in_pisces', 'text' =>
                'Both Monday and Pisces amplify emotional permeability — you may absorb the moods of whoever is nearby without realizing it. '
                . 'Protect your environment carefully today, and give yourself permission to rest without agenda.'],

            // ── Tuesday (Mars) ────────────────────────────────────────────

            ['key' => 'tuesday_moon_in_aries', 'text' =>
                'Mars rules the day and Aries hosts the Moon — drive and impatience are both sharply elevated. '
                . 'Channel the energy into physical effort or decisive action; small frustrations can escalate quickly if left unaddressed.'],

            ['key' => 'tuesday_moon_in_taurus', 'text' =>
                'Mars pushes for action but Taurus resists — expect internal friction between wanting to move fast and needing to feel stable first. '
                . 'Tasks requiring sustained physical effort work well; avoid forcing decisions that need more time.'],

            ['key' => 'tuesday_moon_in_gemini', 'text' =>
                'Mars\'s drive combines with Gemini\'s restlessness for a day full of starts and scattered energy. '
                . 'Best for short, varied tasks and direct conversations — long-term planning is harder when attention keeps splitting.'],

            ['key' => 'tuesday_moon_in_cancer', 'text' =>
                'Mars wants to push forward but Cancer pulls attention toward emotional safety and home. '
                . 'Protect what matters to you today rather than going on offense — defensiveness is natural but unnecessary conflict is easily avoided.'],

            ['key' => 'tuesday_moon_in_leo', 'text' =>
                'Mars and Leo both favor bold, visible action — confidence and assertiveness are at their strongest today. '
                . 'Put yourself forward in situations that call for leadership or creative initiative; this combination rewards courage.'],

            ['key' => 'tuesday_moon_in_virgo', 'text' =>
                'Mars\'s drive gets channeled through Virgo\'s precision today — excellent for work requiring focus, correction, or physical skill. '
                . 'Avoid harsh criticism of others; the analytical energy can tip into sharpness more easily than usual.'],

            ['key' => 'tuesday_moon_in_libra', 'text' =>
                'Mars pushes for directness while Libra seeks diplomacy — expect tension between what you want to say and what seems fair. '
                . 'Choose your battles carefully; not every conflict today is worth the energy it would cost.'],

            ['key' => 'tuesday_moon_in_scorpio', 'text' =>
                'Mars and Scorpio both run at high intensity — determination is strong, but so is the risk of power struggles or fixation. '
                . 'Excellent for research, deep work, or resolving something that has been avoided.'],

            ['key' => 'tuesday_moon_in_sagittarius', 'text' =>
                'Mars and Sagittarius combine for restless, expansive energy — best for physical activity, travel, or tackling something you have been postponing. '
                . 'Opinions come out bluntly today; directness works better than diplomacy.'],

            ['key' => 'tuesday_moon_in_capricorn', 'text' =>
                'Mars\'s drive meets Capricorn\'s focus on results — one of the more productive Tuesday combinations available. '
                . 'Tackle demanding, high-effort tasks with confidence; ambition and stamina are both fully available today.'],

            ['key' => 'tuesday_moon_in_aquarius', 'text' =>
                'Mars\'s personal drive bumps against Aquarius\'s preference for group goals — energy is available but may feel directionless without a clear purpose. '
                . 'Work on something that benefits others as much as yourself and momentum follows more easily.'],

            ['key' => 'tuesday_moon_in_pisces', 'text' =>
                'Mars\'s assertiveness is softened by Pisces sensitivity today — direct confrontation feels harder than usual. '
                . 'Quiet determination works better than force; artistic or compassionate actions are more effective than aggressive ones.'],

            // ── Wednesday (Mercury) ───────────────────────────────────────

            ['key' => 'wednesday_moon_in_aries', 'text' =>
                'Mercury\'s quick thinking meets Aries impulsiveness — words come fast and conclusions form before all the facts are in. '
                . 'Good for brainstorming and rapid decisions; avoid sending messages written in anger.'],

            ['key' => 'wednesday_moon_in_taurus', 'text' =>
                'Mercury wants to move quickly but Taurus slows processing down — thinking is more careful and deliberate than usual today. '
                . 'Good for reviewing, revising, or any decision that benefits from patience rather than speed.'],

            ['key' => 'wednesday_moon_in_gemini', 'text' =>
                'Mercury rules the day and Gemini amplifies it — communication, curiosity, and mental energy all peak together. '
                . 'Write, negotiate, learn, or make calls; just be aware that follow-through may be harder than getting started.'],

            ['key' => 'wednesday_moon_in_cancer', 'text' =>
                'Mercury\'s logical clarity meets Cancer\'s emotional reasoning — intuitive communication works especially well today. '
                . 'Good for conversations about home, family, or matters where empathy matters more than data.'],

            ['key' => 'wednesday_moon_in_leo', 'text' =>
                'Mercury\'s communication gets a theatrical edge with Leo\'s flair — ideas come out more boldly and persuasively than usual. '
                . 'Good for presentations, creative pitches, or any situation where confidence in delivery matters.'],

            ['key' => 'wednesday_moon_in_virgo', 'text' =>
                'Mercury rules the day and Virgo sharpens analysis further — precision and problem-solving are at their best. '
                . 'Excellent for editing, detail work, or any task requiring careful attention; avoid getting stuck in over-analysis.'],

            ['key' => 'wednesday_moon_in_libra', 'text' =>
                'Mercury and Libra combine for diplomatic, balanced communication today. '
                . 'Perfect for negotiations, mediating disagreements, or any conversation requiring tact — decisions may slow as multiple perspectives keep presenting themselves.'],

            ['key' => 'wednesday_moon_in_scorpio', 'text' =>
                'Mercury\'s communication takes on a probing, investigative quality — excellent for research or difficult conversations that require honesty. '
                . 'Be aware that words carry more weight than usual; others are reading between the lines.'],

            ['key' => 'wednesday_moon_in_sagittarius', 'text' =>
                'Mercury\'s precision meets Sagittarius\'s big-picture thinking — excellent for teaching, writing, or planning on a larger scale. '
                . 'Watch for overpromising or glossing over important details in favor of the broad strokes.'],

            ['key' => 'wednesday_moon_in_capricorn', 'text' =>
                'Mercury\'s flexibility gets grounded by Capricorn\'s practicality — communications today tend toward the serious and purposeful. '
                . 'Good for business writing, structured planning, or any task where precision and authority matter.'],

            ['key' => 'wednesday_moon_in_aquarius', 'text' =>
                'Mercury and Aquarius both favor intellectual analysis and unconventional thinking today. '
                . 'Strong day for innovative ideas, group discussions, or original problem-solving — personal or emotional topics may feel less accessible than usual.'],

            ['key' => 'wednesday_moon_in_pisces', 'text' =>
                'Mercury\'s clarity is softened by Pisces\'s impressionistic quality — intuition and imagination are stronger than logical precision. '
                . 'Good for creative writing or compassionate communication; avoid important contracts or detailed technical work if possible.'],

            // ── Thursday (Jupiter) ────────────────────────────────────────

            ['key' => 'thursday_moon_in_aries', 'text' =>
                'Jupiter\'s optimism gets a direct, fast-moving edge from Aries today — confidence and initiative are both strong. '
                . 'Act on a plan you have been holding back; the energy strongly favors beginning something with conviction.'],

            ['key' => 'thursday_moon_in_taurus', 'text' =>
                'Jupiter\'s expansiveness meets Taurus\'s love of comfort — a naturally generous, indulgent combination. '
                . 'Good for financial planning, quality time with people you value, or anything that rewards patience and consistency.'],

            ['key' => 'thursday_moon_in_gemini', 'text' =>
                'Jupiter\'s breadth of thinking meets Gemini\'s curiosity — an excellent day for learning, travel planning, or gathering information across multiple sources. '
                . 'Avoid overcommitting to too many ideas without a concrete next step.'],

            ['key' => 'thursday_moon_in_cancer', 'text' =>
                'Jupiter expands emotional generosity and Cancer deepens it — a strongly nurturing combination. '
                . 'Good for family gatherings, charitable acts, or any context where emotional support and warmth are genuinely needed.'],

            ['key' => 'thursday_moon_in_leo', 'text' =>
                'Jupiter and Leo amplify each other\'s confidence and generosity — one of the most expressive and optimistic combinations of the week. '
                . 'Excellent for leadership, celebrations, or any public-facing activity that benefits from enthusiasm and warmth.'],

            ['key' => 'thursday_moon_in_virgo', 'text' =>
                'Jupiter\'s optimism gets filtered through Virgo\'s practicality — good for planning with realistic detail or work expansion that is actually achievable. '
                . 'Watch for overloading your schedule in the belief that everything will work out on its own.'],

            ['key' => 'thursday_moon_in_libra', 'text' =>
                'Jupiter expands Libra\'s social instincts today — an excellent day for partnerships, agreements, and social events. '
                . 'Generosity in relationships is easy to express; just ensure that optimism does not lead to commitments that stretch beyond what is realistic.'],

            ['key' => 'thursday_moon_in_scorpio', 'text' =>
                'Jupiter\'s expansion meets Scorpio\'s depth — good for research, transformation work, or situations requiring both courage and thoroughness. '
                . 'Insight comes more easily than usual; trust what surfaces today and act on it while the clarity is available.'],

            ['key' => 'thursday_moon_in_sagittarius', 'text' =>
                'Jupiter rules Thursday and Sagittarius hosts the Moon — the combination is at its most expansive and confident. '
                . 'Strong day for travel, teaching, publishing, or any bold act of faith in your own direction.'],

            ['key' => 'thursday_moon_in_capricorn', 'text' =>
                'Jupiter\'s optimism gets a practical, results-focused frame from Capricorn — excellent for long-term planning or setting ambitious but realistic goals. '
                . 'Progress made today has more staying power than on most other days.'],

            ['key' => 'thursday_moon_in_aquarius', 'text' =>
                'Jupiter and Aquarius both favor broad thinking and collective benefit — strong day for collaborative projects, social causes, or innovative planning. '
                . 'Ideas generated today tend to be forward-looking and genuinely useful beyond your immediate situation.'],

            ['key' => 'thursday_moon_in_pisces', 'text' =>
                'Jupiter expands Pisces sensitivity and imagination — a deeply intuitive and compassionate combination. '
                . 'Good for creative work, spiritual practice, or any context where empathy and vision are more valuable than logic.'],

            // ── Friday (Venus) ────────────────────────────────────────────

            ['key' => 'friday_moon_in_aries', 'text' =>
                'Venus\'s appreciation for beauty meets Aries\'s directness — confidence in how you present yourself is higher than usual. '
                . 'Act on social impulses without overthinking; direct expressions of interest or appreciation land well today.'],

            ['key' => 'friday_moon_in_taurus', 'text' =>
                'Venus rules the day and Taurus hosts the Moon — sensory pleasure and appreciation for quality peak together. '
                . 'Perfect for social events, creative work, or anything that rewards slowing down and enjoying what you already have.'],

            ['key' => 'friday_moon_in_gemini', 'text' =>
                'Venus\'s social warmth meets Gemini\'s easy conversation — light, enjoyable interactions come naturally today. '
                . 'Good for networking, catching up with friends, or any social context where wit carries more weight than emotional depth.'],

            ['key' => 'friday_moon_in_cancer', 'text' =>
                'Venus\'s warmth meets Cancer\'s emotional nurturing — deeply relational energy that favors intimate gatherings and genuine care. '
                . 'Small gestures of appreciation for family or close friends carry unusual weight today.'],

            ['key' => 'friday_moon_in_leo', 'text' =>
                'Venus and Leo combine for one of the most socially confident combinations of the week. '
                . 'Dress well, show up fully, and let yourself enjoy the attention — creative work, romantic gestures, and public appreciation all flow more easily than usual.'],

            ['key' => 'friday_moon_in_virgo', 'text' =>
                'Venus\'s appreciation for beauty gets filtered through Virgo\'s eye for detail — excellent for aesthetic refinement or creative work requiring careful execution. '
                . 'Social gatherings feel more enjoyable today when they have a purposeful, considered quality.'],

            ['key' => 'friday_moon_in_libra', 'text' =>
                'Venus rules the day and Libra is its home sign — harmony, beauty, and social grace are all at their peak. '
                . 'Strong day for romantic plans, important conversations in relationships, or anything requiring diplomacy and a light touch.'],

            ['key' => 'friday_moon_in_scorpio', 'text' =>
                'Venus\'s social openness meets Scorpio\'s depth and privacy — surface-level interactions feel less satisfying than usual. '
                . 'Good for deep one-on-one conversations, intimate plans, or creative work with genuine emotional honesty at its core.'],

            ['key' => 'friday_moon_in_sagittarius', 'text' =>
                'Venus\'s pleasure-seeking meets Sagittarius\'s adventurousness — spontaneous social plans or outdoor activities are especially enjoyable today. '
                . 'Keep plans flexible; the best moments may not be the ones you scheduled in advance.'],

            ['key' => 'friday_moon_in_capricorn', 'text' =>
                'Venus\'s warmth is grounded by Capricorn\'s seriousness today — social interactions feel more purposeful and less frivolous than usual. '
                . 'Good for establishing or deepening professional relationships, or for practical acts of care that have lasting value.'],

            ['key' => 'friday_moon_in_aquarius', 'text' =>
                'Venus\'s social instincts meet Aquarius\'s group orientation — community events, group celebrations, or collaborative creative work feel especially rewarding. '
                . 'One-on-one romance may feel slightly cooler than the broader social pleasure available today.'],

            ['key' => 'friday_moon_in_pisces', 'text' =>
                'Venus and Pisces combine for exceptional sensitivity to beauty, music, and emotional atmosphere. '
                . 'Creative work, romantic gestures with genuine feeling, or time spent in nature or art will be especially meaningful today.'],

            // ── Saturday (Saturn) ─────────────────────────────────────────

            ['key' => 'saturday_moon_in_aries', 'text' =>
                'Saturn\'s discipline meets Aries\'s impatience — the tension between pushing ahead and doing things properly is sharpest today. '
                . 'Channel Aries energy into starting something you will actually maintain, rather than something that burns bright and fades.'],

            ['key' => 'saturday_moon_in_taurus', 'text' =>
                'Saturn and Taurus both favor slow, steady progress — one of the most productive Saturday combinations for sustained effort. '
                . 'Build, repair, organize, or tend to something practical; results made today are more durable than usual.'],

            ['key' => 'saturday_moon_in_gemini', 'text' =>
                'Saturn wants focus while Gemini scatters attention — a frustrating combination unless you deliberately choose one or two tasks and stay with them. '
                . 'Short, varied work sessions are more realistic than long concentrated blocks today.'],

            ['key' => 'saturday_moon_in_cancer', 'text' =>
                'Saturn\'s discipline meets Cancer\'s pull toward rest and home — a good day for domestic responsibilities or any practical task related to your living situation. '
                . 'Emotional duties feel heavier today; attending to them directly reduces the weight rather than adding to it.'],

            ['key' => 'saturday_moon_in_leo', 'text' =>
                'Saturn\'s restraint and Leo\'s desire for recognition pull in opposite directions — ambition is present but expression feels constrained. '
                . 'Good for behind-the-scenes creative work or preparing something that will be shown publicly later.'],

            ['key' => 'saturday_moon_in_virgo', 'text' =>
                'Saturn and Virgo complement each other strongly today — precision, responsibility, and sustained attention to detail are all available. '
                . 'Excellent for administrative tasks, health routines, or any work requiring careful organization and follow-through.'],

            ['key' => 'saturday_moon_in_libra', 'text' =>
                'Saturn\'s structure meets Libra\'s need for fairness — good for reviewing agreements or addressing long-standing relational imbalances. '
                . 'Progress in partnerships today requires honesty more than diplomacy.'],

            ['key' => 'saturday_moon_in_scorpio', 'text' =>
                'Saturn and Scorpio both favor depth and endurance — excellent for research, financial planning, or working through something that requires patience and honesty. '
                . 'What you resolve today tends to stay resolved.'],

            ['key' => 'saturday_moon_in_sagittarius', 'text' =>
                'Saturn\'s limits and Sagittarius\'s expansiveness create useful tension today — big ideas benefit from being tested against practical constraints. '
                . 'Good for strategic planning that connects ambition with realistic timelines.'],

            ['key' => 'saturday_moon_in_capricorn', 'text' =>
                'Saturn rules the day and Capricorn hosts the Moon — discipline, ambition, and results-orientation peak together. '
                . 'One of the most productive Saturdays for long-term goals; the work done today tends to compound over time.'],

            ['key' => 'saturday_moon_in_aquarius', 'text' =>
                'Saturn and Aquarius both engage with systems and collective structures — good for planning, contributing to group projects, or thinking through long-term organizational goals. '
                . 'Individual emotional needs take a back seat today; that is appropriate rather than something to resist.'],

            ['key' => 'saturday_moon_in_pisces', 'text' =>
                'Saturn\'s structure meets Pisces\'s fluidity — focus may be harder to maintain than usual, but creative or spiritual disciplines benefit from the combination. '
                . 'Good for practices requiring both regularity and inner openness, like writing, meditation, or music.'],

            // ── Sunday (Sun) ──────────────────────────────────────────────

            ['key' => 'sunday_moon_in_aries', 'text' =>
                'The Sun\'s vitality meets Aries\'s drive — energy is high and self-expression comes easily today. '
                . 'Good for outdoor activity, personal projects, or anything that puts you in a position of initiative and visible confidence.'],

            ['key' => 'sunday_moon_in_taurus', 'text' =>
                'Sunday\'s ease meets Taurus\'s love of comfort — a genuinely restful combination. '
                . 'Allow yourself to slow down, enjoy quality food, spend time in nature, or engage in a creative pleasure that has no deadline attached.'],

            ['key' => 'sunday_moon_in_gemini', 'text' =>
                'The Sun\'s expressiveness meets Gemini\'s curiosity — a social Sunday with light conversation, media, and mental stimulation. '
                . 'Good for catching up with people or exploring a new topic; deep concentration is harder than usual.'],

            ['key' => 'sunday_moon_in_cancer', 'text' =>
                'The Sun\'s vitality turns inward with Cancer\'s home focus — a day for family time, meaningful meals, or personal creative work in a private setting. '
                . 'Recognition today feels most satisfying when it comes from people you are genuinely close to.'],

            ['key' => 'sunday_moon_in_leo', 'text' =>
                'The Sun rules the day and Leo amplifies everything it touches — confidence, creativity, and the desire to be seen are all at peak. '
                . 'Dress boldly, take initiative in social situations, and let yourself occupy space fully without apology.'],

            ['key' => 'sunday_moon_in_virgo', 'text' =>
                'Sunday\'s ease meets Virgo\'s focus on what needs attention — a natural day for gentle organization, health habits, or creative work requiring careful craft. '
                . 'Rest and purpose feel most satisfying today when they are combined rather than separated.'],

            ['key' => 'sunday_moon_in_libra', 'text' =>
                'The Sun\'s expressiveness meets Libra\'s social grace — excellent for social events, aesthetic activities, or time with someone whose company you genuinely enjoy. '
                . 'Harmony in relationships feels more accessible than usual; take advantage of it.'],

            ['key' => 'sunday_moon_in_scorpio', 'text' =>
                'The Sun\'s outward vitality meets Scorpio\'s preference for depth and privacy today. '
                . 'Meaningful one-on-one time, creative work with emotional intensity, or honest self-reflection suits this combination better than large social gatherings.'],

            ['key' => 'sunday_moon_in_sagittarius', 'text' =>
                'The Sun and Sagittarius combine for one of the most expansive, enthusiastic combinations of the week. '
                . 'Travel, outdoor adventure, learning something new, or any act of generous self-expression fits the energy perfectly today.'],

            ['key' => 'sunday_moon_in_capricorn', 'text' =>
                'The Sun\'s vitality gets channeled through Capricorn\'s ambition — a productive Sunday for working on personal goals without the usual weekday pressures. '
                . 'Progress made today has a quiet satisfaction that lasts beyond the day itself.'],

            ['key' => 'sunday_moon_in_aquarius', 'text' =>
                'The Sun\'s self-expression meets Aquarius\'s group orientation — social gatherings, community events, or collaborative creative projects feel especially rewarding. '
                . 'Individual recognition matters less today than a sense of collective contribution.'],

            ['key' => 'sunday_moon_in_pisces', 'text' =>
                'The Sun\'s vitality softens in Pisces today — gentle creative expression, spiritual practice, music, or compassionate connection suit the energy well. '
                . 'Rest without guilt; not every Sunday needs to be productive to count as genuinely restorative.'],

        ];
    }
}
