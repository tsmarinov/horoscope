<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lunation interpretation text blocks — manually authored, 1 variant.
 *
 * section: lunation
 * Keys: new_moon_{sign} (12) + full_moon_{sign} (12) = 24 blocks
 *
 * New Moon = intention, seed, beginning
 * Full Moon = culmination, revelation, release
 */
class LunationTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'lunation',
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
    }

    private function blocks(): array
    {
        return [
            // ── New Moons ────────────────────────────────────────────────

            [
                'key'  => 'new_moon_aries',
                'text' => 'A New Moon in Aries marks a <strong>fresh start powered by personal initiative</strong>. This is the most energetically charged lunation of the year for new beginnings — the impulse to act, start, and lead is at its peak. Whatever you have been hesitating to launch, this is the moment to begin. <strong>Set intentions around identity, courage, and what you want to initiate in the months ahead.</strong> The seed planted now carries the energy of self-assertion and forward motion.',
            ],
            [
                'key'  => 'new_moon_taurus',
                'text' => 'A New Moon in Taurus invites you to <strong>plant intentions around security, material life, and what truly matters to you</strong>. This lunation slows the pace and asks what you want to build that will last — in your finances, your home, your body, your values. <strong>What do you want to grow steadily over time?</strong> The Taurus New Moon favors patient effort over rushed action. Set intentions that are grounded, practical, and rooted in what gives you a genuine sense of stability.',
            ],
            [
                'key'  => 'new_moon_gemini',
                'text' => 'A New Moon in Gemini opens a cycle around <strong>communication, learning, and the exchange of ideas</strong>. This is a fertile time to begin a course of study, start writing, make new local connections, or clarify how and what you communicate. <strong>Curiosity is your most important asset right now.</strong> Set intentions around the conversations you want to have, the information you want to gather, and the ideas you want to develop. What you say and learn in this cycle can open unexpected doors.',
            ],
            [
                'key'  => 'new_moon_cancer',
                'text' => 'A New Moon in Cancer opens a cycle around <strong>home, family, emotional security, and belonging</strong>. This lunation asks where you feel most at home — and where you don\'t. It\'s a powerful time to set intentions around your living situation, your family relationships, or the emotional foundations you want to strengthen. <strong>What kind of inner and outer home do you want to create?</strong> The Cancer New Moon rewards genuine self-care and honest attention to what you need to feel emotionally safe.',
            ],
            [
                'key'  => 'new_moon_leo',
                'text' => 'A New Moon in Leo lights up the area of <strong>creative self-expression, joy, romance, and authentic visibility</strong>. This is the lunation that asks you to stop hiding and start showing up as yourself. <strong>Set intentions around what you want to create, perform, love, or celebrate.</strong> The energy favors boldness, generosity, and genuine enthusiasm. Whatever you begin under this lunation will carry the Leo signature: warmth, drama, and the courage to be seen. Follow what makes you come alive.',
            ],
            [
                'key'  => 'new_moon_virgo',
                'text' => 'A New Moon in Virgo begins a cycle of <strong>practical refinement, health, and purposeful daily effort</strong>. This is the lunation for getting your systems in order — your routines, your health habits, your working methods. <strong>Set intentions around how you want to improve the quality of your everyday life.</strong> Virgo New Moon energy rewards discernment, attention to detail, and service. Small changes made with care now accumulate into meaningful results. What needs to be improved, organized, or healed?',
            ],
            [
                'key'  => 'new_moon_libra',
                'text' => 'A New Moon in Libra opens a cycle focused on <strong>relationships, balance, and the art of working with others</strong>. This is the lunation for setting intentions around partnership — romantic, professional, or social. <strong>What kind of connections do you want to cultivate?</strong> Libra New Moon energy favors diplomacy, fairness, and a genuine effort to meet others halfway. Contracts, collaborations, and commitments begun now benefit from careful attention. What do you want your closest relationships to look like?',
            ],
            [
                'key'  => 'new_moon_scorpio',
                'text' => 'A New Moon in Scorpio initiates a cycle of <strong>depth, transformation, and honest reckoning with what lies beneath the surface</strong>. This lunation does not do well with surface-level intentions — it asks what you are truly ready to change, release, or confront. <strong>Set intentions around letting go, deepening intimacy, or transforming something that no longer serves you.</strong> The seed planted under a Scorpio New Moon grows in the dark before it surfaces — trust the process even when you cannot yet see results.',
            ],
            [
                'key'  => 'new_moon_sagittarius',
                'text' => 'A New Moon in Sagittarius opens a cycle of <strong>expansion, exploration, and the search for meaning</strong>. This is the lunation for setting intentions around travel, higher learning, philosophical inquiry, or anything that broadens your world. <strong>What beliefs do you want to test? What horizons do you want to cross?</strong> Sagittarius New Moon energy favors optimism and the courage to aim higher than seems comfortable. The intentions set here tend to grow large — plant them deliberately and point them toward genuine growth.',
            ],
            [
                'key'  => 'new_moon_capricorn',
                'text' => 'A New Moon in Capricorn marks the most auspicious time of the year to <strong>set serious long-term intentions around career, ambition, and lasting achievement</strong>. This lunation rewards clear goals, realistic planning, and disciplined follow-through. <strong>What do you want to build over the next year — professionally and structurally?</strong> Capricorn New Moon energy favors those who take responsibility, commit to the long game, and are willing to work for what they want. The seeds planted here grow slowly but solidly.',
            ],
            [
                'key'  => 'new_moon_aquarius',
                'text' => 'A New Moon in Aquarius opens a cycle around <strong>community, innovation, and alignment with a larger vision</strong>. This lunation asks what you want to contribute to something beyond yourself — a group, a cause, a collective goal. <strong>Set intentions around your social networks, your long-term vision, and any area where you want to think differently.</strong> Aquarius New Moon energy favors originality and collaboration. The ideas born now can take on a life of their own when shared with the right people.',
            ],
            [
                'key'  => 'new_moon_pisces',
                'text' => 'A New Moon in Pisces invites you into the most inward and spiritually receptive lunation of the year. <strong>Intentions set here belong to the realm of the imaginal — dreams, compassion, creative vision, and spiritual connection.</strong> This is not the time for rigid goals but for opening to what wants to emerge through you. <strong>What do you want to dream into being?</strong> The Pisces New Moon rewards surrender over control. Let your intentions be fluid, spacious, and rooted in what genuinely moves you.',
            ],

            // ── Full Moons ───────────────────────────────────────────────

            [
                'key'  => 'full_moon_aries',
                'text' => 'A Full Moon in Aries illuminates the tension between <strong>your own needs and the needs of others</strong>. Something in the area of identity, independence, or personal direction reaches a peak — a decision, a confrontation, or a moment of clarity about what you truly want. <strong>This is a time for courageous honesty, not polite compromise.</strong> What has been building in you that needs to be expressed or acted upon? The Aries Full Moon rewards those who are honest about what they need and willing to claim it.',
            ],
            [
                'key'  => 'full_moon_taurus',
                'text' => 'A Full Moon in Taurus brings something around <strong>security, money, or personal values to a culminating point</strong>. What has been accumulating in your material life or your sense of self-worth now becomes visible — a decision about resources, a realization about what you truly value, or a completion in the realm of the physical. <strong>This lunation asks what you are ready to release in order to feel genuinely secure.</strong> The Taurus Full Moon rewards honest assessment of what you have and what is truly enough.',
            ],
            [
                'key'  => 'full_moon_gemini',
                'text' => 'A Full Moon in Gemini brings <strong>information, conversations, and mental activity to a peak</strong>. Something you have been thinking about, writing, or communicating reaches a turning point. A truth surfaces in dialogue, a decision crystallizes, or scattered threads come together into a clearer picture. <strong>This is a lunation for speaking what has been left unsaid and hearing what has been obscured.</strong> The Gemini Full Moon shines light on the stories you tell yourself — and asks which ones are still serving you.',
            ],
            [
                'key'  => 'full_moon_cancer',
                'text' => 'A Full Moon in Cancer illuminates the deepest layer of <strong>emotional life — home, family, and the need for belonging</strong>. Something that has been simmering beneath the surface of your domestic or private world comes to light. Feelings that have been suppressed rise up; family dynamics reach a point of resolution or tension. <strong>This lunation asks what you are holding onto emotionally that no longer serves you.</strong> The Cancer Full Moon rewards those who are willing to feel what is real and release what has been carried too long.',
            ],
            [
                'key'  => 'full_moon_leo',
                'text' => 'A Full Moon in Leo brings <strong>creative work, romantic situations, and the question of recognition to a culminating point</strong>. Something you have been expressing, creating, or performing reaches its fullest visibility now. A moment of applause, confrontation, or honest reckoning with whether you are showing up authentically. <strong>This lunation asks where you have been playing small or waiting for permission to shine.</strong> The Leo Full Moon rewards those who are willing to be seen fully — in both their strengths and their vulnerabilities.',
            ],
            [
                'key'  => 'full_moon_virgo',
                'text' => 'A Full Moon in Virgo illuminates the cumulative effect of <strong>daily habits, health choices, and the quality of your work</strong>. Something in the area of service, routine, or physical wellbeing reaches a peak — a health matter comes to light, a work situation reaches a decision point, or the gap between your intentions and your actual daily practices becomes undeniable. <strong>This lunation asks what needs to be cleaned up, corrected, or released in your day-to-day life.</strong> The results of your routines are on full display.',
            ],
            [
                'key'  => 'full_moon_libra',
                'text' => 'A Full Moon in Libra brings <strong>a significant relationship to a peak moment of clarity or decision</strong>. What has been developing in a partnership — romantic, professional, or legal — now surfaces fully. A conversation that has been avoided becomes necessary; a contract reaches completion; a balance that has been off demands correction. <strong>This lunation asks what is truly fair in your most important relationships.</strong> The Libra Full Moon rewards honest dialogue and genuine willingness to see the other person\'s perspective.',
            ],
            [
                'key'  => 'full_moon_scorpio',
                'text' => 'A Full Moon in Scorpio is among the most emotionally intense lunations of the year. <strong>What has been hidden, suppressed, or festering beneath the surface now emerges</strong> — in intimate relationships, shared finances, or the depths of your own psychology. This is not a comfortable lunation, but it is an honest one. <strong>What has been kept in the dark is asking to be acknowledged and released.</strong> The Scorpio Full Moon rewards those willing to face the truth without flinching — and that courage always brings genuine transformation.',
            ],
            [
                'key'  => 'full_moon_sagittarius',
                'text' => 'A Full Moon in Sagittarius illuminates the larger story of <strong>your beliefs, your search for meaning, and where your life is pointing</strong>. A philosophical question reaches a turning point; a journey — literal or inner — comes to a significant milestone; or the gap between what you believe and how you actually live becomes undeniable. <strong>This lunation asks whether your daily life is aligned with your highest values.</strong> The Sagittarius Full Moon rewards those willing to expand beyond the familiar and release what no longer fits the life they are growing into.',
            ],
            [
                'key'  => 'full_moon_capricorn',
                'text' => 'A Full Moon in Capricorn brings <strong>professional ambitions, public reputation, and long-term projects to a peak of visibility</strong>. Something in your career or your relationship with authority reaches a culminating point — recognition arrives, a responsibility peaks, or the cost of your ambition becomes fully apparent. <strong>This lunation asks what you have been building and whether it is worth the price.</strong> The Capricorn Full Moon rewards sustained effort and honest reckoning with results — and invites release of what has been carried out of obligation rather than genuine purpose.',
            ],
            [
                'key'  => 'full_moon_aquarius',
                'text' => 'A Full Moon in Aquarius brings <strong>community, collective goals, and your place within a larger whole to a point of illumination</strong>. Something in your social networks, group affiliations, or long-term vision reaches a peak — a friendship clarifies, a collective project culminates, or the question of where you truly belong demands an answer. <strong>This lunation asks what you are contributing to something beyond yourself.</strong> The Aquarius Full Moon rewards authenticity within community — and invites release of group loyalties that no longer reflect who you are becoming.',
            ],
            [
                'key'  => 'full_moon_pisces',
                'text' => 'A Full Moon in Pisces is the most spiritually charged lunation of the year. <strong>Dreams, intuitions, hidden feelings, and unfinished emotional business rise to the surface</strong>. Something in the realm of the invisible — grief, compassion, spiritual longing, creative vision — reaches its fullest expression. This is a powerful time for release, forgiveness, and letting go of what has been carried at a level deeper than words. <strong>The Pisces Full Moon rewards surrender and dissolves what has kept you separate from your own wholeness.</strong>',
            ],
        ];
    }
}
