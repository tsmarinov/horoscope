@extends('layouts.app')

@section('title', $profile->name . ' — Natal Chart')
@section('description', 'Natal chart for ' . $profile->name)
@section('main_class', 'page-wrap-narrow')

@php
    $bodyGlyphs = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'];
    $bodyNames  = ['Sun','Moon','Mercury','Venus','Mars','Jupiter','Saturn','Uranus','Neptune','Pluto','Chiron','North Node','Lilith'];
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $houseNames = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $aspectGlyphs = [
        'conjunction'  => '☌', 'opposition'   => '☍', 'trine'       => '△',
        'square'       => '□', 'sextile'      => '⚹', 'quincunx'    => '⚻',
        'semi_sextile'     => '⚺', 'semisquare'      => '∠', 'sesquiquadrate' => '⊼',
        'mutual_reception' => '⇌',
    ];
    $planets = $chart->planets ?? [];
    $aspects = $chart->aspects ?? [];
    $houses  = $chart->houses  ?? [];
    $sign    = $profile->sunSign();
    $age     = $profile->age();
    // Wheel data
    $wheelAsc     = $chart->ascendant;
    $wheelPlanets = array_values(array_map(fn($p) => [
        'body' => (int)($p['body'] ?? 0),
        'lon'  => (float)(($p['sign'] ?? 0) * 30 + ($p['degree'] ?? 0)),
        'rx'   => (bool)($p['is_retrograde'] ?? false),
    ], $planets));
    $wheelHouses  = array_values($houses);
    $wheelAspects = array_values(array_map(fn($a) => [
        'a'    => (int)($a['body_a'] ?? 0),
        'b'    => (int)($a['body_b'] ?? 0),
        'type' => $a['aspect'] ?? '',
    ], $aspects));
@endphp

