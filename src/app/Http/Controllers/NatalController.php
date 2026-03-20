<?php

namespace App\Http\Controllers;

use App\Enums\ReportMode;
use App\Facades\AspectCalculator;
use App\Jobs\GenerateNatalPortrait;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\ReportBuilder;
use Illuminate\Support\Facades\Cache;

class NatalController extends Controller
{
    use \App\Http\Controllers\Concerns\BuildsPdfFooter;
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

    private const PLANET_NAMES = [
        0 => 'sun', 1 => 'moon', 2 => 'mercury', 3 => 'venus', 4 => 'mars',
        5 => 'jupiter', 6 => 'saturn', 7 => 'uranus', 8 => 'neptune', 9 => 'pluto',
    ];

    public function pdf(Profile $profile)
    {
        abort_if(!$this->ownsProfile($profile), 403);

        $profile->loadMissing('birthCity');
        $chart = AspectCalculator::calculate($profile);

        $gender           = $profile->gender instanceof \App\Enums\Gender ? $profile->gender->value : $profile->gender;
        $short            = request()->boolean('short');
        $singletons       = $this->computeSingletons($chart->planets ?? [], $gender, $profile->id, $short);
        $houseLords       = $this->computeHouseLords($chart, $gender, $profile->id, $short);
        $houseLordAspects = $this->computeHouseLordAspects($chart, $gender, $profile->id, $short);
        $aspectTexts      = $this->computeAspectTexts($chart, $gender, $profile->id, $short);
        $angleAspectTexts = $this->computeAngleAspectTexts($chart, $gender, $profile->id, $short);

        $builder   = app(ReportBuilder::class);
        $portrait  = $builder->loadCached($profile, $short ? ReportMode::AiL1Haiku : ReportMode::AiL1, 'en')?->introduction;

        $pdf = \PDF::loadView('natal.pdf', compact(
            'profile', 'chart', 'singletons', 'houseLords',
            'houseLordAspects', 'aspectTexts', 'angleAspectTexts', 'portrait'
        ))->setPaper('a4')
          ->setOption('encoding', 'UTF-8')
          ->setOption('margin-top', '15')
          ->setOption('margin-bottom', '22')
          ->setOption('margin-left', '20')
          ->setOption('margin-right', '20')
          ->setOption('enable-javascript', true)
          ->setOption('javascript-delay', 1500)
          ->setOption('no-stop-slow-scripts', true)
          ->setOption('footer-html', $this->buildFooterFile())
          ->setOption('footer-spacing', '3');

        $filename = 'natal-' . \Str::slug($profile->name) . '.pdf';
        return $pdf->download($filename);
    }

    public function generatePortrait(Profile $profile)
    {
        abort_if(!auth()->check(), 403);

        $cacheKey = "natal_portrait_generating_{$profile->id}";

        // Don't dispatch again if already queued
        if (Cache::has($cacheKey)) {
            return response()->json(['queued' => true]);
        }

        Cache::put($cacheKey, true, 300);
        GenerateNatalPortrait::dispatch($profile);

        $user = auth()->user();
        \App\Models\UserHoroscopeLog::create([
            'user_id'              => $user->id,
            'user_uuid'            => $user->uuid ?? null,
            'user_email'           => $user->email,
            'profile_uuid'         => $profile->uuid,
            'profile_snapshot'     => \App\Models\UserHoroscopeLog::snapshotProfile($profile),
            'type'                 => 'natal_portrait',
            'premium_content'      => true,
            'premium_requested_at' => now(),
        ]);

        return response()->json(['queued' => true]);
    }

    public function portraitStatus(Profile $profile): \Illuminate\Http\JsonResponse
    {
        abort_if(!auth()->check(), 403);

        $generating    = Cache::has("natal_portrait_generating_{$profile->id}");
        $builder       = app(ReportBuilder::class);
        $portraitFull  = $builder->loadCached($profile, ReportMode::AiL1,      'en')?->introduction;
        $portraitShort = $builder->loadCached($profile, ReportMode::AiL1Haiku,  'en')?->introduction;

        return response()->json([
            'generating'    => $generating,
            'portrait_full'  => $portraitFull,
            'portrait_short' => $portraitShort,
        ]);
    }

