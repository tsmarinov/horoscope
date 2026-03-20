<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored lunation house text blocks (~80-100 words each).
 *
 * Section: lunation_house
 * Keys:    new_moon_house_1 … new_moon_house_12
 *          full_moon_house_1 … full_moon_house_12
 * Total:   24 blocks
 */
class LunationHouseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'lunation_house',
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

            // ── New Moon ────────────────────────────────────────────────────

            [
                'key'  => 'new_moon_house_1',
                'text' => 'A new cycle is opening around your sense of self and how you present to the world. This is the right moment to redefine how you want to be seen and to take deliberate steps toward a fresh start in your personal goals. <strong>Physical changes, new habits, and shifts in your self-image</strong> are all supported now. What you begin under this lunation has a direct impact on your confidence and direction for the next six months.',
            ],
            [
                'key'  => 'new_moon_house_2',
                'text' => 'Your financial picture and sense of personal worth are entering a new chapter. This lunation plants seeds around <strong>income, resources, and what you truly value</strong> — it\'s the moment to start building something that will grow steadily rather than chasing quick results. A practical new step in managing money, establishing a skill, or clarifying what security means to you personally will have lasting effects over the coming months.',
            ],
            [
                'key'  => 'new_moon_house_3',
                'text' => 'Your mind is ready for new inputs and your communication style is shifting. This is an excellent time to <strong>start a course, launch a project involving writing or speaking</strong>, or open honest conversations with people in your immediate environment. Short trips, new learning experiences, and reconnecting with siblings or neighbors may carry unusual significance now. What you plant in conversation and learning at this point will take root in meaningful ways.',
            ],
            [
                'key'  => 'new_moon_house_4',
                'text' => 'A fresh chapter is beginning in your domestic life and inner world. Whether that means <strong>a change of home, a shift in family dynamics</strong>, or a renewed commitment to building emotional security, this lunation asks you to look at your roots. What you choose to build or let go of in your home environment now will shape your sense of belonging for months to come. Private matters deserve deliberate attention.',
            ],
            [
                'key'  => 'new_moon_house_5',
                'text' => 'A new creative or romantic cycle is opening. This lunation brings <strong>fresh energy to self-expression, passion, and pleasure</strong> — it\'s the right moment to begin a creative project, allow yourself to be seen fully in a relationship, or bring more play and joy into your daily life. Children, art, and anything that makes your heart lighter all carry special significance now. What you initiate here connects directly to your happiness.',
            ],
            [
                'key'  => 'new_moon_house_6',
                'text' => 'Your daily routines and health habits are ready for a reset. This lunation opens a new cycle around <strong>work, service, and physical wellbeing</strong> — an ideal moment to start a new diet, organize your work environment, or establish habits that will support your energy long-term. Small, consistent changes begun now will compound into significant improvements. Pay attention to your body\'s signals and don\'t dismiss minor issues before they develop further.',
            ],
            [
                'key'  => 'new_moon_house_7',
                'text' => 'A significant new chapter in your one-on-one relationships is beginning. Whether in <strong>romantic partnership, business collaboration, or important agreements</strong>, this lunation plants seeds that will mature over the next six months. The people you meet or commit to now carry lasting significance. If you\'ve been considering a serious conversation with a partner or are ready to formalize a collaboration, this is the most supportive moment to take that step.',
            ],
            [
                'key'  => 'new_moon_house_8',
                'text' => 'A new cycle around <strong>deep transformation, shared resources, and what lies beneath the surface</strong> is beginning. Financial matters involving joint money, inheritance, or debt may need fresh attention. On a deeper level, this lunation invites you to release something that no longer serves you and commit to genuine change. What you begin here has the potential to fundamentally alter how you use power, handle intimacy, and relate to loss and renewal.',
            ],
            [
                'key'  => 'new_moon_house_9',
                'text' => 'Your beliefs, worldview, and hunger for broader experience are entering a fresh cycle. This lunation supports <strong>travel, higher education, publishing, or any pursuit that expands your horizons</strong> beyond the familiar. A new philosophy or spiritual direction may begin taking shape now. Whether you\'re planning a journey, starting a course of study, or simply opening your mind to different perspectives, what you initiate carries real weight for your long-term growth.',
            ],
            [
                'key'  => 'new_moon_house_10',
                'text' => 'A major new chapter in your professional life and public reputation is beginning. This lunation plants seeds around <strong>career direction, achievements, and how you\'re perceived by the world</strong>. Goals you set now and steps you take toward your ambitions have greater traction than usual. If you\'ve been waiting for the right moment to make a career move, pursue recognition, or clarify your professional direction, this lunation gives it meaningful momentum.',
            ],
            [
                'key'  => 'new_moon_house_11',
                'text' => 'A fresh cycle is opening around <strong>friendships, group affiliations, and your hopes for the future</strong>. This is the right time to join a new community, strengthen bonds with people who share your values, or clarify what you truly want from the years ahead. Social connections formed under this lunation often turn out to be meaningful and lasting. A dream or long-term goal that\'s been forming in the background is ready to move from the conceptual stage to the practical.',
            ],
            [
                'key'  => 'new_moon_house_12',
                'text' => 'A new cycle is beginning in the hidden, private areas of your life. This lunation brings <strong>inner work, spiritual renewal, and a clearing of old burdens</strong> to the foreground. What you choose to let go of, process, or release over the coming weeks will free up significant inner resources. Solitude, contemplation, and honest self-examination are more productive than external activity right now. Trust what surfaces from beneath the surface — it\'s asking to be acknowledged.',
            ],

            // ── Full Moon ───────────────────────────────────────────────────

            [
                'key'  => 'full_moon_house_1',
                'text' => 'Something significant about your identity and self-presentation is coming to a head. A situation that has been developing is now reaching a <strong>turning point involving your confidence, independence, or personal direction</strong>. Others see you clearly now — which can bring recognition but also reveals whatever you\'ve been projecting unconsciously. Decisions made at this peak about how you want to show up in the world will carry lasting weight. Your personal needs deserve to come first.',
            ],
            [
                'key'  => 'full_moon_house_2',
                'text' => 'A financial matter or question of personal value is reaching its peak. Something involving <strong>money, possessions, or what you\'re truly worth</strong> is being illuminated — this might mean a payment arriving, a resource reaching its limit, or a clearer picture of your actual financial situation. What you\'ve built (or failed to build) around security and income becomes visible now. This is a moment for clarity, not new spending — use what this full moon reveals to adjust your approach.',
            ],
            [
                'key'  => 'full_moon_house_3',
                'text' => 'A conversation, agreement, or learning process is reaching its culmination. Something that has been said or left unsaid is <strong>demanding honest attention now</strong>. The full moon illuminates your immediate environment — siblings, neighbors, short journeys, messages, and everyday exchanges. A truth that\'s been circling may finally land clearly. This is a moment to <strong>complete a piece of writing, resolve a local dispute, or have the direct conversation</strong> you\'ve been avoiding.',
            ],
            [
                'key'  => 'full_moon_house_4',
                'text' => 'Your home life and emotional foundations are under full illumination. A situation within the family or domestic sphere is reaching a point where <strong>something must be acknowledged, resolved, or released</strong>. Old patterns around belonging, security, or private matters surface with unusual clarity. This full moon often coincides with a change of residence, a family conversation that\'s been overdue, or a significant shift in how you feel about where you belong.',
            ],
            [
                'key'  => 'full_moon_house_5',
                'text' => 'A creative work, romantic situation, or matter involving children is reaching its peak. What began months ago in your love life or creative endeavors is now <strong>ready for a culmination</strong> — a relationship becoming more defined, a project completing, or a moment of genuine joy or heartbreak making itself felt. The full moon here amplifies feelings of passion and desire. Be honest about what and who actually brings you happiness, rather than what you think should.',
            ],
            [
                'key'  => 'full_moon_house_6',
                'text' => 'A health situation or work matter is coming to a head. Something in your <strong>daily routines, workplace dynamics, or physical wellbeing</strong> can no longer be deferred — it needs attention and resolution. Overwork, health imbalances, or conflicts with colleagues become impossible to ignore under this full moon. This is the moment to address what your body has been signaling, complete a work project, or acknowledge a work dynamic that isn\'t sustainable.',
            ],
            [
                'key'  => 'full_moon_house_7',
                'text' => 'A significant relationship is reaching a turning point. Something between you and a <strong>partner, collaborator, or significant other</strong> is being fully illuminated — what\'s been working, what hasn\'t, and what needs to change. This full moon often brings a relationship decision to the surface: a commitment deepening, a conflict coming to a head, or a partnership reaching its natural conclusion. See clearly what\'s actually in front of you, not what you\'ve hoped it would become.',
            ],
            [
                'key'  => 'full_moon_house_8',
                'text' => 'A deep transformation is reaching its peak. Something involving <strong>joint finances, emotional power dynamics, or a significant inner change</strong> is being exposed to full light. Secrets, buried feelings, or unresolved matters around shared resources may surface now with unusual intensity. This full moon asks you to <strong>face something that has been avoided</strong> — in money, intimacy, or your relationship with loss and change. What is released here genuinely frees you.',
            ],
            [
                'key'  => 'full_moon_house_9',
                'text' => 'A belief, journey, or educational matter is reaching its culmination. Something you\'ve been learning, travelling toward, or coming to believe is now <strong>reaching a point of clarity or completion</strong>. A trip may end or reach its destination. A long-held belief may be challenged or confirmed. This full moon illuminates your relationship with truth, freedom, and meaning — what expands you and what has been keeping you intellectually or spiritually confined.',
            ],
            [
                'key'  => 'full_moon_house_10',
                'text' => 'Your professional life and public reputation are under full illumination. A <strong>career achievement, recognition, or significant turn in your public standing</strong> is reaching its peak. This is often the full moon associated with promotions, public moments, or a situation at work coming to a decisive point. How others see you professionally is clarified now — for better or worse. What you\'ve built toward is visible, and so are the gaps between your ambitions and your current reality.',
            ],
            [
                'key'  => 'full_moon_house_11',
                'text' => 'A friendship, group affiliation, or long-held hope is reaching its culmination. Something that began as a shared goal or community connection is now <strong>showing its true shape</strong> — whether that means a meaningful bond being cemented or a misalignment in values becoming clear. This full moon often brings a resolution around social belonging: who your people actually are, which dreams remain worth pursuing, and which ones need to be updated based on who you\'ve become.',
            ],
            [
                'key'  => 'full_moon_house_12',
                'text' => 'Something hidden is surfacing. This full moon illuminates <strong>the private, unconscious, and carefully concealed aspects of your life</strong> — old emotional patterns, self-sabotaging habits, or matters you\'ve kept from even yourself. This is one of the most psychologically significant lunations and often coincides with a release of something that has been draining you in private. What comes to light now, though it may be uncomfortable, ultimately creates space for genuine renewal.',
            ],
        ];
    }
}
