<?php
return [
    'rating_wait'      => '⚠ wait',
    'retrograde'       => 'Retrograde',
    'retrograde_short' => 'Rx',
    'aspects' => [
        'conjunction'      => 'Conjunction',
        'opposition'       => 'Opposition',
        'trine'            => 'Trine',
        'square'           => 'Square',
        'sextile'          => 'Sextile',
        'quincunx'         => 'Quincunx',
        'semi_sextile'     => 'Semi-sextile',
        'mutual_reception' => 'Mutual Reception',
    ],

    'rx_legend'    => 'Rx = Retrograde (apparent backward motion)',
    'today_mark'   => '← today',
    'no_rx'        => 'no Rx',

    'areas' => [
        'title'   => '★  AREAS OF LIFE',
    ],

    'lunar' => [
        'day_of' => 'Day :day / 30',
        'moon_in' => 'Moon in',
    ],

    'weekday' => [
        'footer' => '→ Lunar calendar   → Daily horoscope   → Weekly horoscope',
    ],

    'natal' => [
        'footer_links' => [
            '→ Daily forecast',
            '→ Weekly forecast',
            '→ Monthly forecast',
            '→ Yearly forecast',
            '→ Lunar calendar',
        ],
    ],

    'solar' => [
        'ai_overview'     => 'YEARLY OVERVIEW',
        'progressions'    => 'PROGRESSIONS',
        'arc_directions'  => 'SOLAR ARC DIRECTIONS',
        'factors'         => 'SOLAR RETURN FACTORS',
        'lunations_title' => 'ECLIPSES & LUNATIONS',
        'key_transits'    => 'KEY TRANSITS BY QUARTER',
        'singleton'       => 'Singleton',
        'missing_element' => 'Missing element',
        'natal_label'     => 'natal',
        'dispositor'      => 'Dispositor',
    ],
    'daily' => [
        'ai_overview' => "TODAY'S OVERVIEW",
    ],
    'weekly' => [
        'ai_overview' => 'WEEKLY OVERVIEW',
    ],
    'monthly' => [
        'ai_overview' => 'MONTHLY OVERVIEW',
    ],
    'keydates' => [
        'title' => 'KEY DATES',
    ],
    'quarters' => [
        'q1' => 'Q1 · Jan–Mar',
        'q2' => 'Q2 · Apr–Jun',
        'q3' => 'Q3 · Jul–Sep',
        'q4' => 'Q4 · Oct–Dec',
    ],
    'synastry' => [
        'title'    => 'Synastry',
        'subtitle' => 'Compatibility · astrological relationship profile',
        'label_a'  => 'A',
        'label_b'  => 'B',

        'positions_title'   => 'POSITIONS',
        'categories_title'  => 'RELATIONSHIPS',
        'biwheel_label'     => '[  bi-wheel : A (inner) · B (outer)  ]',

        'a_to_b'  => 'A → B  —  how :name_a influences :name_b',
        'b_to_a'  => 'B → A  —  how :name_b influences :name_a',
        'mutual'  => 'Mutual  —  resonance / tension between same archetypes',

        'intro_title'     => 'INTRO  [pre-gen · ☉A × ☉B · free]',
        'intro_text_tag'  => '[text: intro · 144 variants · Sun × Sun ordered pair]',
        'ai_divider'      => '  AI L1 (premium)',
        'synthesis_title' => 'SYNTHESIS  [live Haiku · cached · replaces intro]',
        'synthesis_text_tag' => '[text: synthesis · all aspects · focus by type]',

        'cross_placements_title' => 'CROSS-CHART PLACEMENTS',
        'asc_placements_title'   => 'ASC CROSS-PLACEMENTS',
        'planet_in_house'        => ':glyph :name :label  (:name in :sign)  →  H:house in the chart of :other',
        'asc_falls_in'           => 'ASC :label  (:name · :sign)  →  falls in H:house in the chart of :other',

        'no_aspects'        => '(no aspects)',
        'no_mutual_aspects' => '(no aspects between same-archetype planets)',

        'synthesis_placeholder' => '[text blocks not generated yet]',

        'partner_archetypes_title' => 'PARTNER ARCHETYPES',
        'male_venus_label'         => 'attraction to women',
        'male_moon_label'          => 'the woman he would choose',
        'female_mars_label'        => 'attraction to men',
        'female_sun_label'         => 'the man she would choose',
        'seventh_lord_label'       => 'H7 ruler — partner type',
        'partner_text_tag'         => '[text: partner archetype · sign]',

        'types' => [
            'general'       => 'General',
            'romantic'      => 'Romantic',
            'business'      => 'Business',
            'friends'       => 'Friends',
            'family'        => 'Family',
            'spiritual'     => 'Spiritual',
            'communication' => 'Communication',
            'emotion'       => 'Emotion',
            'sexual'        => 'Sexual',
            'creative'      => 'Creative',
        ],
        'type_icons' => [
            'general'       => '✦',
            'romantic'      => '❤',
            'business'      => '🤝',
            'friends'       => '🫂',
            'family'        => '👨‍👩‍👧',
            'spiritual'     => '🔮',
            'communication' => '💬',
            'emotion'       => '🌙',
            'sexual'        => '🔥',
            'creative'      => '🎨',
        ],
    ],

    'retrograde' => [
        'title'          => 'Retrograde Calendar',
        'subtitle'       => 'Universal · no birth data required',
        'active_now'     => 'ACTIVE NOW',
        'active_none'    => 'No active retrograde periods today.',
        'sr'             => 'SR',
        'sd'             => 'SD',
        'station_note'   => 'SR = planet stations before going retrograde  ·  SD = stations before going direct. These ~3–5 days are the most intense.',
        'section_inner'  => 'INNER PLANETS',
        'section_outer'  => 'OUTER & SOCIAL PLANETS',
        'personal_title' => 'HOW IT AFFECTS YOU',
        'no_rx'          => 'no Rx in :year',
        'legend_today'   => '│ today',
        'legend_rx'      => '▓ Rx period',
        'legend_station' => 'SR/SD = station days',
        'planet_theme' => [
            'mercury' => 'Communication · Contracts · Technology',
            'venus'   => 'Love · Values · Beauty',
            'mars'    => 'Action · Energy · Initiative',
            'jupiter' => 'Expansion · Success · Wisdom',
            'saturn'  => 'Structure · Discipline · Karma',
            'uranus'  => 'Change · Freedom · Innovation',
            'neptune' => 'Dreams · Spirituality · Illusions',
            'pluto'   => 'Transformation · Power · Rebirth',
        ],
        'planet_desc' => [
            'mercury' => 'Mercury Retrograde is an invitation to reconsider words, contracts, and plans. Avoid new starts in communication and technology. A good time for revision, review, and completing unfinished work.',
            'venus'   => 'Venus Retrograde turns attention inward to values and relationships. Avoid new romantic commitments or major purchases. A good time to reassess what and who truly matters.',
            'mars'    => 'Mars Retrograde slows initiative and redirects energy inward. Avoid launching new projects or confrontations. A good time to review strategy and consolidate existing efforts.',
            'jupiter' => 'Jupiter Retrograde turns expansion inward — reconsider beliefs and growth strategies. Success requires depth, not just enthusiasm. A good time for philosophical reflection.',
            'saturn'  => 'Saturn Retrograde surfaces unfinished tasks and structural weaknesses. A good time to review commitments, work, and long-term goals. Past obligations come to the fore.',
            'uranus'  => 'Uranus Retrograde slows revolution — changes become more gradual and internal. A good time to reconsider personal freedom and beliefs without external provocation.',
            'neptune' => 'Neptune Retrograde disperses the fog — illusions become more visible. A good time for spiritual clarity and honest self-knowledge. Avoid unrealistic projects.',
            'pluto'   => 'Pluto Retrograde surfaces hidden dynamics and questions of power. A period of deep internal transformation, especially around control, loss, and release.',
        ],
    ],
];
