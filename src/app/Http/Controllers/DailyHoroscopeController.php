<?php

namespace App\Http\Controllers;

use App\DataTransfer\Horoscope\DailyHoroscopeDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Models\UserHoroscopeLog;
use App\Services\Horoscope\DailyHoroscopeService;
use App\Services\Ai\HoroscopeSynthesisService;
use Carbon\Carbon;

class DailyHoroscopeController extends Controller
{
    private const BODY_GLYPHS = [
        0 => '☉', 1 => '☽',  2 => '☿', 3 => '♀',  4 => '♂',
        5 => '♃', 6 => '♄',  7 => '♅', 8 => '♆',  9 => '♇',
       10 => '⚷', 11 => '☊', 12 => '⚸',
    ];

    private const SIGN_GLYPHS = [
        0 => '♈', 1 => '♉', 2 => '♊',  3 => '♋',
        4 => '♌', 5 => '♍', 6 => '♎',  7 => '♏',
        8 => '♐', 9 => '♑', 10 => '♒', 11 => '♓',
    ];

    private const ASPECT_GLYPHS = [
        'conjunction'      => '☌', 'opposition'       => '☍',
        'trine'            => '△', 'square'           => '□',
        'sextile'          => '⚹', 'quincunx'         => '⚻',
        'semi_sextile'     => '∠', 'mutual_reception' => '⇌',
    ];

    public function redirect()
    {
        $profile = Profile::where('user_id', auth()->id())
            ->whereNotNull('last_used_at')
            ->orderByDesc('last_used_at')
            ->first()
            ?? Profile::where('user_id', auth()->id())->orderBy('first_name')->first();

        if ($profile === null) {
            return redirect()->route('stellar-profiles.index');
        }

        return redirect()->route('daily.show', $profile);
    }

