<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Short (haiku) solar dispositor natal house texts — 1 sentence per key.
 * section: solar_dispositor_house_short  |  12 keys  |  1 variant
 */
class SolarDispositorHouseShortSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'solar_dispositor_house_short',
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
            ['key' => 'solar_dispositor_natal_house_1',  'text' => 'The year\'s energy expresses itself through <strong>your own presence and initiative</strong> — direct action and personal courage are the mechanisms of change.'],
            ['key' => 'solar_dispositor_natal_house_2',  'text' => 'The year\'s themes play out through <strong>money, practical resources, and what you genuinely value</strong> — financial choices carry unusual weight this year.'],
            ['key' => 'solar_dispositor_natal_house_3',  'text' => 'The year\'s opportunities arrive through <strong>conversations, ideas, and everyday connections</strong> — what you say and learn opens the key doors.'],
            ['key' => 'solar_dispositor_natal_house_4',  'text' => 'The year\'s themes resolve through <strong>home, family, and your private inner life</strong> — domestic decisions and emotional honesty drive the year forward.'],
            ['key' => 'solar_dispositor_natal_house_5',  'text' => 'The year\'s energy channels through <strong>creativity, pleasure, and self-expression</strong> — following what genuinely delights you is not indulgence, it\'s the work.'],
            ['key' => 'solar_dispositor_natal_house_6',  'text' => 'The year\'s potential is unlocked through <strong>consistent daily effort, health, and practical service</strong> — discipline and routine are the year\'s real leverage points.'],
            ['key' => 'solar_dispositor_natal_house_7',  'text' => 'The year\'s themes unfold through <strong>key relationships and close partnerships</strong> — how you engage with others determines how the year\'s opportunities arrive.'],
            ['key' => 'solar_dispositor_natal_house_8',  'text' => 'The year\'s energy moves through <strong>shared resources, deep intimacy, and necessary transformation</strong> — what you release makes space for what the year is trying to bring.'],
            ['key' => 'solar_dispositor_natal_house_9',  'text' => 'The year\'s main themes express through <strong>travel, higher learning, and expanding your worldview</strong> — the further you stretch, the more the year delivers.'],
            ['key' => 'solar_dispositor_natal_house_10', 'text' => 'The year\'s energy channels through <strong>career, public life, and professional ambition</strong> — what you build in the world becomes the primary vehicle for this year\'s themes.'],
            ['key' => 'solar_dispositor_natal_house_11', 'text' => 'The year\'s potential flows through <strong>networks, groups, and collective endeavours</strong> — the people you gather with and the goals you share collectively shape the year\'s direction.'],
            ['key' => 'solar_dispositor_natal_house_12', 'text' => 'The year\'s themes work themselves out <strong>quietly, beneath the surface</strong> — solitude, reflection, and inner honesty are the conditions under which this year\'s real progress happens.'],
        ];
    }
}
