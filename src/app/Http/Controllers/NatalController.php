<?php

namespace App\Http\Controllers;

use App\Facades\AspectCalculator;
use App\Models\Profile;
use App\Models\TextBlock;

class NatalController extends Controller
{
    private const SIGN_ELEMENTS = [
        'fire', 'earth', 'air', 'water',
        'fire', 'earth', 'air', 'water',
        'fire', 'earth', 'air', 'water',
    ];

    private const ELEMENT_LABELS = [
        'fire'  => 'Fire',
        'earth' => 'Earth',
        'air'   => 'Air',
        'water' => 'Water',
    ];

    private const ASC_LORD_MAP = [
        0  => 4,  // Aries → Mars
        1  => 3,  // Taurus → Venus
        2  => 2,  // Gemini → Mercury
        3  => 1,  // Cancer → Moon
        4  => 0,  // Leo → Sun
        5  => 2,  // Virgo → Mercury
        6  => 3,  // Libra → Venus
        7  => 9,  // Scorpio → Pluto
        8  => 5,  // Sagittarius → Jupiter
        9  => 6,  // Capricorn → Saturn
        10 => 7,  // Aquarius → Uranus
        11 => 8,  // Pisces → Neptune
    ];

    private const HOUSE_LABELS = [
        1  => 'ASC — Self & Identity',
        2  => '2nd House — Money & Resources',
        3  => '3rd House — Communication & Short Travel',
        4  => '4th House — Home & Family',
        5  => '5th House — Creativity & Romance',
        6  => '6th House — Work & Health',
        7  => '7th House — Partnerships',
        8  => '8th House — Transformation & Shared Resources',
        9  => '9th House — Philosophy & Long Travel',
        10 => '10th House — Career & Public Life',
        11 => '11th House — Friends & Aspirations',
        12 => '12th House — Hidden Matters & Solitude',
    ];

    private const SIGN_NAMES_LOWER = [
        'aries','taurus','gemini','cancer','leo','virgo',
        'libra','scorpio','sagittarius','capricorn','aquarius','pisces',
    ];

    public function show(Profile $profile)
    {
        abort_if($profile->user_id !== auth()->id(), 403);

        $profile->loadMissing('birthCity');

        $chart = AspectCalculator::calculate($profile);

        $profiles   = Profile::where('user_id', auth()->id())->orderBy('first_name')->get();
        $gender     = $profile->gender instanceof \App\Enums\Gender ? $profile->gender->value : $profile->gender;
        $singletons = $this->computeSingletons($chart->planets ?? [], $gender);
        $houseLords = $this->computeHouseLords($chart, $gender);

        return view('natal.show', compact('profile', 'chart', 'singletons', 'houseLords', 'profiles'));
    }

    private function computeSingletons(array $planets, ?string $gender): array
    {
        $groups = ['fire' => [], 'earth' => [], 'air' => [], 'water' => []];

        foreach ($planets as $p) {
            $body = (int) ($p['body'] ?? -1);
            if ($body < 0 || $body > 9) continue;
            $el = self::SIGN_ELEMENTS[$p['sign'] ?? 0] ?? null;
            if ($el) $groups[$el][] = $p;
        }

        $result = [];
        foreach ($groups as $el => $list) {
            $count = count($list);
            if ($count !== 0 && $count !== 1) continue;

            $key   = $count === 1 ? 'singleton_' . $el : 'missing_' . $el;
            $block = TextBlock::pick($key, 'singleton', 1, 'en', $gender);

            $result[] = [
                'type'    => $count === 1 ? 'singleton' : 'missing',
                'element' => self::ELEMENT_LABELS[$el],
                'planet'  => $count === 1 ? $list[0] : null,
                'text'    => $block ? strip_tags($block->text) : null,
            ];
        }

        return $result;
    }

    private function computeHouseLords(\App\Models\NatalChart $chart, ?string $gender): array
    {
        if ($chart->ascendant === null || empty($chart->houses)) {
            return [];
        }

        $ascSignIdx = (int) floor($chart->ascendant / 30);
        $planets    = collect($chart->planets ?? []);
        $result     = [];

        for ($house = 1; $house <= 12; $house++) {
            $cuspSignIdx = ($ascSignIdx + $house - 1) % 12;
            $cuspSign    = self::SIGN_NAMES_LOWER[$cuspSignIdx];
            $lordBodyId  = self::ASC_LORD_MAP[$cuspSignIdx] ?? null;

            if ($lordBodyId === null) continue;

            $lord = $planets->firstWhere('body', $lordBodyId);
            if ($lord === null) continue;

            $lordSign  = self::SIGN_NAMES_LOWER[$lord['sign']] ?? '';
            $lordHouse = $lord['house'] ?? null;

            if ($lordSign === '' || $lordHouse === null) continue;

            $key   = "house_{$house}_cusp_{$cuspSign}_lord_in_{$lordSign}_house_{$lordHouse}";
            $block = TextBlock::pick($key, 'natal_house_lords', 1, 'en', $gender);

            if ($block === null) continue;

            $result[] = [
                'house' => $house,
                'label' => self::HOUSE_LABELS[$house] ?? "House {$house}",
                'text'  => strip_tags($block->text),
            ];
        }

        return $result;
    }
}
