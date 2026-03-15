<?php

namespace App\Http\Controllers;

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
        $cards = collect(array_keys(__('ui.home.cards')))
            ->map(fn (string $key) => [
                'key'   => $key,
                'glyph' => __("ui.home.cards.{$key}.glyph"),
                'title' => __("ui.home.cards.{$key}.title"),
                'desc'  => __("ui.home.cards.{$key}.desc"),
                'cta'   => __("ui.home.cards.{$key}.cta"),
                'url'   => url(self::URL_MAP[$key] ?? "/{$key}"),
            ]);

        return view('home', compact('cards'));
    }
}
