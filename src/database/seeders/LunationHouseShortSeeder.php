<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored lunation house short text blocks (1-2 sentences each).
 *
 * Section: lunation_house_short
 * Keys:    new_moon_house_1 … new_moon_house_12
 *          full_moon_house_1 … full_moon_house_12
 * Total:   24 blocks
 */
class LunationHouseShortSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'lunation_house_short',
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

            ['key' => 'new_moon_house_1',  'text' => 'A new personal cycle begins — <strong>redefine how you want to show up in the world</strong> and take one deliberate step toward your own fresh start.'],
            ['key' => 'new_moon_house_2',  'text' => 'Seeds planted now around <strong>income, resources, and personal values</strong> will take root — begin building financial or material security with intention.'],
            ['key' => 'new_moon_house_3',  'text' => 'Your mind and voice are ready for something new — <strong>start a course, open a meaningful conversation, or commit to a learning project</strong> that\'s been waiting.'],
            ['key' => 'new_moon_house_4',  'text' => 'A fresh chapter begins at home and in your emotional foundations — <strong>what you build or clear in your domestic life now shapes your sense of security for months ahead</strong>.'],
            ['key' => 'new_moon_house_5',  'text' => 'A new creative or romantic cycle opens — <strong>start the project, allow yourself to be seen, or bring more genuine joy</strong> into your life right now.'],
            ['key' => 'new_moon_house_6',  'text' => 'Your daily routines and health habits are ready for a reset — <strong>one small, consistent change started now</strong> will compound into real improvement.'],
            ['key' => 'new_moon_house_7',  'text' => 'A new chapter begins in your one-on-one relationships — <strong>the commitment, conversation, or collaboration you initiate now carries lasting weight</strong>.'],
            ['key' => 'new_moon_house_8',  'text' => 'A cycle of deep change begins — <strong>release what no longer serves you and commit to genuine transformation</strong> in how you handle shared resources and intimacy.'],
            ['key' => 'new_moon_house_9',  'text' => 'Your horizons are ready to expand — <strong>begin the journey, course, or study</strong> that pulls you toward a broader and more meaningful understanding of the world.'],
            ['key' => 'new_moon_house_10', 'text' => 'A major professional cycle is opening — <strong>take a deliberate step toward your career goals now</strong> as ambitions planted under this lunation carry unusual momentum.'],
            ['key' => 'new_moon_house_11', 'text' => 'A fresh social chapter begins — <strong>join the community, clarify your hopes, or reach out to people who share your values</strong> and see what grows.'],
            ['key' => 'new_moon_house_12', 'text' => 'A cycle of inner renewal begins — <strong>let go of what has been draining you in private</strong> and create space for something truer to emerge.'],

            // ── Full Moon ───────────────────────────────────────────────────

            ['key' => 'full_moon_house_1',  'text' => 'Your identity and personal direction are under full illumination — <strong>a turning point in how you\'re seen and how you see yourself</strong> is arriving now.'],
            ['key' => 'full_moon_house_2',  'text' => 'A financial or values matter reaches its peak — <strong>what you\'re actually worth and what resources are truly available to you</strong> become impossible to ignore.'],
            ['key' => 'full_moon_house_3',  'text' => 'A conversation, message, or local matter reaches its culmination — <strong>say what needs to be said or complete what has been left unfinished</strong>.'],
            ['key' => 'full_moon_house_4',  'text' => 'Your home and family life are fully illuminated — <strong>a domestic situation or emotional pattern has reached the point where it must be addressed</strong>.'],
            ['key' => 'full_moon_house_5',  'text' => 'A creative or romantic situation reaches its peak — <strong>what you\'ve been building in your love life or creative work is now ready to show its true shape</strong>.'],
            ['key' => 'full_moon_house_6',  'text' => 'A health or work matter can no longer be deferred — <strong>address what your body has been signaling or complete the work project that has been building</strong>.'],
            ['key' => 'full_moon_house_7',  'text' => 'A relationship is reaching a decisive turning point — <strong>what\'s been working and what hasn\'t is now fully visible, and a decision may be unavoidable</strong>.'],
            ['key' => 'full_moon_house_8',  'text' => 'Something deeply buried is surfacing — <strong>a transformation around shared resources, intimacy, or inner change is reaching its peak and asking to be faced</strong>.'],
            ['key' => 'full_moon_house_9',  'text' => 'A belief, journey, or educational matter reaches its culmination — <strong>what you\'ve been learning or travelling toward is now showing its final shape</strong>.'],
            ['key' => 'full_moon_house_10', 'text' => 'Your career and public standing are fully illuminated — <strong>an achievement is peaking or a significant professional turning point is arriving now</strong>.'],
            ['key' => 'full_moon_house_11', 'text' => 'A friendship, group involvement, or long-held hope reaches its culmination — <strong>what you\'ve been building toward socially is now showing its true form</strong>.'],
            ['key' => 'full_moon_house_12', 'text' => 'Something hidden is surfacing — <strong>an old pattern or private burden is reaching the light, creating space for genuine release and renewal</strong>.'],
        ];
    }
}
