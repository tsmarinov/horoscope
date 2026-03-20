<?php

return [
    'aspects' => [
        'conjunction'  => ['angle' => 0,   'orb' => 3.0],
        'opposition'   => ['angle' => 180, 'orb' => 3.0],
        'trine'        => ['angle' => 120, 'orb' => 3.0],
        'square'       => ['angle' => 90,  'orb' => 3.0],
        'sextile'      => ['angle' => 60,  'orb' => 3.0],
        'quincunx'     => ['angle' => 150, 'orb' => 3.0],
        'semi_sextile' => ['angle' => 30,  'orb' => 3.0],
    ],

    // Orb overrides for natal chart and transits
    'sun_orb'  => 5.0, // any aspect involving the Sun
    'moon_orb' => 5.0, // any aspect involving the Moon

    // Orb for directions and progressions (overrides everything, no mutual reception)
    'progression_orb' => 1.0,

    // AI provider configuration
    'ai' => [
        'provider' => env('AI_PROVIDER', 'claude'),
        'model'    => env('AI_MODEL', 'claude-sonnet-4-6'),
    ],

    // Modern rulerships: sign index (0=Aries…11=Pisces) => ruling planet body constant
    // Used for mutual reception detection (natal chart + transits only)
    'rulerships' => [
        0  => 4,  // Aries    → Mars
        1  => 3,  // Taurus   → Venus
        2  => 2,  // Gemini   → Mercury
        3  => 1,  // Cancer   → Moon
        4  => 0,  // Leo      → Sun
        5  => 2,  // Virgo    → Mercury
        6  => 3,  // Libra    → Venus
        7  => 9,  // Scorpio  → Pluto
        8  => 5,  // Sagittarius → Jupiter
        9  => 6,  // Capricorn   → Saturn
        10 => 7,  // Aquarius    → Uranus
        11 => 8,  // Pisces      → Neptune
    ],
];
