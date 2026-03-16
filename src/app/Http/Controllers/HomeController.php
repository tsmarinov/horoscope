<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const URL_MAP = [
        'daily'   => '/horoscope/daily',
        'weekly'  => '/horoscope/weekly',
        'monthly' => '/horoscope/monthly',
        'solar'   => '/horoscope/solar',
        'weekday' => '/horoscope/weekday',
    ];

    public function index(): View
    {
        $natalUrl = url('/natal');
        if (auth()->check()) {
            $profile  = Profile::where('user_id', auth()->id())->latest()->first();
            $natalUrl = $profile
                ? route('natal.show', $profile)
                : route('stellar-profiles.index');
        }

        $cards = collect(array_keys(__('ui.home.cards')))
            ->map(fn (string $key) => [
                'key'   => $key,
                'glyph' => __("ui.home.cards.{$key}.glyph"),
                'title' => __("ui.home.cards.{$key}.title"),
                'desc'  => __("ui.home.cards.{$key}.desc"),
                'cta'   => __("ui.home.cards.{$key}.cta"),
                'url'   => $key === 'natal' ? $natalUrl : url(self::URL_MAP[$key] ?? "/{$key}"),
            ]);

        return view('home', compact('cards'));
    }
}