    public function redirect()
    {
        if (auth()->check()) {
            $profile = Profile::where('user_id', auth()->id())
                ->whereNotNull('last_used_at')->orderByDesc('last_used_at')->first()
                ?? Profile::where('user_id', auth()->id())->orderBy('first_name')->first();
        } else {
            $guest   = $this->currentGuest();
            $profile = $guest ? Profile::where('guest_id', $guest->id)->first() : null;
        }

        if ($profile === null) {
            return redirect()->route('stellar-profiles.index');
        }

        return redirect()->route('natal.show', $profile);
    }

    public function show(Profile $profile)
    {
        abort_if(!$this->ownsProfile($profile), 403);

        $profile->touchLastUsed();
        $profile->loadMissing('birthCity');

        $chart = AspectCalculator::calculate($profile);

        $builder       = app(ReportBuilder::class);
        $portraitFull  = $builder->loadCached($profile, ReportMode::AiL1,     'en')?->introduction;
        $portraitShort = $builder->loadCached($profile, ReportMode::AiL1Haiku, 'en')?->introduction;
        $generating    = Cache::has("natal_portrait_generating_{$profile->id}");

        if (auth()->check()) {
            $profiles = Profile::where('user_id', auth()->id())->orderByDesc('last_used_at')->orderBy('first_name')->get();
        } else {
            $profiles = collect();
        }
        $gender     = $profile->gender instanceof \App\Enums\Gender ? $profile->gender->value : $profile->gender;
        $singletons            = $this->computeSingletons($chart->planets ?? [], $gender, $profile->id);
        $houseLords            = $this->computeHouseLords($chart, $gender, $profile->id);
        $houseLordAspects      = $this->computeHouseLordAspects($chart, $gender, $profile->id);
        $aspectTexts           = $this->computeAspectTexts($chart, $gender, $profile->id);
        $angleAspectTexts      = $this->computeAngleAspectTexts($chart, $gender, $profile->id);

        $singletonsShort       = $this->computeSingletons($chart->planets ?? [], $gender, $profile->id, true);
        $houseLordsShort       = $this->computeHouseLords($chart, $gender, $profile->id, true);
        $houseLordAspectsShort = $this->computeHouseLordAspects($chart, $gender, $profile->id, true);
        $aspectTextsShort      = $this->computeAspectTexts($chart, $gender, $profile->id, true);
        $angleAspectTextsShort = $this->computeAngleAspectTexts($chart, $gender, $profile->id, true);

        return view('natal.show', compact(
            'profile', 'chart', 'profiles',
            'singletons', 'houseLords', 'houseLordAspects', 'aspectTexts', 'angleAspectTexts',
            'singletonsShort', 'houseLordsShort', 'houseLordAspectsShort', 'aspectTextsShort', 'angleAspectTextsShort',
            'portraitFull', 'portraitShort', 'generating'
        ));
    }

    private function computeSingletons(array $planets, ?string $gender, ?int $profileId = null, bool $short = false): array
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
            $block = TextBlock::pickForProfile($key, $short ? 'singleton_short' : 'singleton', 'en', $gender, $profileId);

