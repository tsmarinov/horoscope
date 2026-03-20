<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored retrograde short text blocks (1 sentence each).
 *
 * Section: retrograde_short
 * Key format: {planet}_rx_{sign}
 * 96 blocks: 8 planets × 12 signs
 */
class RetrogradeTextsShortSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'retrograde_short',
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

        $this->command->info('Retrograde short texts seeded — ' . count($rows) . ' blocks.');
    }

    private function blocks(): array
    {
        return [

            // ── Mercury Rx ────────────────────────────────────────────────

            ['key' => 'mercury_rx_aries',
             'text' => 'Impulsive messages and hasty decisions are more likely to backfire right now — slow down before sending or committing.'],

            ['key' => 'mercury_rx_taurus',
             'text' => 'Thinking slows and stubbornness increases during this period, making it harder than usual to revise financial or practical decisions.'],

            ['key' => 'mercury_rx_gemini',
             'text' => 'Miscommunications and information overload peak right now — double-check everything before acting on it.'],

            ['key' => 'mercury_rx_cancer',
             'text' => 'Past conversations and unresolved family matters resurface during this period, pulling attention away from clear thinking.'],

            ['key' => 'mercury_rx_leo',
             'text' => 'Self-expression feels blocked right now — revise creative work rather than launching anything new.'],

            ['key' => 'mercury_rx_virgo',
             'text' => 'Critical attention to errors peaks during this period, but perfectionism may prevent you from finishing what you started.'],

            ['key' => 'mercury_rx_libra',
             'text' => 'Relationship agreements and pending decisions tangle right now — avoid finalizing contracts or commitments until Mercury moves direct.'],

            ['key' => 'mercury_rx_scorpio',
             'text' => 'Sensitive communications are especially prone to misreading right now — what is left unsaid matters more than usual.'],

            ['key' => 'mercury_rx_sagittarius',
             'text' => 'Travel plans and big-picture commitments hit snags during this period — review beliefs before broadcasting them.'],

            ['key' => 'mercury_rx_capricorn',
             'text' => 'Professional communications go astray right now — review rather than announce, and delay signing anything important.'],

            ['key' => 'mercury_rx_aquarius',
             'text' => 'Technical systems and group coordination hit unexpected friction during this period — check before assuming things are working.'],

            ['key' => 'mercury_rx_pisces',
             'text' => 'Thinking becomes vague and intuitive rather than precise right now — avoid contracts and deadlines while this lasts.'],

            // ── Venus Rx ──────────────────────────────────────────────────

            ['key' => 'venus_rx_aries',
             'text' => 'Old romantic patterns resurface during this period — impulsive decisions about love or money tend to need correction afterward.'],

            ['key' => 'venus_rx_taurus',
             'text' => 'Your values and sense of self-worth are under quiet review right now — examine what you genuinely want rather than what habit expects.'],

            ['key' => 'venus_rx_gemini',
             'text' => 'Past social connections resurface during this period and existing relationships require more honesty than usual.'],

            ['key' => 'venus_rx_cancer',
             'text' => 'Emotional security in close relationships is being reassessed right now — nurturing yourself matters more than seeking reassurance.'],

            ['key' => 'venus_rx_leo',
             'text' => 'The need for recognition in relationships is stronger than usual during this period — examine what you genuinely offer rather than what you want back.'],

            ['key' => 'venus_rx_virgo',
             'text' => 'Criticism in close relationships sharpens right now — small adjustments to how you give and receive care matter more than dramatic changes.'],

            ['key' => 'venus_rx_libra',
             'text' => 'Partnership imbalances that were tolerated before become harder to ignore during this period — avoid major relationship decisions until you see the full picture.'],

            ['key' => 'venus_rx_scorpio',
             'text' => 'Deep patterns of trust and control in close relationships resurface right now — honest examination is more useful than avoidance.'],

            ['key' => 'venus_rx_sagittarius',
             'text' => 'Expectations in relationships are under scrutiny during this period — past connections may resurface and require honest reassessment.'],

            ['key' => 'venus_rx_capricorn',
             'text' => 'Commitments made from practicality rather than genuine connection feel burdensome right now — assess honestly before deciding anything.'],

            ['key' => 'venus_rx_aquarius',
             'text' => 'The balance between freedom and commitment in relationships is being tested during this period — clarify what you genuinely need.'],

            ['key' => 'venus_rx_pisces',
             'text' => 'Romantic idealization peaks right now — connections formed during this period often need honest reassessment once it ends.'],

            // ── Mars Rx ───────────────────────────────────────────────────

            ['key' => 'mars_rx_aries',
             'text' => 'Drive and initiative misfire easily right now — redirecting existing efforts is more productive than starting anything new.'],

            ['key' => 'mars_rx_taurus',
             'text' => 'Physical energy runs slower and more stubborn than usual during this period — patience with your own pace matters more than forcing output.'],

            ['key' => 'mars_rx_gemini',
             'text' => 'Energy scatters across too many directions right now — choose one or two priorities and protect them from everything else.'],

            ['key' => 'mars_rx_cancer',
             'text' => 'Frustration tends toward passive expression during this period — identifying what you actually want before reacting serves you better.'],

            ['key' => 'mars_rx_leo',
             'text' => 'Ambition feels blocked or hollow right now — reassessing your goals is more honest than pushing for visible results.'],

            ['key' => 'mars_rx_virgo',
             'text' => 'Self-criticism is more active than productive effort during this period — small consistent actions build more than sweeping overhauls.'],

            ['key' => 'mars_rx_libra',
             'text' => 'Asserting your needs in relationships feels harder than usual right now — honest conversations work better than continued avoidance.'],

            ['key' => 'mars_rx_scorpio',
             'text' => 'Intense energy is available but often turns inward during this period — deep work rewards more than impulsive confrontation.'],

            ['key' => 'mars_rx_sagittarius',
             'text' => 'The drive to expand hits practical obstacles right now — reviewing your direction rewards more than forcing movement.'],

            ['key' => 'mars_rx_capricorn',
             'text' => 'Ambition is present but progress feels blocked during this period — reassessing your methods matters more than increasing effort.'],

            ['key' => 'mars_rx_aquarius',
             'text' => 'The drive to act on principle tangles with personal frustration right now — review what is actually worth fighting for.'],

            ['key' => 'mars_rx_pisces',
             'text' => 'Physical energy is diffuse and motivation unclear during this period — focused effort on what genuinely matters yields more than pushing through fatigue.'],

            // ── Jupiter Rx ────────────────────────────────────────────────

            ['key' => 'jupiter_rx_aries',
             'text' => 'Bold initiatives launched recently may feel overextended right now — honest reassessment of where your optimism is warranted serves better than new beginnings.'],

            ['key' => 'jupiter_rx_taurus',
             'text' => 'Material growth slows during this period — consolidating what you have rewards more than reaching for more.'],

            ['key' => 'jupiter_rx_gemini',
             'text' => 'Ideas that felt promising now require honest evaluation right now — connecting existing knowledge serves better than gathering new material.'],

            ['key' => 'jupiter_rx_cancer',
             'text' => 'Emotional generosity may feel less available during this period — honest reflection on where your genuine care is directed rewards more than performed warmth.'],

            ['key' => 'jupiter_rx_leo',
             'text' => 'Creative confidence retreats inward right now — evaluating what you are genuinely proud of serves better than seeking external acknowledgment.'],

            ['key' => 'jupiter_rx_virgo',
             'text' => 'Practical growth slows during this period — reviewing sustainability rewards more than continued expansion.'],

            ['key' => 'jupiter_rx_libra',
             'text' => 'Beliefs about fairness in partnerships are under internal review right now — clarifying what you genuinely want matters more than what seems reasonable.'],

            ['key' => 'jupiter_rx_scorpio',
             'text' => 'Expansion pauses for honest reassessment during this period — research and deeper understanding reward more than new initiatives.'],

            ['key' => 'jupiter_rx_sagittarius',
             'text' => 'Long-held beliefs are under more rigorous self-examination than usual right now — distinguishing what you know from what you merely believe is the honest work.'],

            ['key' => 'jupiter_rx_capricorn',
             'text' => 'Ambitions built on optimistic assumptions are being tested during this period — reviewing what is genuinely working matters more than continuing to build.'],

            ['key' => 'jupiter_rx_aquarius',
             'text' => 'Ideas about collective progress turn inward right now — reviewing rather than promoting your vision serves better.'],

            ['key' => 'jupiter_rx_pisces',
             'text' => 'Spiritual and creative expansions slow during this period — honesty about what you genuinely believe rewards more than what feels comforting.'],

            // ── Saturn Rx ─────────────────────────────────────────────────

            ['key' => 'saturn_rx_aries',
             'text' => 'Structures built on impulse are being tested right now — distinguishing discipline you are avoiding from limits that genuinely serve you is the honest work.'],

            ['key' => 'saturn_rx_taurus',
             'text' => 'Long-term financial commitments require more active maintenance than assumed during this period — honest reckoning with resources and obligations is due.'],

            ['key' => 'saturn_rx_gemini',
             'text' => 'Mental discipline and consistency are being tested right now — returning to half-finished work rewards more than starting fresh.'],

            ['key' => 'saturn_rx_cancer',
             'text' => 'The structures supporting emotional security are being reassessed during this period — honest reflection on what genuinely sustains you matters more than seeking reassurance.'],

            ['key' => 'saturn_rx_leo',
             'text' => 'Recognition that has been slow to arrive may prompt honest reassessment of your goals right now — internal validation serves better than seeking external acknowledgment.'],

            ['key' => 'saturn_rx_virgo',
             'text' => 'Routines maintained out of habit rather than genuine benefit are being tested during this period — small honest corrections now produce more durable improvement.'],

            ['key' => 'saturn_rx_libra',
             'text' => 'Long-standing agreements that have been tolerated rather than honored surface for reassessment right now — holding yourself to the standards you expect from others is the honest work.'],

            ['key' => 'saturn_rx_scorpio',
             'text' => 'Deep patterns of control and shared responsibility are under quiet review during this period — unacknowledged habits of avoidance or control are being surfaced.'],

            ['key' => 'saturn_rx_sagittarius',
             'text' => 'Beliefs functioning as unexamined rules are being tested right now — honest review of long-term commitments matters more than continued forward movement.'],

            ['key' => 'saturn_rx_capricorn',
             'text' => 'Ambitions and career structures face the most rigorous review possible during this period — work sustained through discipline alone may reveal where genuine motivation is missing.'],

            ['key' => 'saturn_rx_aquarius',
             'text' => 'Group commitments and future-oriented structures are being honestly tested right now — distinguishing what is worth preserving from what needs updating is the useful work.'],

            ['key' => 'saturn_rx_pisces',
             'text' => 'Limits that protect your energy and creative focus are being reconsidered during this period — building deliberate structure into diffuse areas rewards more than continued openness.'],

            // ── Uranus Rx ─────────────────────────────────────────────────

            ['key' => 'uranus_rx_aries',
             'text' => 'The drive for radical personal change turns inward right now — distinguishing genuine need for freedom from avoidance of responsibility is the honest work.'],

            ['key' => 'uranus_rx_taurus',
             'text' => 'Disruptions to material security pause for internal processing during this period — reconsidering what stability genuinely means for you is more useful than further change.'],

            ['key' => 'uranus_rx_gemini',
             'text' => 'Mental restlessness turns inward right now — reviewing innovative ideas carefully before acting on them rewards more than broadcasting them immediately.'],

            ['key' => 'uranus_rx_cancer',
             'text' => 'Disruptions to home and emotional security are being processed internally during this period — integrating what has already shifted serves better than forcing further change.'],

            ['key' => 'uranus_rx_leo',
             'text' => 'The urge for radical self-reinvention slows right now — revising your creative direction serves better than abandoning it entirely.'],

            ['key' => 'uranus_rx_virgo',
             'text' => 'The impulse to overhaul work or health systems turns inward during this period — reviewing which innovations are genuinely useful matters more than implementing new ones.'],

            ['key' => 'uranus_rx_libra',
             'text' => 'Disruptions to relationship patterns pause for internal reassessment right now — examining what freedom you genuinely need within close relationships is more useful than acting.'],

            ['key' => 'uranus_rx_scorpio',
             'text' => 'Deep structural disruptions turn inward during this period — examining what you have been refusing to acknowledge matters more than pushing further change.'],

            ['key' => 'uranus_rx_sagittarius',
             'text' => 'The impulse to shatter old beliefs pauses for honest review right now — distinguishing genuine insight from the appeal of novelty is the useful work.'],

            ['key' => 'uranus_rx_capricorn',
             'text' => 'Disruptions to career structures turn inward during this period — questioning which structures are genuinely outdated versus merely inconvenient is the honest work.'],

            ['key' => 'uranus_rx_aquarius',
             'text' => 'Revolutionary impulses turn sharply inward right now — asking whether your vision of collective change reflects genuine insight or escape from personal constraint is the honest work.'],

            ['key' => 'uranus_rx_pisces',
             'text' => 'Inner disruptions and creative breakthroughs turn inward during this period — processing what has already been loosened serves better than seeking further disruption.'],

            // ── Neptune Rx ────────────────────────────────────────────────

            ['key' => 'neptune_rx_aries',
             'text' => 'Illusions about self-sufficiency and personal initiative are being dissolved right now — clearer perception of your actual motivations is available if you look honestly.'],

            ['key' => 'neptune_rx_taurus',
             'text' => 'Idealized attachments to security and material comfort are being gently tested during this period — examining the difference between genuine security and avoidance of vulnerability is the honest work.'],

            ['key' => 'neptune_rx_gemini',
             'text' => 'Illusions about knowledge and intellectual certainty are being dissolved right now — admitting the limits of what you actually understand rewards more than maintaining appearances.'],

            ['key' => 'neptune_rx_cancer',
             'text' => 'Idealized images of home and family are being quietly questioned during this period — honest acknowledgment of emotional needs serves better than their idealization.'],

            ['key' => 'neptune_rx_leo',
             'text' => 'Grandiose ideas about your own identity or creative power are being tested right now — honest self-perception serves better than an inspiring self-narrative.'],

            ['key' => 'neptune_rx_virgo',
             'text' => 'The idealization of perfection and service is being dissolved during this period — compassion for your own imperfections serves better than stricter standards.'],

            ['key' => 'neptune_rx_libra',
             'text' => 'Illusions about perfect harmony in relationships are being tested right now — the gap between the relationship you imagine and the one that exists becomes harder to overlook.'],

            ['key' => 'neptune_rx_scorpio',
             'text' => 'Deep illusions about psychological insight are surfacing for examination during this period — genuine confrontation with what you have been avoiding is the most honest use of this time.'],

            ['key' => 'neptune_rx_sagittarius',
             'text' => 'Idealized beliefs and grand visions of meaning are being tested right now — intellectual humility rewards more than inspirational conviction.'],

            ['key' => 'neptune_rx_capricorn',
             'text' => 'The idealization of achievement and control is being quietly dissolved during this period — honest reckoning with what you cannot control despite your best efforts is due.'],

            ['key' => 'neptune_rx_aquarius',
             'text' => 'Idealized visions of collective progress are being tested right now — honest assessment of what is genuinely changing versus wishful projection is the useful work.'],

            ['key' => 'neptune_rx_pisces',
             'text' => 'Comfortable illusions are ready for dissolution during this period — genuine spiritual honesty rewards more right now than the comfort of belief.'],

            // ── Pluto Rx ──────────────────────────────────────────────────

            ['key' => 'pluto_rx_aries',
             'text' => 'The transformation of personal identity turns inward right now — identifying the fear or need your drive toward control is actually serving is the honest work.'],

            ['key' => 'pluto_rx_taurus',
             'text' => 'Deep transformation of material values turns inward during this period — what you are holding onto out of fear versus genuine need becomes clearer if you look honestly.'],

            ['key' => 'pluto_rx_gemini',
             'text' => 'Deeply held mental habits are being examined at their foundation right now — recognizing patterns in your own thinking that have outlived their usefulness is the honest work.'],

            ['key' => 'pluto_rx_cancer',
             'text' => 'Inherited emotional habits of attaching and withdrawing surface for honest examination during this period — genuine healing of old patterns is more available than creating new ones.'],

            ['key' => 'pluto_rx_leo',
             'text' => 'The transformation of ego and creative power turns inward right now — patterns of control or approval-seeking behind your self-expression are ready for honest examination.'],

            ['key' => 'pluto_rx_virgo',
             'text' => 'Compulsive perfectionism driving your daily functioning is being examined at its root during this period — identifying the fear motivating your standards is the honest work.'],

            ['key' => 'pluto_rx_libra',
             'text' => 'Power dynamics in close relationships are being examined honestly right now — genuine renegotiation of how you relate to others is more available than usual.'],

            ['key' => 'pluto_rx_scorpio',
             'text' => 'What has been hidden surfaces with unusual clarity during this period — honesty about the most uncomfortable aspects of your life is demanded; avoidance is not realistic.'],

            ['key' => 'pluto_rx_sagittarius',
             'text' => 'Convictions functioning as identity rather than genuine understanding surface for examination right now — distinguishing what you know from what you merely believe is the honest work.'],

            ['key' => 'pluto_rx_capricorn',
             'text' => 'The transformation of ambition turns inward during this period — honestly asking whether what you are building serves your values or only your need for control is due.'],

            ['key' => 'pluto_rx_aquarius',
             'text' => 'Revolutionary impulses are being examined for their actual cost and genuine motivation right now — honest examination of power dynamics within your communities matters more than acting on principle.'],

            ['key' => 'pluto_rx_pisces',
             'text' => 'The most genuine confrontation with the limits of self is available during this period — what has felt like transcendence may reveal itself as avoidance when examined honestly.'],

        ];
    }
}
