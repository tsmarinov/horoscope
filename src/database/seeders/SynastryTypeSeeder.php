<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored synastry relationship-type description text blocks.
 *
 * Section: synastry_type
 * Keys:    general, romantic, business, friends, family,
 *          spiritual, communication, emotion, sexual, creative
 * Total:   10 blocks
 */
class SynastryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'synastry_type',
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

            [
                'key'  => 'general',
                'text' => 'The overall compatibility between two people — the baseline energy and ease of their relationship. This score reflects the full picture: how naturally they understand each other, the balance between harmony and friction in their charts, and whether the planets they carry support or challenge one another. It is the foundation all other scores are built on.',
            ],
            [
                'key'  => 'romantic',
                'text' => 'The romantic and emotional chemistry between two people — attraction, tenderness, and the possibility of lasting love. This score weighs how well their <em>Venus</em>, <em>Mars</em>, <em>Sun</em>, and <em>Moon</em> interact: whether desire has staying power, whether emotional understanding runs deep, and whether the relationship has the architecture for genuine intimacy rather than just initial spark.',
            ],
            [
                'key'  => 'business',
                'text' => 'The professional compatibility between two people — shared drive, complementary strengths, and the ability to build something together with discipline and trust. This score reflects how well their <em>Saturn</em>, <em>Jupiter</em>, and <em>Sun</em> energies align: whether they can commit to a common goal, whether their working styles are compatible, and whether the relationship creates more than either could achieve alone.',
            ],
            [
                'key'  => 'friends',
                'text' => 'The ease and warmth of a friendship between two people — natural chemistry, shared humor, and genuine affection without the weight of expectation. This score weighs <em>Venus</em>, <em>Moon</em>, and <em>Mercury</em> connections: how effortlessly they enjoy each other\'s company, whether the emotional support flows naturally both ways, and whether their social energies complement rather than exhaust each other.',
            ],
            [
                'key'  => 'family',
                'text' => 'The quality of the kinship bond between two people — loyalty, unconditional care, and the deep familiarity that survives difficulty. This score reflects <em>Moon</em>, <em>Saturn</em>, and <em>Venus</em> interactions: how strongly each person feels a sense of belonging with the other, whether protective instincts are genuine rather than controlling, and whether the relationship holds together under the pressures that test all real family ties.',
            ],
            [
                'key'  => 'spiritual',
                'text' => 'The depth of karmic and soul-level connection between two people — the sense that their meeting carries meaning beyond the ordinary. This score weighs <em>Neptune</em>, <em>Pluto</em>, and the <em>North Node</em>: whether there is a genuine spiritual resonance, a sense of unfinished business from the past, or a shared orientation toward growth that transcends the practical dimensions of the relationship.',
            ],
            [
                'key'  => 'communication',
                'text' => 'How naturally two people understand each other — the ease of conversation, the speed of mental rapport, and the quality of everyday exchange. This score reflects <em>Mercury</em> and <em>Sun</em> connections: whether ideas flow freely between them, whether they listen as well as they speak, and whether misunderstandings are rare or a recurring source of friction that erodes the relationship over time.',
            ],
            [
                'key'  => 'emotion',
                'text' => 'The emotional attunement between two people — empathy, sensitivity, and the capacity to feel genuinely safe with each other. This score weighs <em>Moon</em> and <em>Neptune</em> contacts: how well they read each other\'s unspoken feelings, whether vulnerability is met with care or indifference, and whether the emotional current between them nourishes or slowly depletes the people it flows through.',
            ],
            [
                'key'  => 'sexual',
                'text' => 'The physical chemistry and magnetic attraction between two people — the instinctive pull, the desire that sustains intimacy over time, and the capacity for genuine passion. This score reflects <em>Mars</em>, <em>Venus</em>, and <em>Pluto</em> contacts: whether physical attraction is mutual and lasting, whether it deepens with familiarity, and whether the erotic dimension of their connection has substance beneath the initial charge.',
            ],
            [
                'key'  => 'creative',
                'text' => 'The artistic and imaginative synergy between two people — shared inspiration, the capacity to spark each other\'s creativity, and the pleasure of making something together. This score weighs <em>Venus</em>, <em>Sun</em>, <em>Mercury</em>, and <em>Jupiter</em>: whether being together opens up possibilities rather than closing them down, whether they bring out each other\'s originality, and whether the relationship becomes a genuine source of creative nourishment.',
            ],

        ];
    }
}
