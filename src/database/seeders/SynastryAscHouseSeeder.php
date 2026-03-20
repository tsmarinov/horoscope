<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored synastry ASC-in-house overlay text blocks.
 *
 * Section: synastry_asc_house
 * Keys:    asc_house_1 … asc_house_12
 * Total:   12 blocks
 *
 * Placeholders substituted at render time:
 *   :owner  = person whose Ascendant it is  (e.g. "Ivan")
 *   :other  = person whose house it falls in (e.g. "Maria")
 *   :owner's / :other's = possessive forms
 */
class SynastryAscHouseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'synastry_asc_house',
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
                'key'  => 'asc_house_1',
                'text' => ':owner\'s Ascendant falls in :other\'s first house, creating an immediate sense of recognition and resonance. How :owner naturally presents mirrors or amplifies :other\'s own energy — <strong>:other may feel as though looking into a version of themselves</strong>, which can be both fascinating and at times unsettling. Physical appearance, first impressions, and personal style are common points of connection. :other feels genuinely seen with :owner.',
            ],
            [
                'key'  => 'asc_house_2',
                'text' => ':owner\'s Ascendant falls in :other\'s second house of resources and values. The way :owner shows up in the world connects naturally to what :other values most — :owner\'s presence influences how :other thinks about security, money, and worth. <strong>:owner can either reinforce :other\'s sense of material stability or challenge the values :other has built their security around.</strong> There is often an appreciable aesthetic or financial dimension to the dynamic between them.',
            ],
            [
                'key'  => 'asc_house_3',
                'text' => ':owner\'s Ascendant enters :other\'s third house of communication and daily exchange. :owner\'s natural manner of presenting fits seamlessly into :other\'s everyday mental world — <strong>conversation flows effortlessly, and the relationship feels comfortable and intellectually alive from the beginning.</strong> This overlay supports ongoing communication and is common in relationships that thrive on regular contact, exchange of ideas, and the easy companionship of people who simply enjoy talking.',
            ],
            [
                'key'  => 'asc_house_4',
                'text' => ':owner\'s Ascendant falls in :other\'s fourth house of home and emotional roots. The way :owner naturally presents resonates with :other\'s deepest sense of security and belonging. <strong>:owner feels like home</strong> — :owner\'s presence triggers a private, instinctive warmth that is distinct from the ease of a social connection. This overlay often indicates that :owner has a significant effect on :other\'s domestic life or inner emotional world.',
            ],
            [
                'key'  => 'asc_house_5',
                'text' => ':owner\'s Ascendant enters :other\'s fifth house of creativity, romance, and joy. The way :owner naturally presents appeals directly to the part of :other that wants to play, love, and create. <strong>:owner makes :other feel alive in a particular way</strong> — :owner\'s energy awakens :other\'s own desire for pleasure and self-expression. This is one of the most naturally romantic overlays, as :owner\'s very presence tends to put :other in the mood for everything the fifth house promises.',
            ],
            [
                'key'  => 'asc_house_6',
                'text' => ':owner\'s Ascendant falls in :other\'s sixth house of work, daily life, and health. :owner\'s natural manner fits comfortably into the practical dimensions of :other\'s everyday existence — <strong>:owner is someone :other can work alongside, live with in close quarters, or rely on to keep pace with :other\'s daily rhythm.</strong> This overlay often appears in relationships that develop through shared routines or professional environments, and has a grounding, functional quality.',
            ],
            [
                'key'  => 'asc_house_7',
                'text' => ':owner\'s Ascendant falls directly in :other\'s seventh house of partnership. The way :owner naturally presents is exactly what :other looks for in a significant other — <strong>:owner embodies qualities :other associates with an ideal partner</strong>, which makes this one of the most powerful overlays for long-term romantic commitment. The risk is projection: because :owner seems to match :other\'s ideal, :other may attribute qualities to :owner that aren\'t quite there. Clarity and time tend to be the antidote.',
            ],
            [
                'key'  => 'asc_house_8',
                'text' => ':owner\'s Ascendant enters :other\'s eighth house of depth, transformation, and power. The way :owner presents in the world reaches directly into :other\'s psychological depths — <strong>:owner\'s presence activates something fundamental and not entirely comfortable in :other.</strong> This is an intensely compelling overlay, the kind that makes a connection feel significant and unavoidable. Power dynamics, sexuality, and the psychology of desire are all present in some form.',
            ],
            [
                'key'  => 'asc_house_9',
                'text' => ':owner\'s Ascendant falls in :other\'s ninth house of expansion, belief, and travel. The way :owner naturally presents broadens :other\'s perspective and connects to the most philosophical, free-ranging part of :other\'s nature. <strong>:owner feels like an adventure</strong> — someone whose presence opens up new directions and validates :other\'s hunger for more than the ordinary. This overlay often appears in relationships between people of different backgrounds, beliefs, or cultures.',
            ],
            [
                'key'  => 'asc_house_10',
                'text' => ':owner\'s Ascendant enters :other\'s tenth house of career and public reputation. The way :owner presents has a direct effect on how :other is seen professionally and publicly — <strong>:owner can be a significant ally in :other\'s career</strong>, someone whose association elevates or directs :other\'s public standing. This overlay is particularly meaningful in professional partnerships or romantic relationships that have a visible public dimension.',
            ],
            [
                'key'  => 'asc_house_11',
                'text' => ':owner\'s Ascendant falls in :other\'s eleventh house of friendship, community, and future aspirations. The way :owner naturally moves through the world resonates with :other\'s vision of the future and :other\'s social values. <strong>:owner feels like a kindred spirit</strong> — someone who belongs in :other\'s world and shares :other\'s sense of where things are going. This overlay supports long-term friendship and community bonds, and is common in relationships that develop through shared social circles.',
            ],
            [
                'key'  => 'asc_house_12',
                'text' => ':owner\'s Ascendant falls in :other\'s twelfth house — the hidden, spiritual, and unconscious realm. The way :owner presents reaches into the parts of :other that are not ordinarily visible. <strong>:owner sees through :other\'s persona to something deeper</strong>, which can feel both profoundly intimate and uncomfortably revealing. This overlay often has a quality of the past coming forward and is associated with relationships that feel like they are working something out together.',
            ],

        ];
    }
}
