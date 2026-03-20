<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Short (haiku) solar ASC natal house texts — 1 sentence per key.
 * section: solar_asc_house_short  |  12 keys  |  1 variant
 */
class SolarAscHouseShortSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'solar_asc_house_short',
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
            ['key' => 'solar_asc_natal_house_1',  'text' => 'This is a year of <strong>personal reinvention</strong> — your identity, appearance, and how you present yourself to the world take centre stage.'],
            ['key' => 'solar_asc_natal_house_2',  'text' => 'The year\'s focus falls on <strong>finances, values, and material security</strong> — what you earn, own, and truly need comes into sharp relief.'],
            ['key' => 'solar_asc_natal_house_3',  'text' => 'A year shaped by <strong>communication, learning, and local connections</strong> — ideas, conversations, and short journeys carry the year\'s main opportunities.'],
            ['key' => 'solar_asc_natal_house_4',  'text' => 'The year\'s energy turns inward toward <strong>home, family, and emotional foundations</strong> — where you live and who you belong to are central themes.'],
            ['key' => 'solar_asc_natal_house_5',  'text' => 'A year animated by <strong>creativity, romance, and self-expression</strong> — joy, play, and the courage to be seen are the year\'s defining gifts.'],
            ['key' => 'solar_asc_natal_house_6',  'text' => 'The year is shaped by <strong>work, health, and daily discipline</strong> — building better habits and refining how you serve are the year\'s central tasks.'],
            ['key' => 'solar_asc_natal_house_7',  'text' => 'A year defined by <strong>partnership, collaboration, and significant one-on-one relationships</strong> — what you build with others matters most.'],
            ['key' => 'solar_asc_natal_house_8',  'text' => 'A year of <strong>depth, transformation, and confronting what lies beneath the surface</strong> — shared resources, intimacy, and renewal are key themes.'],
            ['key' => 'solar_asc_natal_house_9',  'text' => 'The year calls you toward <strong>expansion, travel, learning, and the search for meaning</strong> — your worldview is ready to grow significantly.'],
            ['key' => 'solar_asc_natal_house_10', 'text' => 'A year of heightened <strong>professional visibility and ambition</strong> — your career, reputation, and public role are the year\'s primary arena.'],
            ['key' => 'solar_asc_natal_house_11', 'text' => 'The year\'s energy flows through <strong>community, friendships, and long-term collective goals</strong> — your networks and social vision carry real momentum.'],
            ['key' => 'solar_asc_natal_house_12', 'text' => 'A year of <strong>retreat, inner work, and spiritual deepening</strong> — what has been hidden or unprocessed asks for quiet attention and honest release.'],
        ];
    }
}