            $result[] = [
                'type'    => $count === 1 ? 'singleton' : 'missing',
                'element' => self::ELEMENT_LABELS[$el],
                'planet'  => $count === 1 ? $list[0] : null,
                'text'    => $block ? strip_tags($block->text) : null,
            ];
        }

        return $result;
    }

    private function computeHouseLords(\App\Models\NatalChart $chart, ?string $gender, ?int $profileId = null, bool $short = false): array
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
            $block = TextBlock::pickForProfile($key, $short ? 'natal_house_lords_short' : 'natal_house_lords', 'en', $gender, $profileId);

            if ($block === null) continue;

            $result[] = [
                'house' => $house,
                'label' => self::HOUSE_LABELS[$house] ?? "House {$house}",
                'text'  => strip_tags($block->text),
            ];
        }

        return $result;
    }

    private function computeAngleAspectTexts(\App\Models\NatalChart $chart, ?string $gender, ?int $profileId = null, bool $short = false): array
    {
        if ($chart->ascendant === null) {
            return [];
        }

        $angles = array_filter([
            'asc' => (float) $chart->ascendant,
            'mc'  => $chart->mc !== null ? (float) $chart->mc : null,
        ]);

        $aspectConfig = config('astrology.aspects', []);
        $orb          = 5.0;
        $result       = [];

        foreach ($chart->planets as $planet) {
            $bodyId     = (int) ($planet['body'] ?? -1);
            $planetName = self::PLANET_NAMES[$bodyId] ?? null;
            if ($planetName === null) continue;

            $lon = (float) ($planet['longitude'] ?? 0);

            foreach ($angles as $angleName => $angleLon) {
                $diff = abs($lon - $angleLon);
                if ($diff > 180) $diff = 360 - $diff;

                $bestAspect = null;
                $bestOrb    = PHP_FLOAT_MAX;

                foreach ($aspectConfig as $aspName => $def) {
                    $deviation = abs($diff - $def['angle']);
                    if ($deviation <= $orb && $deviation < $bestOrb) {
                        $bestOrb    = $deviation;
                        $bestAspect = $aspName;
                    }
                }

                if ($bestAspect === null) continue;

                $key   = "{$planetName}_{$bestAspect}_{$angleName}";
                $section = $short ? 'natal_angles_short' : 'natal_angles';
                $block = TextBlock::pickForProfile($key, $section, 'en', $gender, $profileId)
                      ?? TextBlock::pickForProfile($key, $section, 'en', null, $profileId);

                if ($block === null) continue;

                $result[] = [
                    'planet' => ucfirst($planetName),
                    'aspect' => $bestAspect,
                    'angle'  => strtoupper($angleName),
                    'tone'   => $block->tone ?? 'neutral',
                    'text'   => $block->text,
                ];
            }
        }

        return $result;
    }

    private function computeAspectTexts(\App\Models\NatalChart $chart, ?string $gender, ?int $profileId = null, bool $short = false): array
    {
        $result = [];

        foreach ($chart->aspects as $asp) {
            $bodyA   = (int) ($asp['body_a'] ?? -1);
            $bodyB   = (int) ($asp['body_b'] ?? -1);
            $aspect  = $asp['aspect'] ?? '';

            $nameA = self::PLANET_NAMES[$bodyA] ?? null;
            $nameB = self::PLANET_NAMES[$bodyB] ?? null;
            if ($nameA === null || $nameB === null || $aspect === '') continue;

            $key   = "{$nameA}_{$aspect}_{$nameB}";
            $section = $short ? 'natal_short' : 'natal';
            $block = TextBlock::pickForProfile($key, $section, 'en', $gender, $profileId)
                  ?? TextBlock::pickForProfile($key, $section, 'en', null, $profileId);

            if ($block === null && $aspect !== 'mutual_reception') continue;

            $result[] = [
                'key'    => $key,
                'bodyA'  => $bodyA,
                'bodyB'  => $bodyB,
                'aspect' => $aspect,
                'tone'   => $block->tone ?? 'neutral',
                'text'   => $block?->text,
            ];
        }

        return $result;
    }

    private function computeHouseLordAspects(\App\Models\NatalChart $chart, ?string $gender, ?int $profileId = null, bool $short = false): array
    {
        if ($chart->ascendant === null || empty($chart->houses)) {
            return [];
        }

        $ascSignIdx = (int) floor($chart->ascendant / 30);
        $result     = [];

        for ($house = 1; $house <= 12; $house++) {
            $cuspSignIdx = ($ascSignIdx + $house - 1) % 12;
            $lordBodyId  = self::ASC_LORD_MAP[$cuspSignIdx] ?? null;

            if ($lordBodyId === null) continue;

            $lordName = self::PLANET_NAMES[$lordBodyId] ?? null;
            if ($lordName === null) continue;

            foreach ($chart->aspects as $asp) {
                $bodyA  = (int) ($asp['body_a'] ?? -1);
                $bodyB  = (int) ($asp['body_b'] ?? -1);
                $aspect = $asp['aspect'] ?? '';

                if ($bodyA === $lordBodyId) {
                    $otherBodyId = $bodyB;
                } elseif ($bodyB === $lordBodyId) {
                    $otherBodyId = $bodyA;
                } else {
                    continue;
                }

                $otherName = self::PLANET_NAMES[$otherBodyId] ?? null;
                if ($otherName === null) continue;

                $key   = "house_{$house}_lord_{$lordName}_{$aspect}_{$otherName}";
                $block = TextBlock::pickForProfile($key, $short ? 'natal_house_lord_aspects_short' : 'natal_house_lord_aspects', 'en', $gender, $profileId);

                if ($block === null) continue;

                $result[] = [
                    'house'  => $house,
                    'label'  => self::HOUSE_LABELS[$house] ?? "House {$house}",
                    'lord'   => ucfirst($lordName),
                    'aspect' => $aspect,
                    'other'  => ucfirst($otherName),
                    'text'   => $block->text,
                ];
            }
        }

        return $result;
    }
}
