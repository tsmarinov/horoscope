<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Short (haiku) lunation texts — 1 sentence per key.
 * section: lunation_short  |  24 keys  |  1 variant
 */
class LunationShortTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'lunation_short',
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
            // ── New Moons ─────────────────────────────────────────────────
            ['key' => 'new_moon_aries',       'text' => 'A bold new cycle begins — set intentions around what you want to <strong>initiate, assert, and claim as your own</strong>.'],
            ['key' => 'new_moon_taurus',      'text' => 'Plant intentions around <strong>stability, material security, and what you want to build</strong> patiently over time.'],
            ['key' => 'new_moon_gemini',      'text' => 'A fresh cycle for <strong>learning, communicating, and connecting</strong> — follow your curiosity wherever it leads.'],
            ['key' => 'new_moon_cancer',      'text' => 'Set intentions around <strong>home, emotional safety, and the relationships</strong> that make you feel truly held.'],
            ['key' => 'new_moon_leo',         'text' => 'The cycle invites you to <strong>create, express, and be seen</strong> — stop waiting for permission to shine.'],
            ['key' => 'new_moon_virgo',       'text' => 'Set intentions around <strong>health, daily habits, and the small improvements</strong> that compound into real change.'],
            ['key' => 'new_moon_libra',       'text' => 'A new cycle for <strong>relationships, balance, and collaboration</strong> — what kind of partnership do you want to grow?'],
            ['key' => 'new_moon_scorpio',     'text' => 'Set intentions around <strong>transformation and honest release</strong> — what are you truly ready to let go of?'],
            ['key' => 'new_moon_sagittarius', 'text' => 'A cycle of <strong>expansion and exploration</strong> begins — set intentions around where you want to grow and what you want to believe.'],
            ['key' => 'new_moon_capricorn',   'text' => 'The most powerful lunation for <strong>long-term ambition</strong> — set clear, serious goals and commit to the work.'],
            ['key' => 'new_moon_aquarius',    'text' => 'Set intentions around <strong>community, innovation, and your vision</strong> for something larger than yourself.'],
            ['key' => 'new_moon_pisces',      'text' => 'The most inward lunation of the year — set <strong>fluid, imaginative intentions</strong> and allow what wants to emerge.'],

            // ── Full Moons ────────────────────────────────────────────────
            ['key' => 'full_moon_aries',       'text' => 'A peak moment around <strong>identity and independence</strong> — what have you been holding back that needs to be expressed?'],
            ['key' => 'full_moon_taurus',      'text' => 'Something in your <strong>material life or sense of self-worth</strong> reaches a turning point — what are you ready to release?'],
            ['key' => 'full_moon_gemini',      'text' => '<strong>Information and conversations</strong> peak — a truth surfaces, a decision crystallises, or scattered threads finally connect.'],
            ['key' => 'full_moon_cancer',      'text' => 'Deep <strong>emotional and domestic themes</strong> come to light — feelings that have been suppressed are ready to be acknowledged.'],
            ['key' => 'full_moon_leo',         'text' => 'A peak of <strong>visibility and creative expression</strong> — are you showing up fully, or still waiting to be seen?'],
            ['key' => 'full_moon_virgo',       'text' => 'The results of your <strong>daily habits and work ethic</strong> are on full display — what needs correcting or releasing?'],
            ['key' => 'full_moon_libra',       'text' => 'A key <strong>relationship reaches a decision point</strong> — honest dialogue and genuine fairness are called for now.'],
            ['key' => 'full_moon_scorpio',     'text' => 'What has been hidden in your <strong>emotional or shared life</strong> surfaces fully — face it honestly and release what no longer serves.'],
            ['key' => 'full_moon_sagittarius', 'text' => 'Your <strong>beliefs and sense of direction</strong> reach a peak — is the life you are living aligned with what you truly value?'],
            ['key' => 'full_moon_capricorn',   'text' => '<strong>Professional ambitions and public reputation</strong> reach a culminating point — recognise what you have built and what to release.'],
            ['key' => 'full_moon_aquarius',    'text' => 'Something in your <strong>community or long-term vision</strong> comes to light — where do you truly belong, and what are you contributing?'],
            ['key' => 'full_moon_pisces',      'text' => 'The most spiritually charged lunation — what has been <strong>hidden or unresolved</strong> rises up, ready for release and forgiveness.'],
        ];
    }
}
