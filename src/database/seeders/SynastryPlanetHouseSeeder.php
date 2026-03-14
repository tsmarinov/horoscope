<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored synastry planet-in-house overlay text blocks.
 *
 * Section: synastry_planet_house
 * Keys:    sun_house_1  … sun_house_12
 *          moon_house_1 … moon_house_12
 * Total:   24 blocks
 *
 * Placeholders substituted at render time:
 *   :owner  = person whose planet it is  (e.g. "Ivan")
 *   :other  = person whose house it is   (e.g. "Maria")
 *   :owner's / :other's = possessive forms
 */
class SynastryPlanetHouseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'synastry_planet_house',
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

            // ── Sun in houses ────────────────────────────────────────────────

            [
                'key'  => 'sun_house_1',
                'text' => ':owner\'s <em>Sun</em> falls in :other\'s first house, bringing :owner\'s solar energy directly into :other\'s sense of self and how :other presents to the world. :owner\'s vitality and confidence shine a light on :other\'s identity — :other may feel more visible, more alive, or more self-aware in :owner\'s presence. <strong>:owner naturally brings out :other\'s best qualities and can strengthen :other\'s self-expression</strong>, though at times :owner\'s presence may feel as though :owner is outshining rather than supporting :other.',
            ],
            [
                'key'  => 'sun_house_2',
                'text' => ':owner\'s <em>Sun</em> illuminates :other\'s second house of resources, values, and material security. :owner\'s presence can motivate :other to earn more, build more, or clarify what :other truly values — :owner may introduce :other to new ways of generating income or help :other see :other\'s own worth more clearly. <strong>:owner has a way of making the material world feel more abundant and purposeful</strong> when they are together, though dependency around money or resources is something to guard against.',
            ],
            [
                'key'  => 'sun_house_3',
                'text' => ':owner\'s <em>Sun</em> lights up :other\'s third house of communication, learning, and daily exchange. In :owner\'s company :other may feel sharper, more articulate, and more intellectually engaged — :owner has a way of <strong>stimulating :other\'s curiosity and making ordinary conversation feel genuinely alive.</strong> Ideas flow more freely between them, and :owner may encourage :other to write, speak, or learn something new. This relationship thrives on consistent mental exchange.',
            ],
            [
                'key'  => 'sun_house_4',
                'text' => ':owner\'s <em>Sun</em> falls in :other\'s fourth house, the area of home, family, and emotional roots. :owner\'s presence has a direct effect on :other\'s sense of security and belonging — :other may feel at home with :owner in a way that is difficult to explain. <strong>:owner can illuminate what is unresolved in :other\'s private world</strong> and may trigger a desire to build something lasting together. Family life, shared space, and the past all become significant themes in this relationship.',
            ],
            [
                'key'  => 'sun_house_5',
                'text' => ':owner\'s <em>Sun</em> energizes :other\'s fifth house of creativity, romance, and joy. In :owner\'s presence :other feels more playful, more expressive, and more alive to pleasure — <strong>:owner brings out the part of :other that wants to create, fall in love, and enjoy being alive.</strong> This is one of the most naturally romantic overlays in synastry and often marks relationships that feel luminous and exciting. The risk is expecting that high to sustain itself indefinitely.',
            ],
            [
                'key'  => 'sun_house_6',
                'text' => ':owner\'s <em>Sun</em> falls in :other\'s sixth house of daily routine, health, and service. :owner\'s influence tends to be practical and organizing — :owner helps :other function better, establish better habits, or pay attention to :other\'s health in ways :other might otherwise neglect. <strong>:owner has a steady, grounding presence in the day-to-day texture of :other\'s life</strong>, though this overlay can feel more workmanlike than romantic if other chart connections don\'t provide warmth and depth.',
            ],
            [
                'key'  => 'sun_house_7',
                'text' => ':owner\'s <em>Sun</em> falls directly in :other\'s seventh house of partnership, commitment, and significant relationships. This is one of the strongest indicators of a relationship that feels fated or significant — <strong>:other sees in :owner what a genuine partner looks and feels like</strong>, and :owner illuminates everything :other wants and needs in a committed bond. Long-term relationship potential is high, though the seventh house also asks :other to see :owner clearly rather than through projection.',
            ],
            [
                'key'  => 'sun_house_8',
                'text' => ':owner\'s <em>Sun</em> enters :other\'s eighth house of transformation, shared resources, and depth. This is an intense overlay — :owner\'s presence reaches below the surface into :other\'s psychological depths, bringing up what is hidden and asking for genuine change. <strong>:owner has a catalytic effect on :other\'s inner life</strong>, for better or worse. This connection rarely remains shallow and often involves significant entanglement around money, sexuality, or emotional power. Not easy, but rarely forgettable.',
            ],
            [
                'key'  => 'sun_house_9',
                'text' => ':owner\'s <em>Sun</em> illuminates :other\'s ninth house of belief, travel, and expansion. :owner\'s energy broadens :other\'s world — being with :owner makes :other feel that larger possibilities exist, that there is more to discover and to believe in. <strong>:owner can be a teacher, a philosophical companion, or a catalyst for experiences both literal and internal.</strong> Relationships with this overlay often feel like they are going somewhere, oriented toward growth rather than mere comfort.',
            ],
            [
                'key'  => 'sun_house_10',
                'text' => ':owner\'s <em>Sun</em> falls in :other\'s tenth house of career, ambition, and public life. :owner\'s presence directly affects :other\'s professional world — :owner may support :other\'s career, introduce :other to important people, or help :other clarify :other\'s ambitions. <strong>:owner has a way of taking :other seriously in the world and encouraging :other to do the same.</strong> This overlay is common in significant professional partnerships and in relationships where one person actively supports the other\'s public life.',
            ],
            [
                'key'  => 'sun_house_11',
                'text' => ':owner\'s <em>Sun</em> energizes :other\'s eleventh house of friendships, community, and long-term hopes. :owner\'s presence expands :other\'s social world, connects :other to new groups or communities, and reignites :other\'s sense of what :other is working toward in the longer term. <strong>:owner encourages the social and idealistic side of :other\'s nature</strong> and may introduce :other to people or ideas that significantly alter :other\'s direction. This overlay is common in friendships that feel genuinely meaningful.',
            ],
            [
                'key'  => 'sun_house_12',
                'text' => ':owner\'s <em>Sun</em> falls in :other\'s twelfth house, the area of hidden life, solitude, and the unconscious. This is one of the most complex and deeply felt overlays in synastry. :owner has a way of <strong>reaching parts of :other that :other doesn\'t ordinarily show</strong> — :owner sees through :other\'s public persona and into something more private and essential. This can feel both profoundly intimate and unsettling. The relationship has a quality of the sacred and the difficult simultaneously.',
            ],

            // ── Moon in houses ───────────────────────────────────────────────

            [
                'key'  => 'moon_house_1',
                'text' => ':owner\'s <em>Moon</em> falls in :other\'s first house, placing :owner\'s emotional world directly in contact with how :other presents to others. :owner is deeply attuned to :other\'s moods and personal energy — in :owner\'s presence :other may feel unusually understood or, at times, emotionally exposed. <strong>:owner brings nurturing, warmth, and instinctive care to :other\'s sense of self</strong>, making this one of the most emotionally resonant placements in synastry. :other often feels most themselves when :owner is nearby.',
            ],
            [
                'key'  => 'moon_house_2',
                'text' => ':owner\'s <em>Moon</em> lands in :other\'s second house of material security and personal values. Emotionally, :owner is invested in :other\'s wellbeing and stability — :owner may express care through practical support, gifts, or helping :other manage resources. <strong>:owner\'s nurturing instincts connect naturally to the things :other values most</strong>, which can make the relationship feel safe and grounding. Watch for emotional dynamics around money or possessions becoming a substitute for deeper connection.',
            ],
            [
                'key'  => 'moon_house_3',
                'text' => ':owner\'s <em>Moon</em> falls in :other\'s third house, weaving :owner\'s emotional life into :other\'s daily communication and mental world. They understand each other easily — conversations feel natural, and there is an instinctive sense of being on the same wavelength. <strong>:owner brings emotional warmth to every exchange</strong>, making even ordinary talk feel nourishing. This overlay strengthens bonds between people who spend regular time together in close proximity.',
            ],
            [
                'key'  => 'moon_house_4',
                'text' => ':owner\'s <em>Moon</em> lands in :other\'s fourth house — the area of home, roots, and emotional foundations. This is one of the most significant lunar overlays: <strong>:owner feels like family to :other in an immediate, instinctive way</strong>, and the pull toward building a shared domestic life together can be strong. Emotional safety comes naturally in this relationship, though old family patterns and childhood dynamics may surface more readily with :owner than with others.',
            ],
            [
                'key'  => 'moon_house_5',
                'text' => ':owner\'s <em>Moon</em> falls in :other\'s fifth house of pleasure, creativity, and romance. :owner\'s emotional nature resonates directly with :other\'s capacity for joy — <strong>:other feels genuinely happy around :owner, more playful and alive than usual</strong>, and :owner brings out a childlike, open quality in :other\'s emotional expression. This is a warm and affectionate overlay that supports both romantic love and close friendship. :owner genuinely delights in who :other is.',
            ],
            [
                'key'  => 'moon_house_6',
                'text' => ':owner\'s <em>Moon</em> enters :other\'s sixth house of daily routine, health, and practical service. :owner\'s nurturing expresses itself in practical ways — through helping, organizing, and caring for :other\'s wellbeing in tangible terms. <strong>:owner has a way of making :other\'s everyday life feel more supported and managed</strong>, though this dynamic can tip into caretaking if not kept in balance. This overlay often appears in relationships with a strong domestic or working partnership dimension.',
            ],
            [
                'key'  => 'moon_house_7',
                'text' => ':owner\'s <em>Moon</em> falls in :other\'s seventh house of partnership and commitment. Emotionally, :owner meets :other exactly where :other needs a partner — <strong>:owner reflects back the kind of attunement :other genuinely seeks in a significant relationship.</strong> This overlay can create a sense of deep recognition in one-on-one bonds and is one of the strongest indicators of emotional compatibility in long-term partnerships. The relationship feels like it was meant to happen.',
            ],
            [
                'key'  => 'moon_house_8',
                'text' => ':owner\'s <em>Moon</em> enters :other\'s eighth house — the area of depth, transformation, and psychological complexity. This overlay creates profound emotional intensity: <strong>:owner touches :other\'s most guarded inner world</strong>, and the relationship can feel both deeply intimate and psychologically exposing. Emotional patterns that ordinarily remain below the surface tend to be activated here, for the purpose of being worked through. This is a difficult and deeply nourishing overlay.',
            ],
            [
                'key'  => 'moon_house_9',
                'text' => ':owner\'s <em>Moon</em> falls in :other\'s ninth house of belief and expansion. :owner\'s emotional world connects naturally to :other\'s sense of meaning, adventure, and philosophical outlook — <strong>:owner feels like someone who broadens :other\'s emotional horizons and validates :other\'s need for freedom and growth.</strong> The relationship has an expansive quality and may involve travel, shared beliefs, or a mutual orientation toward something larger than everyday life.',
            ],
            [
                'key'  => 'moon_house_10',
                'text' => ':owner\'s <em>Moon</em> lands in :other\'s tenth house of career and public life. :owner\'s emotional investment in :other directly connects to :other\'s professional ambitions — <strong>:owner cares genuinely about :other\'s success and may play an active role in supporting :other\'s public standing.</strong> This overlay often appears in significant mentoring relationships or in partnerships where one person has a real emotional stake in the other\'s career.',
            ],
            [
                'key'  => 'moon_house_11',
                'text' => ':owner\'s <em>Moon</em> falls in :other\'s eleventh house of friendship, community, and future hopes. <strong>:owner feels like a true friend</strong> — someone who genuinely wishes :other well and supports :other\'s long-term dreams without agenda. This is one of the warmest and most emotionally uncomplicated overlays in synastry. The relationship has a quality of easy affection and mutual belonging, and :owner naturally fits into the broader social world that matters to :other.',
            ],
            [
                'key'  => 'moon_house_12',
                'text' => ':owner\'s <em>Moon</em> enters :other\'s twelfth house — the hidden, private, and unconscious realm. This is an unusually intimate overlay: <strong>:owner reaches :other in a deeply private way</strong>, touching emotions and memories that rarely surface in ordinary relationships. The bond can feel profound, spiritually significant, or simply unlike any other connection they have. There is a nurturing quality here, but also a risk of emotional blurring if boundaries are not maintained.',
            ],

        ];
    }
}