    public function show(Profile $profile, ?string $date = null)
    {
        abort_if($profile->user_id !== auth()->id(), 403);

        $date = $date ?? now()->toDateString();
        $profile->touchLastUsed();
        $profile->loadMissing('birthCity');

        $service = app(DailyHoroscopeService::class);
        try {
            $dto = $service->build($profile, $date);
        } catch (\RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $gender = TextBlock::resolveGender($profile->gender?->value ?? null);
        $carbon = Carbon::parse($date);

        $transitTexts      = $this->buildTransitTexts($dto, $gender, $profile->id, false);
        $transitTextsShort = $this->buildTransitTexts($dto, $gender, $profile->id, true);
        $lunarText         = $this->loadLunarText($dto, $gender, $profile->id, false);
        $lunarTextShort    = $this->loadLunarText($dto, $gender, $profile->id, true);
        $tipText           = $this->loadTipText($dto, $carbon, $gender, false);
        $tipTextShort      = $this->loadTipText($dto, $carbon, $gender, true);
        $clothingText      = $this->loadClothingText($dto, $carbon, $gender, false);
        $clothingTextShort = $this->loadClothingText($dto, $carbon, $gender, true);
        $aiText            = $this->loadAiSynthesis($profile->id, $date, $gender);

        $profiles = Profile::where('user_id', auth()->id())
            ->orderByDesc('last_used_at')->orderBy('first_name')->get();

        return view('horoscope.daily.show', compact(
            'profile', 'dto', 'date', 'gender', 'profiles',
            'transitTexts', 'transitTextsShort',
            'lunarText', 'lunarTextShort',
            'tipText', 'tipTextShort',
            'clothingText', 'clothingTextShort',
            'aiText'
        ));
    }

    public function generateSynthesis(Profile $profile, string $date)
    {
        abort_if($profile->user_id !== auth()->id(), 403);

        $service = app(DailyHoroscopeService::class);
        try {
            $dto = $service->build($profile, $date);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }

        $gender       = TextBlock::resolveGender($profile->gender?->value ?? null);
        $carbon       = Carbon::parse($date);
        $natalPlanets = $profile->natalChart?->planets ?? [];

        /** @var HoroscopeSynthesisService $synthesisService */
        $synthesisService = app(HoroscopeSynthesisService::class);

        $aiResponse = $synthesisService->daily(
            transitNatalAspects: $dto->transitNatalAspects,
            retrogrades:         $dto->retrogrades,
            areasOfLife:         $dto->areasOfLife,
            natalPlanets:        $natalPlanets,
            date:                $carbon,
            moonSignName:        $dto->moon->signName,
            moonPhaseName:       $dto->moon->phaseName,
            lunarDay:            $dto->moon->lunarDay,
            profileId:           $profile->id,
            gender:              $gender,
        );

        $user = auth()->user();
        UserHoroscopeLog::create([
            'user_id'              => $user->id,
            'user_uuid'            => $user->uuid ?? null,
            'user_email'           => $user->email,
            'profile_uuid'         => $profile->uuid,
            'profile_snapshot'     => UserHoroscopeLog::snapshotProfile($profile),
            'type'                 => 'daily_synthesis',
            'premium_content'      => true,
            'premium_requested_at' => now(),
        ]);

        return response()->json(['html' => $aiResponse?->text]);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function buildTransitTexts(DailyHoroscopeDTO $dto, ?string $gender, int $profileId, bool $short): array
    {
        $items = [];

        foreach ($dto->transitNatalAspects as $asp) {
            $tGlyph   = self::BODY_GLYPHS[$asp->transitBody] ?? '?';
            $nGlyph   = self::BODY_GLYPHS[$asp->natalBody] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$asp->aspect] ?? '·';
            $aspWord  = ucfirst(str_replace('_', ' ', $asp->aspect));
            $chip     = $tGlyph . ' ' . $asp->transitName . '  ' . $aspGlyph . ' ' . $aspWord . '  ' . $nGlyph . ' natal ' . $asp->natalName;
            $key      = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
            $section  = $short ? 'transit_natal_short' : 'transit_natal';
            $block    = TextBlock::pickForProfile($key, $section, 'en', $gender, $profileId);

            $items[] = ['chip' => $chip, 'text' => $block?->text, 'type' => 'transit_natal'];
        }

        foreach ($dto->retrogrades as $rx) {
            $glyph  = self::BODY_GLYPHS[$rx->body] ?? '?';
            $sGlyph = self::SIGN_GLYPHS[$rx->signIndex] ?? '';
            $chip   = $glyph . ' ' . $rx->name . ' Rx  ·  ' . $sGlyph . ' ' . $rx->signName;
            $key    = strtolower($rx->name) . '_rx_' . strtolower($rx->signName);
            $block  = TextBlock::pickForProfile($key, $short ? 'retrograde_short' : 'retrograde', 'en', $gender, $profileId);

            $items[] = ['chip' => $chip, 'text' => $block?->text, 'type' => 'retrograde'];
        }

        foreach ($dto->transitTransitAspects as $tt) {
            $gA       = self::BODY_GLYPHS[$tt->bodyA] ?? '?';
            $gB       = self::BODY_GLYPHS[$tt->bodyB] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$tt->aspect] ?? '·';
            $aspWord  = ucfirst(str_replace('_', ' ', $tt->aspect));
            $chip     = $gA . ' ' . $tt->nameA . '  ' . $aspGlyph . ' ' . $aspWord . '  ' . $gB . ' ' . $tt->nameB;
            $key      = strtolower($tt->nameA) . '_' . $tt->aspect . '_' . strtolower($tt->nameB);
            $block    = TextBlock::pickForProfile($key, $short ? 'transit_short' : 'transit', 'en', $gender, $profileId);

            $items[] = ['chip' => $chip, 'text' => $block?->text, 'type' => 'transit_transit'];
        }

        return $items;
    }

    private function loadLunarText(DailyHoroscopeDTO $dto, ?string $gender, int $profileId, bool $short): ?string
    {
        $key   = 'moon_in_' . strtolower($dto->moon->signName);
        $block = TextBlock::pickForProfile($key, $short ? 'lunar_day_short' : 'lunar_day', 'en', $gender, $profileId);
        return $block?->text;
    }

    private function loadTipText(DailyHoroscopeDTO $dto, Carbon $carbon, ?string $gender, bool $short): ?string
    {
        $key   = strtolower($carbon->format('l')) . '_moon_in_' . strtolower($dto->moon->signName);
        $block = TextBlock::where('key', $key)
            ->where('section', $short ? 'daily_tip_short' : 'daily_tip')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();
        return $block?->text;
    }

    private function loadClothingText(DailyHoroscopeDTO $dto, Carbon $carbon, ?string $gender, bool $short): ?string
    {
        if ($dto->natalVenusSign === null) {
            return null;
        }
        $key   = strtolower($carbon->format('l')) . '_venus_in_' . strtolower(PlanetaryPosition::SIGN_NAMES[$dto->natalVenusSign] ?? '');
        $block = TextBlock::where('key', $key)
            ->where('section', $short ? 'weekday_clothing_short' : 'weekday_clothing')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();
        return $block?->text;
    }

    private function loadAiSynthesis(int $profileId, string $date, ?string $gender): ?string
    {
        $key   = 'daily_' . $profileId . '_' . $date;
        $block = TextBlock::where('key', $key)
            ->where('section', 'ai_synthesis')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();
        return $block?->text;
    }
}
