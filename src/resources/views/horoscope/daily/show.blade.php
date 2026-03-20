@extends('layouts.app')

@section('title', $profile->name . ' — ' . __('ui.daily.page_title'))
@section('description', __('ui.daily.page_title') . ' for ' . $profile->name)
@section('main_class', 'page-wrap-narrow')

@php
    use Carbon\Carbon;
    $bodyGlyphs   = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'];
    $bodyNames    = ['Sun','Moon','Mercury','Venus','Mars','Jupiter','Saturn','Uranus','Neptune','Pluto','Chiron','North Node','Lilith'];
    $signGlyphs   = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames    = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $houseNames   = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $aspectGlyphs = [
        'conjunction' => '☌', 'opposition' => '☍', 'trine'    => '△',
        'square'      => '□', 'sextile'    => '⚹', 'quincunx' => '⚻',
        'semi_sextile' => '∠', 'mutual_reception' => '⇌',
    ];
    $moonPhaseEmojis = [
        'new_moon' => '🌑', 'waxing_crescent' => '🌒', 'first_quarter'  => '🌓',
        'waxing_gibbous' => '🌔', 'full_moon' => '🌕', 'waning_gibbous' => '🌖',
        'last_quarter' => '🌗', 'waning_crescent' => '🌘',
    ];
    $areaEmojis = [
        'love' => '❤️', 'home' => '🏠', 'creativity' => '🎨',
        'spirituality' => '🔮', 'health' => '💚', 'finance' => '💰',
        'travel' => '✈️', 'career' => '💼', 'personal_growth' => '🌱',
        'communication' => '💬', 'contracts' => '📝',
    ];
    $carbon        = Carbon::parse($date);
    $prevDate      = $carbon->copy()->subDay()->toDateString();
    $nextDate      = $carbon->copy()->addDay()->toDateString();
    $isToday       = $date === now()->toDateString();
    $phaseEmoji    = $moonPhaseEmojis[$dto->moon->phaseSlug] ?? '🌑';
    $moonSignGlyph = $signGlyphs[$dto->moon->signIndex] ?? '';
    $rulerGlyph    = $bodyGlyphs[$dto->dayRuler->body] ?? '';
    // Transit subtitle: Sun · Moon · any Rx
    $subtitleParts = [];
    foreach ($dto->positions as $pos) {
        if ($pos->body === 0) {
            $subtitleParts[] = '☉ Sun in ' . ($signGlyphs[$pos->signIndex] ?? '') . ' ' . $pos->signName;
        } elseif ($pos->body === 1) {
            $subtitleParts[] = '☽ Moon in ' . ($signGlyphs[$pos->signIndex] ?? '') . ' ' . $pos->signName . ($pos->isRetrograde ? ' Rx' : '');
        } elseif ($pos->isRetrograde && $pos->body >= 2 && $pos->body <= 6) {
            $subtitleParts[] = ($bodyGlyphs[$pos->body] ?? '') . ' ' . $pos->name . ' Rx';
        }
    }
    $transitSubtitle = implode('  ·  ', $subtitleParts);
@endphp

