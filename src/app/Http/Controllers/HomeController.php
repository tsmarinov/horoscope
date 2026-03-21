<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\View\View;
use App\Http\Controllers\SunSignController;

class HomeController extends Controller
{
    private const URL_MAP = [
        'sun_sign' => '/horoscope/daily',
        'daily'    => '/horoscope/personal/daily',
        'weekly'   => '/horoscope/personal/weekly',
        'monthly'  => '/horoscope/personal/monthly',
        'solar'    => '/horoscope/solar',
        'weekday'  => '/horoscope/weekday',
        'lunar'    => '/lunar-calendar',
    ];

    private const ACTIVE_KEYS = ['sun_sign', 'daily', 'natal', 'lunar'];

    public function index(): View
    {
        $natalUrl = url('/natal');
        if (auth()->check()) {
            $profile  = Profile::where('user_id', auth()->id())->orderByDesc('last_used_at')->orderBy('id')->first();
            $natalUrl = $profile
                ? route('natal.show', $profile)
                : route('stellar-profiles.index');
        }

        $zodiacGlyphs   = array_column(SunSignController::SIGNS, 'glyph');
        $todaySignGlyph = $zodiacGlyphs[now()->dayOfYear % 12];

        $cards = collect(array_keys(__('ui.home.cards')))
            ->map(fn (string $key) => [
                'key'   => $key,
                'glyph' => $key === 'sun_sign' ? $todaySignGlyph : __("ui.home.cards.{$key}.glyph"),
                'title' => __("ui.home.cards.{$key}.title"),
                'desc'  => __("ui.home.cards.{$key}.desc"),
                'cta'   => __("ui.home.cards.{$key}.cta"),
                'url'      => $key === 'natal' ? $natalUrl : url(self::URL_MAP[$key] ?? "/{$key}"),
                'disabled' => !in_array($key, self::ACTIVE_KEYS),
            ]);

        return view('home', compact('cards'));
    }
}
