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
                {{ __('ui.natal.back_to_edit') }}
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
        <div class="section-label wheel-label">{{ __('ui.natal.wheel_label') }}</div>
        @include('partials.wheel-natal', ['svgId' => 'natal-wheel', 'svgClass' => 'wheel-svg'])
        @if(!$wheelAsc)
            <div class="chart-unlock-hint">
                <a href="{{ route('stellar-profiles.index', ['edit' => $profile->uuid]) }}" class="chart-unlock-link">
                    {{ __('ui.natal.unlock_houses') }}
                </a>
            </div>
        @endif
        <div style="text-align:center;margin-top:0.5rem;display:flex;justify-content:center;gap:1.2rem">
            <a href="{{ route('daily.show', $profile) }}" style="font-size:0.78rem;color:var(--theme-muted);text-decoration:none;opacity:0.7" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">{{ __('ui.natal.forecast_links.daily') }} →</a>
        </div>
    </div>

    {{-- Planets --}}
    <div class="card card-flush">
        <div class="card-header">
            <div class="section-label">{{ __('ui.natal.section_planets') }}</div>
        </div>
        <div class="card-scroll">
            <table class="ct">
                <thead>
                    <tr>
                        <th class="ct-th-l">{{ __('ui.natal.col_planet') }}</th>
                        <th class="ct-th-l">{{ __('ui.natal.col_sign') }}</th>
                        <th class="ct-th-r">{{ __('ui.natal.col_position') }}</th>
                        @if(count($houses))<th>{{ __('ui.natal.col_house') }}</th>@endif
                        <th>{{ __('ui.natal.col_rx') }}</th>
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
        <div class="section-label">{{ __('ui.natal.section_houses') }}</div>
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
            {{ __('ui.natal.short_version') }}
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
        <div class="section-label">{{ __('ui.natal.section_element_pattern') }}</div>
        <div class="stack">
        @foreach($items as $s)
        <div>
            <div class="ep-header">
                <span>{{ $elementEmoji[$s['element']] ?? '' }}</span>
                @if($s['type'] === 'singleton')
                    <span class="ep-label">{{ __('ui.natal.singleton_label') }}: {{ $bodyGlyphs[$s['planet']['body']] ?? '' }} {{ $bodyNames[$s['planet']['body']] ?? '' }} ({{ $s['element'] }})</span>
                @else
                    <span class="ep-label ep-label-muted">{{ __('ui.natal.missing_element_label') }}: {{ $s['element'] }}</span>
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