@section('content')

    {{-- Profile switcher --}}
    @if($profiles->count() > 1)
    @php
        $profileList = $profiles->map(fn($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'sub'   => $p->birth_date?->format('M j, Y') ?? '',
            'url'   => route('daily.show', $p),
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
        <button @click="open = !open" class="switcher-btn">
            <span>
                <span x-text="current.name" class="switcher-btn-name"></span>
                <span x-text="current.sub ? ' · ' + current.sub : ''" class="switcher-btn-sub"></span>
            </span>
            <span class="switcher-arrow" x-text="open ? '▲' : '▼'"></span>
        </button>
        <div x-show="open" x-cloak class="switcher-dropdown">
            <div class="switcher-search-wrap">
                <input x-ref="search" x-model="search" @keydown.escape="open = false"
                       placeholder="Search profiles…"
                       class="switcher-input"
                       x-init="$watch('open', v => v && $nextTick(() => $refs.search.focus()))">
            </div>
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
                <div x-show="filtered.length === 0" class="switcher-empty">
                    No profiles found
                </div>
            </div>
        </div>
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div class="page-header">
        <div class="page-header-inner">
            <div class="page-type-label">{{ __('ui.daily.page_title') }}</div>
            <h1 class="font-display profile-name">
                {{ $profile->name }}
            </h1>
            <div class="pdf-row">
                <a href="{{ route('daily.pdf', [$profile, $date]) }}"
                   onclick="showDailyPdfLoading()"
                   class="btn-pdf"
                   title="{{ __('ui.daily.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
            </div>
            <div class="header-date">
                {{ $carbon->format('l, j F Y') }}
            </div>

            {{-- Date navigation --}}
            <div class="date-nav">
                <a href="{{ route('daily.show', [$profile, $prevDate]) }}" class="date-nav-link">
                    ← {{ $carbon->copy()->subDay()->format('j M') }}
                </a>
                <span class="date-nav-current">
                    {{ $isToday ? __('ui.daily.today') : $carbon->format('j M') }}
                    @if(!$isToday)
                        <a href="{{ route('daily.show', [$profile, now()->toDateString()]) }}" class="date-nav-today">Today</a>
                    @endif
                </span>
                <a href="{{ route('daily.show', [$profile, $nextDate]) }}" class="date-nav-link">
                    {{ $carbon->copy()->addDay()->format('j M') }} →
                </a>
            </div>

            {{-- Period tabs --}}
            <div class="period-tabs">
                <span class="period-tab-active">Day</span>
                <a href="#" class="period-tab">Week</a>
                <a href="#" class="period-tab">Month</a>
                <a href="#" class="period-tab">Year</a>
            </div>
        </div>
    </div>

    {{-- Bi-wheel --}}
    @php
        $bwAsc      = $wheelAsc !== null ? (float) $wheelAsc : 'null';
        $bwAscEff   = $wheelAsc !== null ? (float) $wheelAsc : 0.0;
    @endphp
    <div class="card card-wheel card-center">
        <div class="section-label section-label-sm wheel-label">
            {{ $isToday ? "Today's Transits" : 'Transits · ' . $carbon->format('j M Y') }}
        </div>
        <svg id="biwheel" viewBox="-28 -28 376 376" width="100%"
             class="wheel-svg" aria-label="Natal chart with today's transits"></svg>
        @if($transitSubtitle)
        <div class="transit-subtitle">{{ $transitSubtitle }}</div>
        @endif
        <div style="text-align:center;margin-top:0.4rem">
            <a href="{{ route('natal.show', $profile) }}" style="font-size:0.78rem;color:var(--theme-muted);text-decoration:none;opacity:0.7" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">View Natal Chart →</a>
        </div>
    </div>
    <script>
    (function() {
        'use strict';
        const ascLon = {{ $bwAsc }};
        const PL     = @json($wheelNatalPlanets);
        const HS     = @json($wheelHouses);
        const AS     = @json($wheelAspects);
        const TR     = @json($wheelTransits);

        function draw() {
            const NS   = 'http://www.w3.org/2000/svg';
            const CX   = 160, CY = 160;
            // ── Natal ring constants (identical to natal wheel) ──────────────────
            const RO   = 154;  // zodiac outer / transit inner boundary
            const RZ   = 136;  // zodiac inner / ring 4 outer
            const R4IN = 120;  // ring 4 inner / ring 3 outer
            const R3IN = 72;   // ring 3 inner / ring 2 outer
            const RPG  = 112;  // natal planet glyph center
            const RH   = 67;   // house number center
            const RC   = 62;   // center circle / aspect web
            const RTG  = 168;  // transit planet glyph center (floats outside zodiac ring)

            const ascEff = (ascLon !== null) ? ascLon : 0;

            const svg = document.getElementById('biwheel');
            if (!svg) return;

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

            // Read CSS theme vars
            function cv(n) { return getComputedStyle(svg).getPropertyValue(n).trim() || null; }
            const ACCENT = '#6a329f';
            const C = {
                bg:     cv('--theme-bg')     || '#0e0e18',
                card:   cv('--theme-card')   || '#17172a',
                raised: cv('--theme-raised') || '#1e1e32',
                border: cv('--theme-border') || '#2e2e4a',
                text:   cv('--theme-text')   || '#e8e8f0',
                muted:  cv('--theme-muted')  || '#7070a0',
            };

            const SIGN_ELEM = [0,1,2,3, 0,1,2,3, 0,1,2,3];
            const ELEM_C    = ['#c43030','#287838','#b08010','#1d5fa8'];
            const SIGN_G = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'].map(g => g + '\uFE0E');


            // ── Zodiac ring ────────────────────────────────────────────────────────
            for (let s = 0; s < 12; s++) {
                sector(RZ, RO, l2a(s * 30), l2a((s + 1) * 30),
                       s % 2 === 0 ? C.raised : C.card, C.border);
            }
            // Sign glyphs in zodiac ring
            const RGLYPH = RZ + 7;
            for (let s = 0; s < 12; s++) {
                const [gx, gy] = pol(l2a(s * 30 + 15), RGLYPH);
                tx(gx, gy, SIGN_G[s], {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'10', 'fill': ELEM_C[SIGN_ELEM[s]],
                    'font-family':'serif', 'pointer-events':'none',
                });
            }
            mk('circle', {cx:CX, cy:CY, r:RZ, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

            // ── Inner rings background ─────────────────────────────────────────────
            mk('circle', {cx:CX, cy:CY, r:RZ, fill:C.card, stroke:'none'});

            const HOUSE_N = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            const ANGULAR = new Set([0,3,6,9]);

            if (HS.length === 12) {
                mk('circle', {cx:CX, cy:CY, r:R4IN, fill:'none', stroke:C.border, 'stroke-width':'0.4'});
                mk('circle', {cx:CX, cy:CY, r:R3IN, fill:'none', stroke:C.border, 'stroke-width':'0.4'});
            }

            // House cusps + labels
            if (HS.length === 12) {
                const R4MID = (RZ + R4IN) / 2;
                for (let h = 0; h < 12; h++) {
                    const a   = l2a(HS[h]);
                    const isA = ANGULAR.has(h);
                    const [x1,y1] = pol(a, R4IN);
                    const [x2,y2] = pol(a, RC);
                    mk('line', {x1,y1,x2,y2,
                        stroke: isA ? ACCENT : C.border,
                        'stroke-width': isA ? '1.2' : '0.6',
                    });
                    const cDeg  = Math.floor(HS[h] % 30);
                    const cMin  = Math.round(((HS[h] % 30) - cDeg) * 60);
                    const cSign = Math.floor(HS[h] / 30) % 12;
                    const [dlx, dly] = pol(a, R4MID + 2);
                    tx(dlx, dly, `${cDeg}°${String(cMin).padStart(2,'0')}'`, {
                        'text-anchor':'middle', 'dominant-baseline':'central',
                        'font-size':'4.5', 'fill': isA ? ACCENT : C.muted,
                        'font-family':'sans-serif', 'pointer-events':'none',
                    });
                    const [sgx, sgy] = pol(a, R4MID - 4);
                    tx(sgx, sgy, SIGN_G[cSign], {
                        'text-anchor':'middle', 'dominant-baseline':'central',
                        'font-size':'5.5', 'fill': isA ? ACCENT : ELEM_C[SIGN_ELEM[cSign]],
                        'font-family':'serif', 'pointer-events':'none',
                    });
                }
                // House numbers
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

            // ── Aspect colors ──────────────────────────────────────────────────────
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

            // Natal aspect lines
            const lonMap = {};
            for (const p of PL) lonMap[p.body] = p.lon;
            for (const asp of AS) {
                const lonA = lonMap[asp.a], lonB = lonMap[asp.b];
                if (lonA === undefined || lonB === undefined) continue;
                if (asp.type === 'mutual_reception') continue;
                const cfg = ASP_C[asp.type] || { color:'#505060', w:'0.5', op:'0.30' };
                const [x1,y1] = pol(l2a(lonA), RC);
                const [x2,y2] = pol(l2a(lonB), RC);
                mk('line', {x1,y1,x2,y2, stroke:cfg.color, 'stroke-width':cfg.w, opacity:cfg.op});
            }

            // Center ring stroke
            mk('circle', {cx:CX, cy:CY, r:RC, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

            // ── ASC/DSC/MC/IC axis lines (before planets) ─────────────────────────
            if (ascLon !== null) {
                function arrowHead(tx2, ty2, ang, color, sz) {
                    const a1 = ang + Math.PI * 5 / 6;
                    const a2 = ang - Math.PI * 5 / 6;
                    mk('polygon', {
                        points: `${(+tx2).toFixed(1)},${(+ty2).toFixed(1)} ` +
                                `${(tx2 + sz * Math.cos(a1)).toFixed(1)},${(ty2 + sz * Math.sin(a1)).toFixed(1)} ` +
                                `${(tx2 + sz * Math.cos(a2)).toFixed(1)},${(ty2 + sz * Math.sin(a2)).toFixed(1)}`,
                        fill: color, stroke: 'none', 'pointer-events': 'none',
                    });
                }
                function axisLine(lon1, lon2) {
                    const a1 = l2a(lon1), a2 = l2a(lon2);
                    const [ox1,oy1] = pol(a1, R4IN);
                    const [ox2,oy2] = pol(a2, R4IN);
                    const [ix1,iy1] = pol(a1, R3IN);
                    const [ix2,iy2] = pol(a2, R3IN);
                    mk('line', {x1:ix1,y1:iy1,x2:ox1,y2:oy1,
                        stroke:C.muted, 'stroke-width':'1.2', opacity:'0.9'});
                    arrowHead(ox1, oy1, Math.atan2(oy1 - CY, ox1 - CX), C.muted, 5);
                    mk('line', {x1:ix2,y1:iy2,x2:ox2,y2:oy2,
                        stroke:C.muted, 'stroke-width':'1.2', opacity:'0.9'});
                }
                axisLine(ascLon, ascLon + 180);
                if (HS.length === 12) axisLine(HS[9], HS[3]);
            }

            // ── Natal planets ──────────────────────────────────────────────────────
            const BODY_G = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'].map(g => g + '\uFE0E');
            const BODY_C = [
                '#c49a18','#5588aa','#3a7a68','#a84e80',
                '#c03828','#9a6218','#4a6898','#2888a8',
                '#3858a8','#6838a0','#508080','#4068a0','#885060',
            ];

            function spreadPlanets(planets, rGlyph) {
                const pts = planets.map(p => ({ ...p, origA: l2a(p.lon), a: l2a(p.lon), r: rGlyph }));
                pts.sort((a, b) => a.a - b.a);
                function clampToSign(p) {
                    const signIdx = Math.floor(((p.lon % 360) + 360) % 360 / 30);
                    const aMid = l2a(signIdx * 30 + 15);
                    let d = ((p.a - aMid + 360) % 360);
                    if (d > 180) d -= 360;
                    if (d >  14) p.a = (aMid + 14 + 360) % 360;
                    if (d < -14) p.a = (aMid - 14 + 360) % 360;
                }
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
                    for (const p of pts) clampToSign(p);
                    pts.sort((a, b) => a.a - b.a);
                    if (!moved) break;
                }
                return pts;
            }

            // Natal planet spread (inner ring, glyph at RPG=112)
            const natalPts = spreadPlanets(PL, RPG);
            for (const p of natalPts) {
                const [px,py] = pol(p.a, p.r);
                const [lx,ly] = pol(p.origA, RZ);
                mk('line', {x1:lx,y1:ly,x2:px,y2:py,
                    stroke:C.border, 'stroke-width':'0.5', 'stroke-dasharray':'2,3'});
                tx(px, py, BODY_G[p.body] ?? '★', {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'13', 'fill': BODY_C[p.body] ?? ACCENT,
                    'font-family':'serif', 'pointer-events':'none',
                });
                const degInSign = p.lon % 30;
                const dg = Math.floor(degInSign);
                const mn = Math.floor((degInSign - dg) * 60);
                const [dlx,dly] = pol(p.a, p.r - 12);
                tx(dlx, dly, `${dg}°${String(mn).padStart(2,'0')}'`, {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'5.5', 'fill':C.muted,
                    'font-family':'sans-serif', 'pointer-events':'none',
                });
                if (p.rx) {
                    tx(px + 8, py - 6, 'r', {
                        'text-anchor':'middle', 'dominant-baseline':'central',
                        'font-size':'7', 'fill':C.muted,
                        'font-family':'serif', 'font-style':'italic', 'pointer-events':'none',
                    });
                }
            }

            // ── Transit planets — float freely outside zodiac ring ────────────────
            // Sort ONCE by origA — never re-sort — guarantees lines never cross
            function spreadFree(planets, rGlyph) {
                if (planets.length === 0) return [];
                const pts = planets.map(p => ({ ...p, origA: l2a(p.lon), a: l2a(p.lon), r: rGlyph }));
                pts.sort((a, b) => a.origA - b.origA);
                const N = pts.length, MIN_ANG = 7;
                for (let iter = 0; iter < 80; iter++) {
                    let moved = false;
                    for (let i = 0; i < N; i++) {
                        const j = (i + 1) % N;
                        let diff = ((pts[j].a - pts[i].a) + 360) % 360;
                        if (diff > 180) diff -= 360; // signed: + means j is ahead
                        if (diff < MIN_ANG) {
                            const push = (MIN_ANG - diff) / 2;
                            pts[i].a = (pts[i].a - push + 360) % 360;
                            pts[j].a = (pts[j].a + push + 360) % 360;
                            moved = true;
                        }
                    }
                    if (!moved) break;
                }
                return pts;
            }
            const transitPts = spreadFree(TR, RTG);
            for (const p of transitPts) {
                const [px,py] = pol(p.a, p.r);
                // Guide tick: true zodiac position on RO → spread glyph
                const [lx,ly] = pol(p.origA, RO);
                mk('line', {x1:lx,y1:ly,x2:px,y2:py,
                    stroke:C.border, 'stroke-width':'0.5', 'stroke-dasharray':'2,3'});
                // Transit glyph (same colors, slightly smaller than natal)
                tx(px, py, BODY_G[p.body] ?? '★', {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'12', 'fill': BODY_C[p.body] ?? ACCENT,
                    'font-family':'serif', 'pointer-events':'none',
                });
                // Degree label
                const degInSign = p.lon % 30;
                const dg = Math.floor(degInSign);
                const mn = Math.floor((degInSign - dg) * 60);
                const [dlx,dly] = pol(p.a, p.r + 14);
                tx(dlx, dly, `${dg}°${String(mn).padStart(2,'0')}'`, {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'5.5', 'fill':C.muted,
                    'font-family':'sans-serif', 'pointer-events':'none',
                });
                if (p.rx) {
                    tx(px + 8, py - 6, 'r', {
                        'text-anchor':'middle', 'dominant-baseline':'central',
                        'font-size':'7', 'fill':C.muted,
                        'font-family':'serif', 'font-style':'italic', 'pointer-events':'none',
                    });
                }
            }

            // ── ASC/DSC/MC/IC labels (after planets, on top) ───────────────────────
            if (ascLon !== null) {
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
            }
        } // end draw()

        // Wait for Alpine/theme to initialize before drawing
        window.addEventListener('alpine:initialized', () => draw());
        if (document.readyState === 'complete') {
            requestAnimationFrame(() => draw());
        }
        document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(() => draw()));

        // Redraw when theme changes (data-theme attribute on <html> toggled by Alpine)
        new MutationObserver(() => draw()).observe(document.documentElement, {
            attributes: true, attributeFilter: ['data-theme'],
        });
    })();
    </script>

    {{-- Transit planet list --}}
    <div class="card card-mt">
        <div class="section-label">{{ __('ui.daily.transits_today') }}</div>
        <div class="planet-grid">
            @foreach($dto->positions as $pos)
            @php
                $pd = (int) $pos->degreeInSign;
                $pmf = ($pos->degreeInSign - $pd) * 60;
                $pm = (int) $pmf;
                $ps = (int) round(($pmf - $pm) * 60);
                if ($ps === 60) { $ps = 0; $pm++; }
                if ($pm === 60) { $pm = 0; $pd++; }
            @endphp
            <div class="planet-row">
                <span class="planet-glyph">{{ $bodyGlyphs[$pos->body] ?? '' }}</span>
                <span>{{ $pos->name }}</span>
                <span class="planet-in">in</span>
                <span>{{ $signGlyphs[$pos->signIndex] ?? '' }} {{ $pos->signName }}@if($pos->isRetrograde) <span class="rx-badge">Rx</span>@endif</span>
                <span style="color:var(--theme-muted);font-size:0.78rem">{{ $pd }}°{{ str_pad($pm,2,'0',STR_PAD_LEFT) }}'{{ str_pad($ps,2,'0',STR_PAD_LEFT) }}"</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Natal planet list --}}
    @php $natalPlanets = $profile->natalChart?->planets ?? []; @endphp
    @if(count($natalPlanets))
    <div class="card card-mt">
        <div class="section-label">{{ __('ui.daily.natal_planets') }}</div>
        <div class="planet-grid">
            @foreach($natalPlanets as $planet)
            @php
                $nd = floor($planet['degree']);
                $nmf = ($planet['degree'] - $nd) * 60;
                $nm = (int) $nmf;
                $ns = (int) round(($nmf - $nm) * 60);
                if ($ns >= 60) { $ns = 0; $nm++; }
                if ($nm >= 60) { $nm = 0; $nd++; }
            @endphp
            <div class="natal-row">
                <span class="planet-glyph">{{ $bodyGlyphs[$planet['body']] ?? '?' }}</span>
                <span>{{ $bodyNames[$planet['body']] ?? '' }}</span>
                <span class="planet-in">in</span>
                <span>{{ $signGlyphs[$planet['sign']] ?? '' }} {{ $signNames[$planet['sign']] ?? '' }}</span>
                <span style="color:var(--theme-muted);font-size:0.78rem">{{ $nd }}°{{ str_pad($nm,2,'0',STR_PAD_LEFT) }}'{{ str_pad($ns,2,'0',STR_PAD_LEFT) }}"</span>
                <span style="color:var(--theme-muted);font-size:0.78rem;text-align:right">{{ isset($planet['house']) ? ($houseNames[$planet['house'] - 1] ?? '') : '' }}@if($planet['is_retrograde']) <span class="rx-badge">Rx</span>@endif</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- AI Synthesis + Short Version toggle --}}
    <div class="action-row">
        @include('partials.premium-button', ['context' => 'daily', 'generated' => ($aiText !== null)])

        {{-- Short Version toggle --}}
        <label class="toggle-label">
            <span class="toggle-switch">
                <input id="short-ver-chk" type="checkbox" onchange="applyState(this.checked)"
                       class="toggle-input">
                <span id="short-ver-track" class="toggle-track"></span>
                <span id="short-ver-thumb" class="toggle-thumb"></span>
            </span>
            Short Version
        </label>

        {{-- PDF button --}}
        <a href="{{ route('daily.pdf', [$profile, $date]) }}"
           onclick="showDailyPdfLoading()"
           class="btn-pdf"
           title="{{ __('ui.daily.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
    </div>

    {{-- AI synthesis card --}}
    <div id="synthesis-card" class="card card-section card-ai" @if($aiText === null) style="display:none" @endif>
        <div class="section-label section-label-gold">{{ __('ui.daily.ai_overview') }}</div>
        <div id="synthesis-text" class="prose">
            {!! $aiText ?? '' !!}
        </div>
    </div>

    {{-- KEY TRANSIT FACTORS --}}
    @foreach([['full', $transitTexts], ['short', $transitTextsShort]] as [$ver, $items])
    <div class="card card-mt" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label">{{ __('ui.daily.key_transits') }}</div>

        @if(empty($items))
        <div class="empty-msg">{{ __('ui.daily.no_transits') }}</div>
        @else
        @foreach($items as $item)
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="chip-transit">{{ $item['chip'] }}</div>
            @if($item['text'])
            <div class="prose">{!! $item['text'] !!}</div>
            @endif
        </div>
        @endforeach
        @endif
    </div>
    @endforeach

    {{-- Lunar block --}}
    @foreach([['full', $lunarText], ['short', $lunarTextShort]] as [$ver, $text])
    <div class="card card-mt card-lunar" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="section-label section-label-sm">{{ __('ui.daily.lunar_day') }}</div>
        <div class="card-meta" @if($text) style="margin-bottom:0.6rem" @endif>
            {{ $phaseEmoji }} Moon in {{ $moonSignGlyph }} {{ $dto->moon->signName }}
            &nbsp;·&nbsp; Day {{ $dto->moon->lunarDay }} / 30
            &nbsp;·&nbsp; {{ $dto->moon->phaseName }}
        </div>
        @if($text)
        <div class="prose">{!! $text !!}</div>
        @endif
    </div>
    @endforeach

    {{-- Tip of the day --}}
    @foreach([['full', $tipText], ['short', $tipTextShort]] as [$ver, $text])
    @if($text)
    <div class="card card-mt card-tip" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="label-gold">{{ __('ui.daily.tip_label') }}</div>
        <div class="prose">{!! $text !!}</div>
    </div>
    @endif
    @endforeach

    {{-- Clothing & Jewelry --}}
    @foreach([['full', $clothingText], ['short', $clothingTextShort]] as [$ver, $text])
    @if($text)
    <div class="card card-mt card-clothing" data-ver="{{ $ver }}" @if($ver === 'short') style="display:none" @endif>
        <div class="label-purple">{{ __('ui.daily.clothing_label') }}</div>
        <div class="card-meta" style="margin-bottom:0.5rem">
            🗓 {{ $dto->dayRuler->weekday }}
            &nbsp;·&nbsp; {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}
            @if($dto->natalVenusSign !== null)
            &nbsp;·&nbsp; Venus in {{ $signNames[$dto->natalVenusSign] ?? '' }}
            @endif
        </div>
        <div class="prose">{!! $text !!}</div>
    </div>
    @endif
    @endforeach

    {{-- Areas of Life --}}
    <div class="card card-flush card-mt">
        <div class="card-header">
            <div class="section-label">{{ __('ui.areas.title') }}</div>
        </div>
        @foreach($dto->areasOfLife as $area)
        @php
            $emoji  = $areaEmojis[$area->slug] ?? '';
            $isWait = $area->rating === 0;
        @endphp
        <div class="area-row" @if($isWait) style="opacity:0.55" @endif>
            <span>{{ $emoji }} {{ $area->name }}</span>
            @if($isWait)
            <span class="area-wait">{{ __('ui.rating_wait') }}</span>
            @else
            <span class="area-stars">{{ str_repeat('★', $area->rating) }}{{ str_repeat('☆', $area->maxRating - $area->rating) }}</span>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Day meta --}}
    <div class="card card-mt card-day-meta">
        <div>🗓 <strong>{{ $dto->dayRuler->weekday }}</strong>
            &nbsp;·&nbsp; {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}
        </div>
        <div>🎨 {{ $dto->dayRuler->color }}
            &nbsp;·&nbsp; 💎 {{ $dto->dayRuler->gem }}
            &nbsp;·&nbsp; 🔢 {{ $dto->dayRuler->number }}
        </div>
    </div>

    {{-- PDF button at bottom --}}
    <div class="pdf-row-end">
        <a href="{{ route('daily.pdf', [$profile, $date]) }}"
           onclick="showDailyPdfLoading()"
           class="btn-pdf"
           title="{{ __('ui.daily.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
    </div>

    {{-- Footer --}}
    <div class="back-link-row">
        <a href="{{ route('natal.show', $profile) }}" class="back-link">
            {{ __('ui.daily.back_to_natal') }}
        </a>
    </div>

@endsection

{{-- PDF loading overlay --}}
<div id="daily-pdf-overlay" class="loading-overlay overlay-pdf" style="display:none">
    <div class="spinner spinner-sm"></div>
    <p class="overlay-msg-pdf">{{ __('ui.daily.preparing_pdf') }}</p>
</div>
<script>
function showDailyPdfLoading() {
    var overlay = document.getElementById('daily-pdf-overlay');
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

@push('scripts')
<script>
// ── Short version toggle ──────────────────────────────────────────────────
function applyState(short) {
    var track = document.getElementById('short-ver-track');
    var thumb = document.getElementById('short-ver-thumb');
    if (track) track.style.background = short ? '#6a329f' : '#a09ab8';
    if (thumb) thumb.style.transform  = short ? 'translateX(16px)' : '';

    document.querySelectorAll('[data-ver]').forEach(function(el) {
        var ver = el.getAttribute('data-ver');
        el.style.display = (short ? ver === 'short' : ver === 'full') ? '' : 'none';
    });
}

// ── AI synthesis generation ───────────────────────────────────────────────
window.addEventListener('premium-confirmed', function(e) {
    if ((e.detail && e.detail.context) !== 'daily') return;

    var card = document.getElementById('synthesis-card');
    var text = document.getElementById('synthesis-text');
    if (!card || !text) return;

    card.style.display = '';
    text.innerHTML = '<span style="color:var(--theme-muted);font-size:0.85rem">{{ __('ui.daily.generating') }}</span>';

    var csrf = document.querySelector('meta[name=csrf-token]');
    fetch('{{ route('daily.synthesis', [$profile, $date]) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf ? csrf.content : '',
            'Accept': 'application/json',
        },
    })
    .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
    .then(function(data) { text.innerHTML = data.html || ''; })
    .catch(function() {
        text.innerHTML = '<span style="color:#e87070;font-size:0.85rem">Generation failed. Please try again.</span>';
    });
});
</script>
@endpush
