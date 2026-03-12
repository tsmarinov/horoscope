<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Solar Return interpretation text blocks — manually authored, 1 variant.
 *
 * solar_asc_house (12 blocks):
 *   Key: solar_asc_natal_house_{1..12}
 *   Meaning: Solar ASC falls in natal house N → year's main theme
 *
 * solar_dispositor_house (12 blocks):
 *   Key: solar_dispositor_natal_house_{1..12}
 *   Meaning: Dispositor of Solar ASC in natal house N → how theme expresses
 *
 * Total: 24 blocks
 */
class SolarReturnTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (['solar_asc_house' => $this->ascBlocks(), 'solar_dispositor_house' => $this->dispositorBlocks()] as $section => $blocks) {
            $rows = array_map(fn ($r) => array_merge($r, [
                'section'    => $section,
                'language'   => 'en',
                'variant'    => 1,
                'tone'       => 'neutral',
                'tokens_in'  => 0,
                'tokens_out' => 0,
                'cost_usd'   => 0.0,
                'created_at' => $now,
                'updated_at' => $now,
            ]), $blocks);

            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table('text_blocks')->upsert(
                    $chunk,
                    ['key', 'section', 'language', 'variant'],
                    ['text', 'tone', 'updated_at']
                );
            }
        }
    }

    // ── Solar ASC in natal house ──────────────────────────────────────────

    private function ascBlocks(): array
    {
        return [
            [
                'key'  => 'solar_asc_natal_house_1',
                'text' => 'The year is strongly focused on you — your identity, physical body, and personal direction take center stage. This Solar Return Ascendant placement marks a year of new beginnings where your personal initiative sets the tone for everything else. Changes in your appearance, health habits, or personal direction may feel especially meaningful. <strong>You are the central actor in your own story this year</strong>, and how you define yourself now shapes the months ahead.',
            ],
            [
                'key'  => 'solar_asc_natal_house_2',
                'text' => 'The year centers around your financial security and personal values. This Solar Return Ascendant in your natal second house indicates that <strong>money, possessions, and what you truly value</strong> become the dominant theme. Whether building income, reassessing what you own, or clarifying what gives your life meaning, material and psychological security are the year\'s main curriculum. The question running through the year is: what do you actually need to feel stable and content?',
            ],
            [
                'key'  => 'solar_asc_natal_house_3',
                'text' => 'Your mind, voice, and immediate environment take the lead this year. <strong>Communication, learning, short travel, and relationships with siblings or neighbors</strong> define the year\'s texture. This is a mentally active period where ideas flow readily, connections multiply, and daily exchanges carry more weight than usual. Writing, speaking, teaching, or study may become more central to your life. The clarity of your everyday conversations will determine much of what this year achieves.',
            ],
            [
                'key'  => 'solar_asc_natal_house_4',
                'text' => 'The year turns inward, toward home, family, and your emotional foundations. <strong>Domestic changes, family dynamics, and questions of belonging</strong> are the year\'s main themes. This may manifest as a move, renovation, shift in family relationships, or a deeper process of returning to your roots. Security and private life become more important than public achievement. <strong>Building a stable inner foundation</strong> is the work of this year — everything else flows from whether you feel at home within yourself.',
            ],
            [
                'key'  => 'solar_asc_natal_house_5',
                'text' => 'This is a year rich in <strong>creative energy, romance, joy, and self-expression</strong>. The Solar Return Ascendant here turns the year into a celebration of what makes life worth living — love affairs, artistic projects, children, and anything that sparks genuine enthusiasm. You\'re more visible, more playful, and more willing to take creative risks. <strong>Following what genuinely delights you</strong> is not a luxury this year — it\'s the engine that drives everything forward.',
            ],
            [
                'key'  => 'solar_asc_natal_house_6',
                'text' => 'The year is shaped by <strong>work, health, and the discipline of daily life</strong>. Routines, service, and practical improvement are the year\'s defining themes. This is a productive year for establishing habits that serve your long-term wellbeing — physical, professional, and organizational. Work demands may increase, or you may be drawn to refine the way you operate day to day. <strong>Small, consistent actions compound into significant change</strong> under this placement.',
            ],
            [
                'key'  => 'solar_asc_natal_house_7',
                'text' => 'Relationships take center stage this year. A <strong>significant partnership — romantic, professional, or legal</strong> — becomes the year\'s defining context. You\'re learning about yourself through others, and the quality of your close relationships will determine much of what this year means to you. Important people enter your life, existing bonds deepen or clarify. <strong>How you show up for others and what you ask in return</strong> is the year\'s central question.',
            ],
            [
                'key'  => 'solar_asc_natal_house_8',
                'text' => 'This is a year of <strong>deep transformation, intensity, and confronting what lies beneath the surface</strong>. Shared finances, intimacy, power dynamics, and the process of letting go become central themes. This year doesn\'t allow you to remain on the surface — something significant asks to be changed at a fundamental level. <strong>What you release this year frees up real energy for what comes next</strong> — the depth of the change determines the scale of the renewal.',
            ],
            [
                'key'  => 'solar_asc_natal_house_9',
                'text' => 'The year expands your world. <strong>Travel, higher education, philosophy, publishing, and the search for meaning</strong> define this Solar Return placement. You\'re being called to move beyond your existing comfort zone — geographically, intellectually, or spiritually. Encounters with different cultures, belief systems, or teachers may shift your worldview. This is a year for broadening your horizons and committing to growth that goes beyond the immediate and practical.',
            ],
            [
                'key'  => 'solar_asc_natal_house_10',
                'text' => 'Your <strong>career, public reputation, and life direction</strong> are the year\'s main focus. This is a year when your professional life demands attention and offers genuine opportunities for advancement. How you\'re perceived in the world matters more than usual, and your ambitions become more concrete. <strong>Steps taken toward your professional goals this year carry unusual weight</strong> and may shift your trajectory for years ahead. Your reputation is being built — or rebuilt — in real time.',
            ],
            [
                'key'  => 'solar_asc_natal_house_11',
                'text' => 'The year is shaped by <strong>friendships, communities, collective goals, and your vision for the future</strong>. Social connections carry unusual significance — who you spend time with and what groups you belong to will leave a lasting mark. Long-term goals that have been forming in the background come into sharper focus. This is an excellent year for collaborative projects, finding your people, and <strong>aligning your daily choices with your larger vision</strong> for where your life is going.',
            ],
            [
                'key'  => 'solar_asc_natal_house_12',
                'text' => 'This is a year of <strong>inner work, retreat, and significant invisible processes</strong>. What happens beneath the surface — in dreams, in private, in the quiet hours — carries more weight than external events. Old patterns, unresolved matters, and hidden fears may surface to be addressed. This is not primarily a year of outer achievement, but of <strong>clearing what has accumulated</strong> so that the next cycle can begin on genuinely clean ground.',
            ],
        ];
    }

    // ── Dispositor of Solar ASC in natal house ────────────────────────────

    private function dispositorBlocks(): array
    {
        return [
            [
                'key'  => 'solar_dispositor_natal_house_1',
                'text' => 'With the dispositor in the natal first house, <strong>the year\'s theme expresses directly through you</strong> — your body, initiative, and personal presence. The energy of this Solar Return moves through your identity and physical self, making personal action and self-development the primary vehicle. What you do about yourself this year — how you take care of your health, how you project confidence, how you begin new things — <strong>becomes the key that unlocks the year\'s potential</strong>.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_2',
                'text' => 'The dispositor in the natal second house grounds the year\'s theme in <strong>practical resources and personal values</strong>. Whatever the year\'s main focus, it will be realized through money, material security, or a clearer sense of what you truly value. Financial decisions and questions of self-worth become the channel through which the year\'s larger themes express themselves. <strong>Building a stable material base</strong> is the practical work that makes everything else possible.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_3',
                'text' => 'The dispositor in the third house routes the year\'s energy through <strong>communication, learning, and your immediate environment</strong>. The year\'s themes will be worked out through conversations, ideas, short trips, and relationships with those nearby. Writing, speaking, and learning become key tools. Pay attention to what you\'re saying and to whom — <strong>the quality of your everyday communication</strong> is the mechanism through which this year\'s main opportunities and challenges will arrive.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_4',
                'text' => 'With the dispositor in the natal fourth house, the year\'s themes ultimately <strong>root back to home and family</strong>. No matter what the outer focus, the inner work is about emotional security, private life, and your relationship with your roots. Domestic circumstances become the context in which the year\'s larger themes play out. <strong>Building something at home</strong> — literally or emotionally — is what gives this year\'s events their lasting meaning.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_5',
                'text' => 'The dispositor in the fifth house channels the year through <strong>creative expression, romance, and joy</strong>. Whatever the year is mainly about, it will find its fullest expression through playfulness, passion, and authentic self-expression. Love relationships, creative projects, or time spent with children become the arena where this year\'s themes become most vivid. <strong>Following what genuinely excites you</strong> is the engine, not a distraction from the year\'s real work.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_6',
                'text' => 'The dispositor in the sixth house means the year\'s themes <strong>express through daily work, health, and service</strong>. The practical details of how you live — your routines, habits, and professional responsibilities — become the vehicle. This placement often indicates that the year\'s growth happens quietly, through consistent effort rather than dramatic events. <strong>Your daily practices are the mechanism</strong> — what you do regularly and carefully will determine whether the year\'s potential is realized.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_7',
                'text' => 'With the dispositor in the seventh house, the year\'s energy <strong>moves through relationships and significant others</strong>. Partners, collaborators, and one-on-one connections become the primary context. Whatever the year\'s main theme, it will be activated, tested, or fulfilled through your closest relationships. Pay close attention to who enters your life this year — <strong>other people are the mirror and the mechanism</strong> for this year\'s growth.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_8',
                'text' => 'The dispositor in the eighth house directs the year\'s themes through <strong>transformation, depth, and shared resources</strong>. The year\'s energy doesn\'t stay on the surface — it moves through intensity, vulnerability, and genuine change. Joint finances, intimate relationships, and psychological processes become the channel. <strong>Something must be fundamentally transformed</strong> for the year\'s potential to be reached. What you\'re willing to surrender determines what becomes available.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_9',
                'text' => 'With the dispositor in the ninth house, the year\'s themes <strong>express through expansion, belief, and exploration</strong>. Travel, education, spiritual development, or philosophical inquiry become the vehicle through which the year\'s energy flows. Encounters with the unfamiliar — different places, ideas, or worldviews — are how this year\'s main work gets done. <strong>Following curiosity into unfamiliar territory</strong> is not a detour; it\'s the path.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_10',
                'text' => 'The dispositor in the tenth house brings the year\'s themes into <strong>public life and professional achievement</strong>. The year\'s energy channels through career, reputation, and your relationship with authority and ambition. Whatever the year is mainly about, it will be expressed — or tested — in your professional sphere. <strong>Public action and professional commitment</strong> are the year\'s main instruments, and how you handle responsibility now shapes your longer-term trajectory.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_11',
                'text' => 'With the dispositor in the eleventh house, the year\'s themes <strong>express through community, collective goals, and the future</strong>. Social networks, group affiliations, and long-term aspirations become the channel. The year\'s energy flows most productively when you\'re working toward shared goals with others who share your values. <strong>Friendships and alliances</strong> are not just support — they\'re the mechanism through which this year\'s potential gets realized.',
            ],
            [
                'key'  => 'solar_dispositor_natal_house_12',
                'text' => 'The dispositor in the twelfth house routes the year\'s energy through <strong>the hidden, the private, and the unconscious</strong>. The year\'s most important work happens away from public view — in solitude, in dreams, in the quiet processing of what has been. <strong>Invisible effort accumulates into real results</strong> under this placement, but it requires patience and a willingness to work without immediate external validation. What you release privately creates space for the next cycle.',
            ],
        ];
    }
}