@section('content')
    {{-- Profile switcher --}}
    @php
        $profileList = $profiles->map(fn($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'sub'   => $p->birth_date?->format('M j, Y') ?? '',
            'url'   => route('natal.show', $p),
            'active'=> $p->id === $profile->id,
        ])->values()->toArray();
    @endphp
    <div class="switcher"
         x-data="{
            open: false,
            search: '',
            profiles: {{ Js::from($profileList) }},
            get filtered() {
                if (!this.search) return this.profiles;
                const q = this.search.toLowerCase();
                return this.profiles.filter(p => p.name.toLowerCase().includes(q) || p.sub.toLowerCase().includes(q));
            },
            current: {{ Js::from(['name' => $profile->name, 'sub' => $profile->birth_date?->format('M j, Y') ?? '']) }}
         }"
         @click.outside="open = false">

        <div class="switcher-wrap">

        {{-- Trigger --}}
        <button @click="open = !open" class="switcher-btn">
            <span>
                <span x-text="current.name" class="switcher-btn-name"></span>
                <span x-text="current.sub ? ' · ' + current.sub : ''" class="switcher-btn-sub"></span>
            </span>
            <span class="switcher-arrow" x-text="open ? '▲' : '▼'"></span>
        </button>

        {{-- Dropdown --}}
        <div x-show="open" x-cloak class="switcher-dropdown">

            {{-- Search (only when many profiles) --}}
            @if($profiles->count() > 3)
            <div class="switcher-search-wrap">
                <input x-ref="search" x-model="search" @keydown.escape="open = false"
                       placeholder="Search profiles…"
                       class="switcher-input"
                       x-init="$watch('open', v => v && $nextTick(() => $refs.search.focus()))">
            </div>
            @endif

            {{-- List --}}
            <div class="switcher-list">
                <template x-for="p in filtered" :key="p.id">
                    <a :href="p.url"
                       class="switcher-item"
                       :class="p.active ? 'active' : ''"
                       @mouseover="$el.style.background='var(--theme-raised)'"
                       @mouseout="$el.style.background=p.active ? 'var(--theme-raised)' : 'transparent'">
                        <span>
                            <span x-text="p.name" class="switcher-item-name"></span>
                            <span x-text="p.sub ? ' · ' + p.sub : ''" class="switcher-btn-sub"></span>
                        </span>
                        <span x-show="p.active" class="switcher-check">✓</span>
                    </a>
                </template>
                <div x-show="search && filtered.length === 0" class="switcher-empty">
                    No profiles found
                </div>
            </div>

            {{-- Add profile --}}
            @auth
            <a href="{{ route('stellar-profiles.index') }}" class="switcher-add-link">{{ __('ui.switcher.add_profile') }}</a>
            @else
            <a href="{{ route('register') }}" class="switcher-add-link">{{ __('ui.switcher.register_to_add') }}</a>
            @endauth
        </div>
        </div>{{-- /switcher-wrap --}}
    </div>

    {{-- Header --}}
    <div class="page-header">
        <div class="page-header-inner">
            <a href="{{ route('stellar-profiles.index', ['edit' => $profile->uuid]) }}#{{ $profile->uuid }}"
               class="edit-profile-link">
                ← Edit Profile
            </a>
            <div class="page-type-label">{{ __('ui.natal.page_title') }}</div>
            <h1 class="font-display profile-name">
                {{ $profile->name }}
            </h1>
            <div class="profile-meta">
                    @if($sign)<span>{{ $sign['glyph'] }} {{ $sign['name'] }}</span><span>·</span>@endif
                    <span>{{ $profile->birth_date?->format('M j, Y') }}</span>
                    @if($age !== null)<span>· {{ $age }} y.o.</span>@endif
                    @if($profile->birth_time)<span>· {{ substr($profile->birth_time, 0, 5) }}</span>@endif
                    @if($profile->birthCity)<span>· {{ $profile->birthCity->name }}</span>@endif
                    @if($chart->ascendant !== null)
                        @php
                            $ascSign  = (int)floor($chart->ascendant / 30);
                            $ascDeg   = (int)floor(fmod($chart->ascendant, 30));
                            $ascMin   = (int)round((fmod($chart->ascendant, 30) - $ascDeg) * 60);
                            if ($ascMin >= 60) { $ascDeg++; $ascMin = 0; }
                        @endphp
                        <span>· ASC {{ $signGlyphs[$ascSign] }} {{ $signNames[$ascSign] }} {{ $ascDeg }}°{{ str_pad($ascMin, 2, '0', STR_PAD_LEFT) }}'</span>
                    @endif
            </div>
            <div class="pdf-row">
                <a id="top-pdf-btn"
                   href="{{ route('natal.pdf', $profile) }}"
                   onclick="showPdfLoading()"
                   class="btn-pdf"
                   title="{{ __('ui.natal.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
            </div>
        </div>
    </div>

    {{-- Natal Wheel --}}
    <div class="card card-wheel">
        <div class="section-label wheel-label">Natal Chart</div>
        <svg id="natal-wheel" viewBox="0 0 320 320" width="100%"
             class="wheel-svg"
             aria-label="Natal chart wheel"></svg>
        @if(!$wheelAsc)
            <div class="chart-unlock-hint">
                <a href="{{ route('stellar-profiles.index', ['edit' => $profile->uuid]) }}" class="chart-unlock-link">
                    🔒 Add birth time &amp; place to unlock houses
                </a>
            </div>
        @endif
        <div style="text-align:center;margin-top:0.5rem;display:flex;justify-content:center;gap:1.2rem">
            <a href="{{ route('daily.show', $profile) }}" style="font-size:0.78rem;color:var(--theme-muted);text-decoration:none;opacity:0.7" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">Daily Horoscope →</a>
        </div>
    </div>

    {{-- Planets --}}
    <div class="card card-flush">
        <div class="card-header">
            <div class="section-label">Planets</div>
        </div>
        <div class="card-scroll">
            <table class="ct">
                <thead>
                    <tr>
                        <th class="ct-th-l">Planet</th>
                        <th class="ct-th-l">Sign</th>
                        <th class="ct-th-r">Position</th>
                        @if(count($houses))<th>House</th>@endif
                        <th>Rx</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($planets as $planet)
                    @php
                        $deg = floor($planet['degree']);
                        $minFrac = ($planet['degree'] - $deg) * 60;
                        $min = floor($minFrac);
                        $sec = round(($minFrac - $min) * 60);
                        if ($sec >= 60) { $min++; $sec = 0; }
                        if ($min >= 60) { $deg++; $min = 0; }
                    @endphp
                    <tr>
                        <td>
                            <span class="ct-planet-glyph">{{ $bodyGlyphs[$planet['body']] ?? '?' }}</span>
                            <span class="ct-planet-name">{{ $bodyNames[$planet['body']] ?? 'Body ' . $planet['body'] }}</span>
                        </td>
                        <td class="ct-muted">
                            <span class="ct-sign-glyph">{{ $signGlyphs[$planet['sign']] ?? '' }}</span>{{ $signNames[$planet['sign']] ?? '' }}
                        </td>
                        <td class="ct-num">{{ $deg }}°{{ str_pad($min, 2, '0', STR_PAD_LEFT) }}'{{ str_pad($sec, 2, '0', STR_PAD_LEFT) }}"</td>
                        @if(count($houses))
                        <td class="ct-house">{{ $planet['house'] !== null ? ($houseNames[$planet['house'] - 1] ?? $planet['house']) : '—' }}</td>
                        @endif
                        <td class="{{ $planet['is_retrograde'] ? 'ct-rx-on' : 'ct-rx-off' }}">{{ $planet['is_retrograde'] ? 'Rx' : '·' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Houses (Tier 3 only) --}}
    @if(count($houses) && $chart->ascendant !== null)
    <div class="card card-mt">
        <div class="section-label">Houses (Placidus)</div>
        <div class="planet-grid">
            @foreach($houses as $i => $cusp)
            @php
                $sign = (int) floor($cusp / 30);
                $deg  = floor(fmod($cusp, 30));
                $min  = round((fmod($cusp, 30) - $deg) * 60);
                if ($min >= 60) { $deg++; $min = 0; }
            @endphp
            <div class="house-row{{ in_array($i, [0,3,6,9]) ? ' house-row-angle' : '' }}">
                <span class="house-num">{{ $houseNames[$i] }}</span>
                <span><span class="ct-sign-glyph">{{ $signGlyphs[$sign] }}</span>{{ $signNames[$sign] }}</span>
                <span style="color:var(--theme-muted);font-size:0.78rem">{{ $deg }}°{{ str_pad($min,2,'0',STR_PAD_LEFT) }}'</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Aspects --}}
    @if(count($aspects))
    <div class="card card-mt">
        <div class="section-label">{{ __('ui.natal.section_aspects') }}</div>
        <div class="aspect-list">
            @foreach($aspects as $asp)
            @php
                $a   = $asp['body_a'] ?? 0;
                $b   = $asp['body_b'] ?? 0;
                $orb = round(abs($asp['orb'] ?? 0), 2);
                $type = $asp['aspect'] ?? '';
                $glyph = $aspectGlyphs[$type] ?? '∗';
            @endphp
            @continue($type === 'mutual_reception')
            <div class="aspect-row">
                <span class="glyph glyph-accent">{{ $bodyGlyphs[$a] ?? '?' }}</span>
                <span class="asp-name">{{ $bodyNames[$a] ?? $a }}</span>
                <span class="glyph glyph-neutral">{{ $glyph }}</span>
                <span class="asp-name">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                <span class="glyph glyph-accent">{{ $bodyGlyphs[$b] ?? '?' }}</span>
                <span class="asp-name">{{ $bodyNames[$b] ?? $b }}</span>
                <span class="asp-orb">{{ $orb }}°</span>
                @if(!empty($asp['applying']))
                    <span class="asp-dir asp-dir-on">↑</span>
                @else
                    <span class="asp-dir">↓</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Premium button + Short Version toggle + short PDF --}}
    <div class="action-row">
        @include('partials.premium-button', ['context' => 'natal', 'generated' => ($portraitFull !== null && $portraitShort !== null)])

        {{-- Toggle pill --}}
        <label class="toggle-label">
            <span class="toggle-switch">
                <input id="short-ver-chk" type="checkbox" onchange="stelToggleAi(this.checked)"
                       class="toggle-input">
                <span id="short-ver-track" class="toggle-track"></span>
                <span id="short-ver-thumb" class="toggle-thumb"></span>
            </span>
            Short Version
        </label>

        {{-- PDF for current version --}}
        <a id="short-pdf-btn"
           href="{{ route('natal.pdf', $profile) }}"
           onclick="showPdfLoading()"
           class="btn-pdf"
           title="{{ __('ui.natal.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
    </div>

    {{-- Portrait section --}}
    @if($portraitFull !== null || $portraitShort !== null)
    <div class="card card-section card-ai" id="portrait-card" @if($portraitFull === null) style="display:none" @endif>
        <div id="portrait-full" class="prose" @if($portraitFull === null) style="display:none" @endif>
            {!! $portraitFull ?? '' !!}
        </div>
        <div id="portrait-short" class="prose" style="display:none">
            {!! $portraitShort ?? '' !!}
        </div>
    </div>
    @else
    <div id="portrait-card" class="card card-section card-ai" style="display:none">
        <div id="portrait-full" style="display:none"></div>
        <div id="portrait-short" style="display:none"></div>
    </div>
    @endif

    {{-- House Lords --}}
    @foreach([['full', $houseLords], ['short', $houseLordsShort]] as [$ver, $items])
    @if(count($items))
    <div class="card card-section" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label">{{ __('ui.natal.section_house_lords') }}</div>
        @foreach($items as $hl)
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="chip-transit">{{ $hl['label'] }}</div>
            <p class="prose">{{ $hl['text'] }}</p>
        </div>
        @endforeach
    </div>
    @endif
    @endforeach

    {{-- House Lord Aspects --}}
    @foreach([['full', $houseLordAspects], ['short', $houseLordAspectsShort]] as [$ver, $items])
    @if(count($items))
    <div class="card card-section" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label">{{ __('ui.natal.section_house_lord_aspects') }}</div>
        @foreach($items as $item)
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="chip-transit">{{ $item['label'] }}</div>
            <div class="sub-label">{{ $item['lord'] }} · {{ ucfirst(str_replace('_', '-', $item['aspect'])) }} · {{ $item['other'] }}</div>
            <p class="prose">{!! $item['text'] !!}</p>
        </div>
        @endforeach
    </div>
    @endif
    @endforeach

    {{-- Singleton / Missing Element --}}
    @php
        $elementEmoji = ['Fire'=>'🔥','Earth'=>'🌿','Air'=>'💨','Water'=>'💧'];
    @endphp
    @foreach([['full', $singletons], ['short', $singletonsShort]] as [$ver, $items])
    @if(count($items))
    <div class="card card-section" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label">Element Pattern</div>
        <div class="stack">
        @foreach($items as $s)
        <div>
            <div class="ep-header">
                <span>{{ $elementEmoji[$s['element']] ?? '' }}</span>
                @if($s['type'] === 'singleton')
                    <span class="ep-label">Singleton: {{ $bodyGlyphs[$s['planet']['body']] ?? '' }} {{ $bodyNames[$s['planet']['body']] ?? '' }} ({{ $s['element'] }})</span>
                @else
                    <span class="ep-label ep-label-muted">Missing element: {{ $s['element'] }}</span>
                @endif
            </div>
            @if($s['text'])
            <p class="prose">{{ $s['text'] }}</p>
            @endif
        </div>
        @endforeach
        </div>
    </div>
    @endif
    @endforeach

    {{-- Angle Aspects (ASC / MC) --}}
    @foreach([['full', $angleAspectTexts], ['short', $angleAspectTextsShort]] as [$ver, $items])
    @if(count($items))
    <div class="card card-section" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label">{{ __('ui.natal.section_angle_aspects') }}</div>
        @foreach($items as $item)
        @php
            $aspLabel = ucfirst(str_replace('_', '-', $item['aspect']));
            $aspGlyph = $aspectGlyphs[$item['aspect']] ?? '∗';
        @endphp
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="chip-transit">{{ $item['planet'] }} {{ $aspGlyph }} {{ $aspLabel }} {{ $item['angle'] }}</div>
            <p class="prose">{!! $item['text'] !!}</p>
        </div>
        @endforeach
    </div>
    @endif
    @endforeach

    {{-- Natal Aspects --}}
    @foreach([['full', $aspectTexts], ['short', $aspectTextsShort]] as [$ver, $items])
    @if(count($items))
    <div class="card card-section" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label">{{ __('ui.natal.section_aspects') }}</div>
        @foreach($items as $item)
        @php
            $aspLabel = __('ui.aspects.' . $item['aspect'], [], 'en') ?: ucwords(str_replace('_', ' ', $item['aspect']));
            $aspGlyph = $aspectGlyphs[$item['aspect']] ?? '∗';
            $nameA    = \App\Models\PlanetaryPosition::BODY_NAMES[$item['bodyA']] ?? '';
            $nameB    = \App\Models\PlanetaryPosition::BODY_NAMES[$item['bodyB']] ?? '';
        @endphp
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="chip-transit">{{ $nameA }} {{ $aspGlyph }} {{ $aspLabel }} {{ $nameB }}</div>
            @if($item['text'])
            <p class="prose">{!! $item['text'] !!}</p>
            @endif
        </div>
        @endforeach
    </div>
    @endif
    @endforeach

    {{-- PDF button after last data card --}}
    <div class="pdf-row-end">
        <a id="bottom-pdf-btn"
           href="{{ route('natal.pdf', $profile) }}"
           onclick="showPdfLoading()"
           class="btn-pdf"
           title="{{ __('ui.natal.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
    </div>

    {{-- Forecast links --}}
    @php $activeForecasts = ['/horoscope/daily']; @endphp
    <div class="card card-section card-content">
        <div class="section-label">{{ __('ui.natal.forecasts_title') }}</div>
        <div class="forecast-list">
            @foreach([
                [__('ui.natal.forecast_links.daily'),   '/horoscope/daily'],
                [__('ui.natal.forecast_links.weekly'),  '/horoscope/weekly'],
                [__('ui.natal.forecast_links.monthly'), '/horoscope/monthly'],
                [__('ui.natal.forecast_links.solar'),   '/horoscope/solar'],
            ] as [$label, $href])
            @if(in_array($href, $activeForecasts))
            <a href="{{ url($href) }}" class="forecast-link">
                <span class="forecast-arrow">→</span> {{ $label }}
            </a>
            @else
            <span class="forecast-link forecast-link-disabled">
                <span class="forecast-arrow">→</span> {{ $label }}
            </span>
            @endif
            @endforeach
        </div>
    </div>

    <div class="back-link-row">
        <a href="{{ route('stellar-profiles.index') }}" class="back-link">{{ __('ui.natal.back_to_profiles') }}</a>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const ascLon = {{ $wheelAsc !== null ? (float) $wheelAsc : 'null' }};
    const PL     = @json($wheelPlanets);
    const HS     = @json($wheelHouses);
    const AS     = @json($wheelAspects);

    function draw() {
        const NS  = 'http://www.w3.org/2000/svg';
        const CX  = 160, CY = 160;
        const RO   = 154;  // ring 5 outer
        const RZ   = 136;  // ring 5 inner / ring 4 outer  (zodiac ring, ~18px)
        const R4IN = 120;  // ring 4 inner / ring 3 outer  (cusp ring, ~16px)
        const R3IN = 72;   // ring 3 inner / ring 2 outer  (planet ring, ~48px)
        const RPG  = 112;  // planet glyph center — near outer edge of ring 3
        const RH   = 67;   // house number center = (R3IN+RC)/2
        const RC   = 62;   // center circle  (ring 1 = aspect web)

        const ascEff = (ascLon !== null) ? ascLon : 0;

        const svg = document.getElementById('natal-wheel');
        if (!svg) return;
        svg.innerHTML = '';

        const st  = getComputedStyle(document.documentElement);
        const get = v => st.getPropertyValue(v).trim();
        const C = {
            bg:     get('--theme-bg')     || '#f0edf5',
            card:   get('--theme-card')   || '#ffffff',
            raised: get('--theme-raised') || '#ede8f5',
            border: get('--theme-border') || '#c8c0d8',
            text:   get('--theme-text')   || '#0f0e14',
            muted:  get('--theme-muted')  || '#6b6880',
        };
        const ACCENT = '#6a329f';

    function l2a(lon) {
        return ((180 - (lon - ascEff)) % 360 + 360) % 360;
    }
    function pol(deg, r) {
        const a = deg * Math.PI / 180;
        return [+(CX + r * Math.cos(a)).toFixed(2), +(CY + r * Math.sin(a)).toFixed(2)];
    }
    function mk(tag, attrs) {
        const e = document.createElementNS(NS, tag);
        for (const [k, v] of Object.entries(attrs)) e.setAttribute(k, String(v));
        svg.appendChild(e);
        return e;
    }
    function tx(x, y, t, attrs) {
        const e = document.createElementNS(NS, 'text');
        e.setAttribute('x', x); e.setAttribute('y', y); e.textContent = t;
        for (const [k, v] of Object.entries(attrs)) e.setAttribute(k, String(v));
        svg.appendChild(e);
    }
    function sector(rI, rO, aSt, aEn, fill, stroke) {
        const span = ((aSt - aEn) + 360) % 360;
        const lg   = span > 180 ? 1 : 0;
        const [x1,y1]=pol(aSt,rO), [x2,y2]=pol(aEn,rO);
        const [x3,y3]=pol(aEn,rI), [x4,y4]=pol(aSt,rI);
        mk('path', {
            d: `M${x1} ${y1}A${rO} ${rO} 0 ${lg} 0 ${x2} ${y2}L${x3} ${y3}A${rI} ${rI} 0 ${lg} 1 ${x4} ${y4}Z`,
            fill, stroke, 'stroke-width': '0.25',
        });
    }

    // Sign element colors: Fire=red, Earth=green, Air=yellow, Water=blue
    const SIGN_ELEM = [0,1,2,3, 0,1,2,3, 0,1,2,3];
    const ELEM_C    = ['#c43030','#287838','#b08010','#1d5fa8'];
    // \uFE0E = text presentation selector — prevents Twemoji emoji rendering
    const SIGN_G = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'].map(g => g + '\uFE0E');

    // ── Background ───────────────────────────────────────────────────────────
    mk('circle', {cx:CX, cy:CY, r:RO, fill:C.bg, stroke:C.border, 'stroke-width':'0.2'});

    // ── Zodiac ring — alternating sectors ────────────────────────────────────
    for (let s = 0; s < 12; s++) {
        sector(RZ, RO, l2a(s * 30), l2a((s + 1) * 30),
               s % 2 === 0 ? C.raised : C.card, C.border);
    }

    // ── Tick marks: 360° fine scale from OUTER edge inward ───────────────────
    // Drawn ON the zodiac ring (from RO inward) so they're never covered
    for (let deg = 0; deg < 360; deg++) {
        const a      = l2a(deg);
        const isTen  = deg % 10 === 0;
        const isFive = deg % 5  === 0;
        const len    = isTen ? 5.5 : isFive ? 3.5 : 2;
        const [x1,y1] = pol(a, RO);
        const [x2,y2] = pol(a, RO - len);
        mk('line', {x1,y1,x2,y2,
            stroke: C.border,
            'stroke-width': isTen ? '0.9' : isFive ? '0.7' : '0.5',
        });
    }

    // ── Sign glyphs — colored by element ─────────────────────────────────────
    // Centered in lower portion of zodiac ring (below tick area)
    const RGLYPH = RZ + 7; // centered in glyph zone (below tick tips)
    for (let s = 0; s < 12; s++) {
        const [gx, gy] = pol(l2a(s * 30 + 15), RGLYPH);
        tx(gx, gy, SIGN_G[s], {
            'text-anchor':'middle', 'dominant-baseline':'central',
            'font-size':'10', 'fill': ELEM_C[SIGN_ELEM[s]],
            'font-family':'serif', 'pointer-events':'none',
        });
    }
    mk('circle', {cx:CX, cy:CY, r:RZ, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

    // ── Rings 4+3+2 background ────────────────────────────────────────────────
    mk('circle', {cx:CX, cy:CY, r:RZ, fill:C.card, stroke:'none'});

    const HOUSE_N = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    const ANGULAR = new Set([0,3,6,9]);

    if (HS.length === 12) {
    // ── Separator: Ring 5 / Ring 4 ────────────────────────────────────────────
    mk('circle', {cx:CX, cy:CY, r:R4IN, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

    // ── Separator: Ring 3 / Ring 2 ────────────────────────────────────────────
    mk('circle', {cx:CX, cy:CY, r:R3IN, fill:'none', stroke:C.border, 'stroke-width':'0.4'});
    }

    // ── House cusps (R4IN→RC), Ring-4 labels, Ring-2 numbers ─────────────────
    if (HS.length === 12) {
        const R4MID = (RZ + R4IN) / 2;   // 128 — center of ring 4
        for (let h = 0; h < 12; h++) {
            const a   = l2a(HS[h]);
            const isA = ANGULAR.has(h);

            // Cusp lines from Ring-4 inner edge to center circle
            const [x1,y1] = pol(a, R4IN);
            const [x2,y2] = pol(a, RC);
            mk('line', {x1,y1,x2,y2,
                stroke: isA ? ACCENT : C.border,
                'stroke-width': isA ? '1.2' : '0.6',
            });

            // Ring 4 — degree°min' (outer half)
            const cDeg  = Math.floor(HS[h] % 30);
            const cMin  = Math.round(((HS[h] % 30) - cDeg) * 60);
            const cSign = Math.floor(HS[h] / 30) % 12;
            const [dlx, dly] = pol(a, R4MID + 2);
            tx(dlx, dly, `${cDeg}°${String(cMin).padStart(2,'0')}'`, {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'4.5', 'fill': isA ? ACCENT : C.muted,
                'font-family':'sans-serif', 'pointer-events':'none',
            });
            // Ring 4 — sign glyph (inner half)
            const [sgx, sgy] = pol(a, R4MID - 4);
            tx(sgx, sgy, SIGN_G[cSign], {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'5.5', 'fill': isA ? ACCENT : ELEM_C[SIGN_ELEM[cSign]],
                'font-family':'serif', 'pointer-events':'none',
            });

            // Ring 2 — house number: drawn after loop (needs sorted order)
        }

        // Sort house indices by ecliptic longitude so arc midpoints are correct
        // (Placidus cusps are NOT in ascending longitude order by house number)
        const sortedIdx = HS.map((_, i) => i).sort((a, b) => HS[a] - HS[b]);
        for (let i = 0; i < 12; i++) {
            const hIdx  = sortedIdx[i];
            const nIdx  = sortedIdx[(i + 1) % 12];
            const span  = ((HS[nIdx] - HS[hIdx]) + 360) % 360;
            const midLon = HS[hIdx] + span / 2;
            const [nx, ny] = pol(l2a(midLon), RH);
            tx(nx, ny, HOUSE_N[hIdx], {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'7.5', 'fill': C.muted,
                'font-family':'sans-serif', 'font-weight':'normal',
                'pointer-events':'none',
            });
        }
    }

    // ── Aspect colors — Zet convention ───────────────────────────────────────
    // Red = hard (opposition, square, semisquare, sesquiquadrate)
    // Blue = soft (conjunction, trine, sextile, semisextile)
    // Green = quincunx
    const ASP_C = {
        conjunction:    { color:'#2060c0', w:'1.0', op:'0.80' },
        opposition:     { color:'#c02020', w:'1.0', op:'0.80' },
        trine:          { color:'#2060c0', w:'0.8', op:'0.75' },
        square:         { color:'#c02020', w:'0.8', op:'0.75' },
        sextile:        { color:'#2060c0', w:'0.7', op:'0.75' },
        quincunx:       { color:'#208040', w:'0.6', op:'0.60' },
        semi_sextile:   { color:'#2060c0', w:'0.5', op:'0.40' },
        semisquare:     { color:'#c02020', w:'0.5', op:'0.45' },
        sesquiquadrate: { color:'#c02020', w:'0.5', op:'0.45' },
    };
    const lonMap = {};
    for (const p of PL) lonMap[p.body] = p.lon;

    // Center circle fill — BEFORE aspect lines
    mk('circle', {cx:CX, cy:CY, r:RC, fill:C.card, stroke:'none'});

    // ── Aspect glyph symbol at midpoint of each line ─────────────────────────
    function aspGlyph(x1, y1, x2, y2, type, color) {
        const mx = +((x1 + x2) / 2).toFixed(1);
        const my = +((y1 + y2) / 2).toFixed(1);
        const s  = 3.8, sw = '0.8';
        mk('circle', {cx:mx, cy:my, r:String((s * 1.15).toFixed(1)), fill:'none', stroke:'none'});
        if (type === 'trine') {
            const h = s * 0.9;
            mk('polygon', {
                points:`${mx},${(my-h).toFixed(1)} ${(mx-h*0.866).toFixed(1)},${(my+h*0.5).toFixed(1)} ${(mx+h*0.866).toFixed(1)},${(my+h*0.5).toFixed(1)}`,
                fill:'none', stroke:color, 'stroke-width':sw, 'stroke-linejoin':'round', 'pointer-events':'none',
            });
        } else if (type === 'square') {
            mk('rect', {x:(mx-s*0.72).toFixed(1), y:(my-s*0.72).toFixed(1),
                width:String((2*s*0.72).toFixed(1)), height:String((2*s*0.72).toFixed(1)),
                fill:'none', stroke:color, 'stroke-width':sw, 'pointer-events':'none'});
        } else if (type === 'sextile') {
            for (let i = 0; i < 6; i++) {
                const a = i * 60 * Math.PI / 180;
                mk('line', {x1:mx, y1:my,
                    x2:+(mx + s * Math.cos(a)).toFixed(1), y2:+(my + s * Math.sin(a)).toFixed(1),
                    stroke:color, 'stroke-width':sw, 'pointer-events':'none'});
            }
        } else if (type === 'conjunction') {
            mk('circle', {cx:mx, cy:my, r:String(s.toFixed(1)), fill:'none', stroke:color, 'stroke-width':sw});
            mk('circle', {cx:mx, cy:my, r:'1.4', fill:color, stroke:'none'});
        } else if (type === 'opposition') {
            mk('line', {x1:(mx-s).toFixed(1), y1:my, x2:(mx+s).toFixed(1), y2:my, stroke:color, 'stroke-width':sw});
            mk('circle', {cx:mx, cy:my, r:'1.4', fill:color, stroke:'none'});
        } else if (type === 'quincunx') {
            mk('line', {x1:(mx-s*0.5).toFixed(1), y1:(my+s*0.5).toFixed(1), x2:mx, y2:(my-s*0.5).toFixed(1), stroke:color, 'stroke-width':'0.9', 'pointer-events':'none'});
            mk('line', {x1:(mx+s*0.5).toFixed(1), y1:(my+s*0.5).toFixed(1), x2:mx, y2:(my-s*0.5).toFixed(1), stroke:color, 'stroke-width':'0.9', 'pointer-events':'none'});
        } else if (type === 'semi_sextile') {
            const t = mk('text', {x:mx, y:(my+s*0.4).toFixed(1), 'text-anchor':'middle', 'dominant-baseline':'middle',
                fill:color, 'font-size':'7', 'pointer-events':'none'});
            t.textContent = '⚺';
        }
    }

    // ── Planet–planet aspects (solid colored lines + glyph at midpoint) ───────
    for (const asp of AS) {
        const lonA = lonMap[asp.a], lonB = lonMap[asp.b];
        if (lonA === undefined || lonB === undefined) continue;
        if (asp.type === 'mutual_reception') continue;
        const cfg = ASP_C[asp.type] || { color:'#505060', w:'0.5', op:'0.30' };
        const [x1,y1] = pol(l2a(lonA), RC);
        const [x2,y2] = pol(l2a(lonB), RC);
        mk('line', {x1,y1,x2,y2, stroke:cfg.color, 'stroke-width':cfg.w, opacity:cfg.op});
        aspGlyph(x1, y1, x2, y2, asp.type, cfg.color);
    }

    // ── Planet–angle aspects (dashed lines, ASC + MC) ────────────────────────
    const ANG_ORBS = [
        { type:'conjunction', angle:  0, orb:8 },
        { type:'opposition',  angle:180, orb:8 },
        { type:'trine',       angle:120, orb:6 },
        { type:'square',      angle: 90, orb:6 },
        { type:'sextile',     angle: 60, orb:4 },
    ];
    function angAsp(lonA, lonB) {
        let d = Math.abs(lonA - lonB); if (d > 180) d = 360 - d;
        let best = null, bo = Infinity;
        for (const def of ANG_ORBS) {
            const dev = Math.abs(d - def.angle);
            if (dev <= def.orb && dev < bo) { bo = dev; best = def.type; }
        }
        return best;
    }
    function drawAngLine(lonP, lonAng) {
        const type = angAsp(lonP, lonAng);
        if (!type) return;
        const cfg = ASP_C[type];
        const [x1,y1] = pol(l2a(lonP),   RC);
        const [x2,y2] = pol(l2a(lonAng), RC);
        mk('line', {x1,y1,x2,y2, stroke:cfg.color,
            'stroke-width': String((parseFloat(cfg.w) * 0.85).toFixed(2)),
            'opacity':       String((parseFloat(cfg.op) * 0.85).toFixed(2)),
            'stroke-dasharray':'2.5,2'});
    }
    if (ascLon !== null) {
        for (const p of PL) drawAngLine(p.lon, ascLon);
        if (HS.length === 12) { for (const p of PL) drawAngLine(p.lon, HS[9]); }
    }

    // Center ring stroke — on top of aspect web
    mk('circle', {cx:CX, cy:CY, r:RC, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

    // ── Planets ───────────────────────────────────────────────────────────────
    const BODY_G = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'].map(g => g + '\uFE0E');
    const BODY_C = [
        '#c49a18','#5588aa','#3a7a68','#a84e80',
        '#c03828','#9a6218','#4a6898','#2888a8',
        '#3858a8','#6838a0','#508080','#4068a0','#885060',
    ];

    const pts = PL.map(p => ({ ...p, origA: l2a(p.lon), a: l2a(p.lon), r: RPG }));
    pts.sort((a, b) => a.a - b.a);

    // Helper: clamp a planet's display angle to its zodiac sign sector
    function clampToSign(p) {
        const signIdx = Math.floor(((p.lon % 360) + 360) % 360 / 30);
        const aMid = l2a(signIdx * 30 + 15);
        let d = ((p.a - aMid + 360) % 360);
        if (d > 180) d -= 360;
        if (d >  14) p.a = (aMid + 14 + 360) % 360;
        if (d < -14) p.a = (aMid - 14 + 360) % 360;
    }

    // Angular spread — keep planets within their sign sector, spread to avoid overlap
    const MIN_ANG = 9;
    for (let iter = 0; iter < 60; iter++) {
        let moved = false;
        for (let i = 0; i < pts.length; i++) {
            const j = (i + 1) % pts.length;
            const diff = ((pts[j].a - pts[i].a) + 360) % 360;
            if (diff > 0 && diff < MIN_ANG) {
                const push = (MIN_ANG - diff) / 2;
                pts[i].a = (pts[i].a - push + 360) % 360;
                pts[j].a = (pts[j].a + push)       % 360;
                moved = true;
            }
        }
        // Enforce sign boundaries after every iteration
        for (const p of pts) clampToSign(p);
        pts.sort((a, b) => a.a - b.a);
        if (!moved) break;
    }

    for (const p of pts) {
        const [px,py] = pol(p.a, p.r);
        // Guide tick: true zodiac position → spread glyph position
        const [lx,ly] = pol(p.origA, RZ);
        mk('line', {x1:lx,y1:ly,x2:px,y2:py,
            stroke:C.border, 'stroke-width':'0.5', 'stroke-dasharray':'2,3'});
        // Planet glyph
        tx(px, py, BODY_G[p.body] ?? '★', {
            'text-anchor':'middle', 'dominant-baseline':'central',
            'font-size':'13', 'fill': BODY_C[p.body] ?? ACCENT,
            'font-family':'serif', 'pointer-events':'none',
        });
        // Degree-in-sign label (e.g. "17°18'")
        const degInSign = p.lon % 30;
        const dg = Math.floor(degInSign);
        const mn = Math.floor((degInSign - dg) * 60);
        const [dlx,dly] = pol(p.a, p.r - 12);
        tx(dlx, dly, `${dg}°${String(mn).padStart(2,'0')}'`, {
            'text-anchor':'middle', 'dominant-baseline':'central',
            'font-size':'5.5', 'fill':C.muted,
            'font-family':'sans-serif', 'pointer-events':'none',
        });
        // Rx label
        if (p.rx) {
            tx(px + 8, py - 6, 'r', {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'7', 'fill':C.muted,
                'font-family':'serif', 'font-style':'italic', 'pointer-events':'none',
            });
        }
    }

    // ── Combustion indicator (Zet convention) ────────────────────────────────
    const sun = PL.find(p => p.body === 0);
    if (sun) {
        for (const p of PL) {
            if (p.body === 0) continue;
            let diff = Math.abs(p.lon - sun.lon);
            if (diff > 180) diff = 360 - diff;
            if (diff > 8) continue;
            const cazimi = diff < 0.283; // 0°17'
            const aS = l2a(sun.lon), aP = l2a(p.lon);
            const span  = ((aP - aS) + 360) % 360;
            const sweep = span <= 180 ? 1 : 0;
            const [sx,sy] = pol(aS, RC);
            const [ex,ey] = pol(aP, RC);
            mk('path', {
                d: `M${sx} ${sy} A${RC} ${RC} 0 0 ${sweep} ${ex} ${ey}`,
                fill:'none',
                stroke: cazimi ? '#2277dd' : '#4499cc',
                'stroke-width': cazimi ? '3' : '2.5',
                'stroke-linecap':'round',
                opacity: cazimi ? '1' : '0.9',
            });
        }
    }

    // ── ASC–DSC / MC–IC axes with arrowheads (drawn last, on top) ────────────
    if (ascLon !== null) {
        // Arrowhead triangle at tip (tx,ty) pointing outward in direction ang
        function arrowHead(tx, ty, ang, color, sz) {
            const a1 = ang + Math.PI * 5 / 6;
            const a2 = ang - Math.PI * 5 / 6;
            mk('polygon', {
                points: `${(+tx).toFixed(1)},${(+ty).toFixed(1)} ` +
                        `${(tx + sz * Math.cos(a1)).toFixed(1)},${(ty + sz * Math.sin(a1)).toFixed(1)} ` +
                        `${(tx + sz * Math.cos(a2)).toFixed(1)},${(ty + sz * Math.sin(a2)).toFixed(1)}`,
                fill: color, stroke: 'none', 'pointer-events': 'none',
            });
        }
        // Each axis = two half-arrows from inner circle outward; arrow only on primary side
        function axisLine(lon1, lon2) {
            const a1 = l2a(lon1), a2 = l2a(lon2);
            const [ox1,oy1] = pol(a1, R4IN);
            const [ox2,oy2] = pol(a2, R4IN);
            const [ix1,iy1] = pol(a1, R3IN);
            const [ix2,iy2] = pol(a2, R3IN);
            // Primary half (lon1): arrowhead at outer tip
            mk('line', {x1:ix1,y1:iy1,x2:ox1,y2:oy1,
                stroke:C.muted, 'stroke-width':'1.2', opacity:'0.9'});
            arrowHead(ox1, oy1, Math.atan2(oy1 - CY, ox1 - CX), C.muted, 5);
            // Opposite half (lon2): no arrowhead
            mk('line', {x1:ix2,y1:iy2,x2:ox2,y2:oy2,
                stroke:C.muted, 'stroke-width':'1.2', opacity:'0.9'});
        }
        axisLine(ascLon, ascLon + 180);
        if (HS.length === 12) axisLine(HS[9], HS[3]);

        // AC / DC / MC / IC degree labels
        const degStr = (lon) => {
            const w = ((lon % 30) + 30) % 30;
            const d = Math.floor(w);
            const m = Math.round((w - d) * 60);
            return `${d}°${String(m >= 60 ? 59 : m).padStart(2,'0')}'`;
        };
        const lbl = (x, y, name, deg, fill) => {
            tx(x, y-4, name, {'text-anchor':'middle','dominant-baseline':'central','font-size':'7','fill':fill,'font-family':'sans-serif','font-weight':'bold','pointer-events':'none'});
            tx(x, y+5, deg,  {'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':fill,'font-family':'sans-serif','pointer-events':'none'});
        };
        lbl(CX - RC + 9, CY, 'AC', degStr(ascLon), ACCENT);
        lbl(CX + RC - 9, CY, 'DC', degStr((ascLon + 180) % 360), C.muted);
        if (HS.length === 12) {
            const [mx,my] = pol(l2a(HS[9]), RC - 14);
            lbl(mx, my, 'MC', degStr(HS[9]), ACCENT);
            const [ix,iy] = pol(l2a(HS[3]), RC - 14);
            lbl(ix, iy, 'IC', degStr(HS[3]), C.muted);
        }
    } // end if (ascLon !== null)
    } // end draw()

    // Alpine.js sets data-theme AFTER our script runs (module scripts are deferred).
    // Wait for alpine:initialized so getComputedStyle reads the correct theme vars.
    document.addEventListener('alpine:initialized', draw, { once: true });
    setTimeout(draw, 200); // fallback if Alpine event doesn't fire

    // Redraw when theme changes (data-theme attribute on <html> toggled by Alpine)
    new MutationObserver(() => draw()).observe(document.documentElement, {
        attributes: true, attributeFilter: ['data-theme'],
    });
})();
</script>
<script>
// ── Short Version toggle ─────────────────────────────────────────────
(function () {
    var LS_KEY   = 'stellar_short_ver';
    var pdfBase  = '{{ route('natal.pdf', $profile) }}';
    var chk      = document.getElementById('short-ver-chk');
    var track    = document.getElementById('short-ver-track');
    var thumb    = document.getElementById('short-ver-thumb');
    var topPdf    = document.getElementById('top-pdf-btn');
    var shortPdf  = document.getElementById('short-pdf-btn');
    var bottomPdf = document.getElementById('bottom-pdf-btn');

    function applyState(short) {
        document.querySelectorAll('[data-ver="full"]').forEach(function(el) {
            el.style.display = short ? 'none' : '';
        });
        document.querySelectorAll('[data-ver="short"]').forEach(function(el) {
            el.style.display = short ? '' : 'none';
        });
        if (chk)   chk.checked = short;
        if (track) track.style.background = short ? '#6a329f' : '#a09ab8';
        if (thumb) thumb.style.transform  = short ? 'translateX(16px)' : '';
        if (topPdf)    topPdf.href    = short ? pdfBase + '?short=1' : pdfBase;
        if (shortPdf)  shortPdf.href  = short ? pdfBase + '?short=1' : pdfBase;
        if (bottomPdf) bottomPdf.href = short ? pdfBase + '?short=1' : pdfBase;
        var pFull  = document.getElementById('portrait-full');
        var pShort = document.getElementById('portrait-short');
        var pCard  = document.getElementById('portrait-card');
        var hasFull  = pFull  && pFull.innerHTML.trim();
        var hasShort = pShort && pShort.innerHTML.trim();
        if (pFull)  pFull.style.display  = (!short && hasFull) ? '' : 'none';
        if (pShort) pShort.style.display = (short && hasShort) ? '' : 'none';
        if (pCard)  pCard.style.display  = ((!short && hasFull) || (short && hasShort)) ? '' : 'none';
    }

    applyState(localStorage.getItem(LS_KEY) === '1');

    window.stelToggleAi = function (val) {
        var next = (val !== undefined) ? val : (localStorage.getItem(LS_KEY) !== '1');
        localStorage.setItem(LS_KEY, next ? '1' : '0');
        applyState(next);
    };
})();
</script>
<script>
function startPortraitPolling() {
    var overlay = document.getElementById('portrait-loading-overlay');
    if (overlay) overlay.style.display = 'flex';

    var statusUrl = '{{ route('natal.portrait.status', $profile) }}';
    var timer = setInterval(function() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function(data) {
                if (!data.generating && (data.portrait_full || data.portrait_short)) {
                    clearInterval(timer);
                    if (overlay) overlay.style.display = 'none';

                    var card = document.getElementById('portrait-card');
                    var pFull  = document.getElementById('portrait-full');
                    var pShort = document.getElementById('portrait-short');

                    if (data.portrait_full && pFull) {
                        pFull.innerHTML = data.portrait_full;
                        pFull.style.display = '';
                    }
                    if (data.portrait_short && pShort) {
                        pShort.innerHTML = data.portrait_short;
                    }
                    if (card) card.style.display = '';

                    // Disable premium button
                    var btn = document.querySelector('[data-premium-btn]');
                    if (btn) { btn.disabled = true; btn.style.opacity = '0.45'; btn.style.cursor = 'not-allowed'; }
                }
            })
            .catch(function() { clearInterval(timer); if (overlay) overlay.style.display = 'none'; });
    }, 5000);
}

window.addEventListener('premium-confirmed', function(e) {
    if (!e.detail || e.detail.context !== 'natal') return;

    var csrf = document.querySelector('meta[name=csrf-token]');
    fetch('{{ route('natal.portrait', $profile) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf ? csrf.content : '', 'Accept': 'application/json' },
    })
    .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
    .then(function(data) { if (data.queued) startPortraitPolling(); })
    .catch(function(err) { console.error('portrait error', err); });
});
</script>
@endpush

@if($generating ?? false)
<script>
document.addEventListener('DOMContentLoaded', function() { startPortraitPolling(); });
</script>
@endif

{{-- PDF loading overlay --}}
<div id="pdf-loading-overlay" class="loading-overlay overlay-pdf">
    <div class="spinner spinner-sm"></div>
    <p class="overlay-msg-pdf">{{ __('ui.natal.preparing_pdf') }}</p>
</div>
<script>
function showPdfLoading() {
    var overlay = document.getElementById('pdf-loading-overlay');
    if (!overlay) return;
    overlay.style.display = 'flex';
    var timer = setTimeout(function() { overlay.style.display = 'none'; }, 30000);
    window.addEventListener('focus', function hidePdf() {
        clearTimeout(timer);
        overlay.style.display = 'none';
        window.removeEventListener('focus', hidePdf);
    });
}
</script>

{{-- Portrait generation loading overlay --}}
<div id="portrait-loading-overlay" class="loading-overlay overlay-portrait">
    <div class="spinner spinner-lg"></div>
    <p class="overlay-msg-portrait">{{ __('ui.natal.generating_portrait') }}</p>
</div>
<style>
@keyframes spin{to{transform:rotate(360deg)}}
#portrait-full p, #portrait-short p { margin:0 0 1.1em; }
#portrait-full p:last-child, #portrait-short p:last-child { margin-bottom:0; }
/* Lead paragraph */
#portrait-full p:first-child, #portrait-short p:first-child {
    font-size:0.95rem;line-height:1.7;
}
/* Ensure bold/italic render inside portrait */
#portrait-full strong, #portrait-short strong,
#portrait-full b, #portrait-short b { font-weight:700; }
#portrait-full em, #portrait-short em,
#portrait-full i, #portrait-short i { font-style:italic; }
/* Mid-section divider after 5th paragraph (full version only) */
#portrait-full p:nth-child(5) {
    padding-bottom:1.1em;
    border-bottom:1px solid rgba(212,175,55,0.35);
    margin-bottom:1.1em;
}
</style>

