<?php

namespace App\Services\Horoscope;

use App\DataTransfer\Horoscope\WeekdayDTO;
use App\DataTransfer\Horoscope\WeekdayHoroscopeDTO;
use App\Models\Profile;
use App\Models\WeekdayText;
use Carbon\Carbon;

class WeekdayHoroscopeService
{
    private const DAYS = [
        1 => ['ruler' => 'Moon',    'number' => 2, 'hex' => '#b4b4c8'],
        2 => ['ruler' => 'Mars',    'number' => 9, 'hex' => '#c03030'],
        3 => ['ruler' => 'Mercury', 'number' => 5, 'hex' => '#c8a820'],
        4 => ['ruler' => 'Jupiter', 'number' => 3, 'hex' => '#2a5098'],
        5 => ['ruler' => 'Venus',   'number' => 6, 'hex' => '#d8809a'],
        6 => ['ruler' => 'Saturn',  'number' => 8, 'hex' => '#602880'],
        7 => ['ruler' => 'Sun',     'number' => 1, 'hex' => '#d89020'],
    ];

    public function build(?Profile $profile, string $date, string $language = 'en'): WeekdayHoroscopeDTO
    {
        $carbon   = Carbon::parse($date);
        $todayIso = (int) $carbon->isoFormat('E'); // 1=Mon … 7=Sun

        // Natal Venus sign for clothing tip
        $venusSign   = null;
        $profileName = '';

        if ($profile !== null) {
            $profileName = $profile->name ?? $profile->user?->name ?? 'Profile #' . $profile->id;
            foreach ($profile->natalChart?->planets ?? [] as $planet) {
                if ($planet['body'] === 3) {
                    $venusSign = $planet['sign'];
                    break;
                }
            }
        }

        $days = [];
        foreach (self::DAYS as $iso => $data) {
            $isToday = ($iso === $todayIso);
            $wt      = WeekdayText::where('iso_day', $iso)->where('language', $language)->first();
            $name        = $wt?->name        ?? $data['name']        ?? 'Day ' . $iso;
            $colors      = $wt?->colors      ?? $data['colors']      ?? '';
            $gem         = $wt?->gem         ?? $data['gem']         ?? '';
            $theme       = $wt?->theme       ?? $data['theme']       ?? '';
            $description = $wt?->description ?? $data['description'] ?? '';

            // Clothing tip key: only for today + when profile has Venus sign
            $clothingKey = null;
            if ($isToday && $venusSign !== null) {
                $signNames   = \App\Models\PlanetaryPosition::SIGN_NAMES;
                $clothingKey = strtolower($name)
                    . '_venus_in_'
                    . strtolower($signNames[$venusSign] ?? '');
            }

            $days[$iso] = new WeekdayDTO(
                isoDayOfWeek: $iso,
                name:         $name,
                ruler:        $data['ruler'],
                number:       $data['number'],
                colors:       $colors,
                hex:          $data['hex'],
                gem:          $gem,
                theme:        $theme,
                description:  $description,
                isToday:      $isToday,
                clothingTipKey: $clothingKey,
            );
        }

        return new WeekdayHoroscopeDTO(
            date:           $date,
            profileName:    $profileName,
            days:           $days,
            natalVenusSign: $venusSign,
        );
    }
}
