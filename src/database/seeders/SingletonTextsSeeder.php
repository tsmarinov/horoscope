<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Singleton and missing element text blocks.
 *
 * singleton (8 blocks): singleton_{element} + missing_{element}
 * singleton_short (8 blocks): same keys, 1 sentence each
 *
 * Elements: fire, earth, air, water
 * Bodies counted: Sun–Pluto (body 0–9) only — Chiron/NNode/Lilith excluded
 */
class SingletonTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (['singleton' => $this->fullBlocks(), 'singleton_short' => $this->shortBlocks()] as $section => $blocks) {
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

            DB::table('text_blocks')->upsert(
                $rows,
                ['key', 'section', 'language', 'variant'],
                ['text', 'tone', 'updated_at']
            );
        }
    }

    private function fullBlocks(): array
    {
        return [
            [
                'key'  => 'singleton_fire',
                'text' => 'One planet carries your entire fire element — all impulse, confidence, and forward drive flows through it alone. When that planet is strong or well-aspected, you can be surprisingly decisive and energised. When it is under pressure or retrograde, motivation drops sharply and impatience turns inward. <strong>You tend to act in concentrated bursts</strong> rather than maintaining steady momentum, which can make you highly effective in short intense efforts but harder to sustain over long stretches without clear direction.',
            ],
            [
                'key'  => 'singleton_earth',
                'text' => 'One planet handles all your practical grounding — money, physical security, and day-to-day reliability rest almost entirely on it. When it is well-placed, you can be impressively focused and productive in a specific area. When it is stressed, material concerns can feel overwhelming or entirely absent from your awareness. <strong>Your practical energy concentrates rather than spreads</strong>, which means you may be exceptionally capable in one domain while finding routine maintenance in other areas surprisingly difficult.',
            ],
            [
                'key'  => 'singleton_air',
                'text' => 'One planet carries all your rational thinking and social connection. Every conversation, analytical process, and exchange of ideas runs through this single point. When it functions well, you can be sharp, precise, and unusually clear in a particular mode of thinking. When it is challenged, mental clarity and ease of communication suffer at once rather than separately. <strong>Your thinking is concentrated and specific</strong> — you may have one very strong way of processing ideas while other modes of reasoning feel unnatural or tiring.',
            ],
            [
                'key'  => 'singleton_water',
                'text' => 'One planet manages your entire emotional depth — intuition, empathy, and psychological sensitivity all depend on it. When this planet is active and well-supported, you can be remarkably perceptive or emotionally committed in a specific way. When it is under pressure, the whole inner world feels blocked at once. <strong>Your emotional responses tend to be intense and specific rather than fluid</strong>, which means feelings build quietly and then arrive with force rather than moving through you in a continuous, manageable flow.',
            ],
            [
                'key'  => 'missing_fire',
                'text' => 'No planets in fire signs means spontaneity, bold initiative, and raw confidence are not default modes for you. You rarely act on impulse and may feel uncomfortable in situations that demand immediate enthusiasm or visible drive. <strong>To compensate, you often develop focused ambition through discipline and preparation</strong> — building momentum before acting rather than starting fast. Over time, directed effort can replace what impulse does not provide naturally, often with better and more lasting results.',
            ],
            [
                'key'  => 'missing_earth',
                'text' => 'No planets in earth signs means practical grounding, physical routine, and material consistency do not come naturally. Money management, physical maintenance, and stable habits often require deliberate effort to build and keep. <strong>To compensate, you tend to attach to external structures</strong> — reliable people, fixed schedules, or institutions that provide the grounding your chart does not generate automatically. Building deliberate physical anchors — regular exercise, consistent meals, fixed sleep — makes a real and lasting difference.',
            ],
            [
                'key'  => 'missing_air',
                'text' => 'No planets in air signs means detached analysis, easy social conversation, and abstract thinking are not automatic strengths. You process experience more through feeling, sensation, or direct action than through ideas alone. <strong>To compensate, you often develop rational skills deliberately</strong> — through reading, structured writing, or surrounding yourself with clear thinkers. Intellectual capacity becomes a built tool rather than an instinct. You may find casual conversation draining but excel in direct, grounded communication where what you say carries real weight.',
            ],
            [
                'key'  => 'missing_water',
                'text' => 'No planets in water signs means emotional depth, intuition, and sitting with feelings are not natural defaults. You tend to handle emotions through action, analysis, or practical problem-solving rather than experiencing them directly. <strong>To compensate, you often engage emotional life through close relationships</strong> — letting others carry the emotional texture that does not flow easily for you. Building deliberate space for private reflection helps you stay genuinely connected to your own inner life rather than discovering feelings only when they become unavoidable.',
            ],
        ];
    }

    private function shortBlocks(): array
    {
        return [
            [
                'key'  => 'singleton_fire',
                'text' => 'The single fire planet concentrates all your drive and initiative — energised when active, sharply demotivated when under pressure.',
            ],
            [
                'key'  => 'singleton_earth',
                'text' => 'One earth planet carries all your practical grounding — highly focused in one area, but vulnerable when that planet is stressed.',
            ],
            [
                'key'  => 'singleton_air',
                'text' => 'A single air planet holds your entire rational mind — thinking and communication both rise and fall together.',
            ],
            [
                'key'  => 'singleton_water',
                'text' => 'One water planet manages all emotional depth — perceptive and intense in one way, but the whole inner world is affected when it is challenged.',
            ],
            [
                'key'  => 'missing_fire',
                'text' => 'No fire planets means impulse and bold action do not come naturally — you compensate through discipline, preparation, and building deliberate momentum.',
            ],
            [
                'key'  => 'missing_earth',
                'text' => 'No earth planets means practical grounding takes real effort — you compensate by attaching to external structures, routines, and reliable people.',
            ],
            [
                'key'  => 'missing_air',
                'text' => 'No air planets means analytical thinking and easy conversation are learned rather than instinctive — you compensate through deliberate study and purposeful communication.',
            ],
            [
                'key'  => 'missing_water',
                'text' => 'No water planets means emotions do not flow automatically — you compensate through close relationships and deliberate space for private reflection.',
            ],
        ];
    }
}
