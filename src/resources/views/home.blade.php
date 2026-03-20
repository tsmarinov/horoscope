@extends('layouts.app')

@section('title', __('ui.home.hero_title'))
@section('description', __('ui.home.hero_sub'))

@section('content')

{{-- ── Hero ──────────────────────────────────────────────────────────────── --}}
<div class="page-hero-lg">
    <h1 class="font-display" style="font-size:1.35rem;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.4rem">
        {{ __('ui.home.hero_title') }}
    </h1>
    <p class="page-subtitle" style="max-width:26rem;margin:0 auto">
        {{ __('ui.home.hero_sub') }}
    </p>
</div>

{{-- ── Cards grid ────────────────────────────────────────────────────────── --}}
<div class="hcard-grid home-cards">
    @foreach ($cards as $card)
    @if($card['disabled'])
    <div class="hcard-disabled">
    @else
    <a href="{{ $card['url'] }}" class="hcard"
       onmouseover="this.style.borderColor='#6a329f'"
       onmouseout="this.style.borderColor='var(--theme-border)'"
    >
    @endif

        {{-- Art header --}}
        <div class="hcard-art">
            @switch($card['key'])

            @case('daily')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <circle cx="140" cy="55" r="52" stroke="#6a329f" stroke-width="0.5" opacity="0.12"/>
                <circle cx="140" cy="55" r="38" stroke="#6a329f" stroke-width="0.5" opacity="0.18"/>
                <circle cx="140" cy="55" r="22" stroke="#6a329f" stroke-width="1.5" opacity="0.65"/>
                <circle cx="140" cy="55" r="12" fill="#6a329f" opacity="0.12"/>
                <line x1="140" y1="24" x2="140" y2="17" stroke="#6a329f" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                <line x1="140" y1="86" x2="140" y2="93" stroke="#6a329f" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                <line x1="109" y1="55" x2="102" y2="55" stroke="#6a329f" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                <line x1="171" y1="55" x2="178" y2="55" stroke="#6a329f" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                <line x1="118" y1="33" x2="113" y2="28" stroke="#6a329f" stroke-width="1.75" stroke-linecap="round" opacity="0.5"/>
                <line x1="162" y1="77" x2="167" y2="82" stroke="#6a329f" stroke-width="1.75" stroke-linecap="round" opacity="0.5"/>
                <line x1="162" y1="33" x2="167" y2="28" stroke="#6a329f" stroke-width="1.75" stroke-linecap="round" opacity="0.5"/>
                <line x1="118" y1="77" x2="113" y2="82" stroke="#6a329f" stroke-width="1.75" stroke-linecap="round" opacity="0.5"/>
                <circle cx="55"  cy="22" r="1.5" fill="#6a329f" opacity="0.3"/>
                <circle cx="230" cy="18" r="1"   fill="#6a329f" opacity="0.3"/>
                <circle cx="240" cy="88" r="1.5" fill="#6a329f" opacity="0.25"/>
                <circle cx="40"  cy="80" r="1"   fill="#6a329f" opacity="0.25"/>
            </svg>
            @break

            @case('weekly')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <circle cx="130" cy="55" r="38" stroke="#6a329f" stroke-width="0.5" opacity="0.15"/>
                <path d="M155 55 C155 37 145 22 130 19 C148 21 162 36.5 162 55 C162 73.5 148 89 130 91 C145 88 155 73 155 55Z" fill="#6a329f" opacity="0.55"/>
                <circle cx="50"  cy="22" r="2"   fill="#6a329f" opacity="0.45"/>
                <circle cx="220" cy="18" r="1.5" fill="#6a329f" opacity="0.4"/>
                <circle cx="230" cy="82" r="2"   fill="#6a329f" opacity="0.4"/>
                <circle cx="45"  cy="85" r="1"   fill="#6a329f" opacity="0.3"/>
                <circle cx="240" cy="50" r="1.5" fill="#6a329f" opacity="0.35"/>
                <circle cx="30"  cy="58" r="1"   fill="#6a329f" opacity="0.25"/>
                <path d="M205 28 L207 22.5 L209 28 L214.5 30 L209 32 L207 37.5 L205 32 L199.5 30Z" fill="#6a329f" opacity="0.4"/>
                <path d="M65  42 L66.5 38 L68 42 L72 43.5 L68 45 L66.5 49 L65 45 L61 43.5Z" fill="#6a329f" opacity="0.3"/>
                <path d="M248 68 L249 65.5 L250 68 L252.5 69 L250 70 L249 72.5 L248 70 L245.5 69Z" fill="#6a329f" opacity="0.3"/>
            </svg>
            @break

            @case('monthly')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                {{-- dot grid 6×4 --}}
                @php
                    $cols = [70,98,126,154,182,210];
                    $rows = [22,42,62,82];
                    $acc  = [[1,0],[3,1],[0,2],[4,2],[2,3],[5,0]];
                @endphp
                @foreach($rows as $ri => $ry)
                    @foreach($cols as $ci => $cx)
                        @php $isAcc = in_array([$ci,$ri], $acc); @endphp
                        @if($isAcc)
                        <circle cx="{{ $cx }}" cy="{{ $ry }}" r="4.5" fill="#6a329f" opacity="0.55"/>
                        @else
                        <circle cx="{{ $cx }}" cy="{{ $ry }}" r="2.5" fill="#6a329f" opacity="0.18"/>
                        @endif
                    @endforeach
                @endforeach
                {{-- crescent --}}
                <path d="M240 40 C240 31 235 24 227 22 C235.5 23.5 242 31 242 40 C242 49 235.5 56.5 227 58 C235 56 240 49 240 40Z" fill="#6a329f" opacity="0.45"/>
            </svg>
            @break

            @case('solar')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <ellipse cx="130" cy="62" rx="90" ry="36" stroke="#6a329f" stroke-width="1" opacity="0.28" stroke-dasharray="5 4"/>
                <circle  cx="100" cy="62" r="7"  fill="#6a329f" opacity="0.4"/>
                <circle  cx="100" cy="62" r="13" stroke="#6a329f" stroke-width="0.75" opacity="0.18"/>
                <circle  cx="100" cy="62" r="20" stroke="#6a329f" stroke-width="0.5"  opacity="0.1"/>
                {{-- star at return point --}}
                <path d="M212 28 L214.5 20 L217 28 L225 30.5 L217 33 L214.5 41 L212 33 L204 30.5Z" fill="#6a329f" opacity="0.7"/>
                <circle cx="130" cy="26" r="4" fill="#6a329f" opacity="0.45"/>
                <circle cx="220" cy="62" r="5" fill="#6a329f" opacity="0.3"/>
                <circle cx="50"  cy="28" r="1.5" fill="#6a329f" opacity="0.3"/>
                <circle cx="240" cy="88" r="1.5" fill="#6a329f" opacity="0.25"/>
                <circle cx="40"  cy="80" r="1"   fill="#6a329f" opacity="0.2"/>
            </svg>
            @break

            @case('natal')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <circle cx="140" cy="55" r="48" stroke="#6a329f" stroke-width="1"    opacity="0.28"/>
                <circle cx="140" cy="55" r="36" stroke="#6a329f" stroke-width="0.75" opacity="0.22"/>
                <circle cx="140" cy="55" r="22" stroke="#6a329f" stroke-width="1"    opacity="0.32"/>
                <circle cx="140" cy="55" r="8"  fill="#6a329f"   opacity="0.2"/>
                @for($i = 0; $i < 12; $i++)
                    @php
                        $a  = $i * 30 * M_PI / 180;
                        $x1 = round(140 + 22 * cos($a), 1);
                        $y1 = round(55  + 22 * sin($a), 1);
                        $x2 = round(140 + 48 * cos($a), 1);
                        $y2 = round(55  + 48 * sin($a), 1);
                    @endphp
                    <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="#6a329f" stroke-width="0.75" opacity="0.22"/>
                @endfor
                @php
                    $pts = [0.15, 0.58, 1.1, 1.72, 0.85, 1.4];
                @endphp
                @foreach($pts as $a)
                    @php
                        $px = round(140 + 40 * cos($a * M_PI), 1);
                        $py = round(55  + 40 * sin($a * M_PI), 1);
                    @endphp
                    <circle cx="{{ $px }}" cy="{{ $py }}" r="3" fill="#6a329f" opacity="0.6"/>
                @endforeach
            </svg>
            @break

            @case('synastry')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <circle cx="115" cy="55" r="38" stroke="#6a329f" stroke-width="1.25" opacity="0.42"/>
                <circle cx="165" cy="55" r="38" stroke="#6a329f" stroke-width="1.25" opacity="0.42"/>
                <circle cx="115" cy="55" r="24" stroke="#6a329f" stroke-width="0.75" opacity="0.18"/>
                <circle cx="165" cy="55" r="24" stroke="#6a329f" stroke-width="0.75" opacity="0.18"/>
                {{-- intersection fill --}}
                <path d="M140 21.5 C153 29 160 41.5 160 55 C160 68.5 153 81 140 88.5 C127 81 120 68.5 120 55 C120 41.5 127 29 140 21.5Z" fill="#6a329f" opacity="0.1"/>
                <circle cx="115" cy="55" r="3" fill="#6a329f" opacity="0.5"/>
                <circle cx="165" cy="55" r="3" fill="#6a329f" opacity="0.5"/>
                <circle cx="50"  cy="30" r="1.5" fill="#6a329f" opacity="0.3"/>
                <circle cx="235" cy="28" r="1.5" fill="#6a329f" opacity="0.3"/>
            </svg>
            @break

            @case('lunar')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <line x1="14" y1="55" x2="266" y2="55" stroke="#6a329f" stroke-width="0.5" opacity="0.12" stroke-dasharray="2 4"/>
                @php
                    $phases = [
                        ['cx'=>34,  'fill'=>0.0 ],
                        ['cx'=>82,  'fill'=>0.28],
                        ['cx'=>140, 'fill'=>0.52],
                        ['cx'=>198, 'fill'=>0.78],
                        ['cx'=>246, 'fill'=>1.0 ],
                    ];
                    $r = 20;
                @endphp
                @foreach($phases as $ph)
                <circle cx="{{ $ph['cx'] }}" cy="55" r="{{ $r }}" stroke="#6a329f" stroke-width="1" opacity="0.38"/>
                @if($ph['fill'] > 0)
                <defs>
                    <clipPath id="lcp{{ $ph['cx'] }}">
                        <circle cx="{{ $ph['cx'] }}" cy="55" r="{{ $r - 0.5 }}"/>
                    </clipPath>
                </defs>
                @php $left = $ph['cx'] - $r + ($r * 2 * (1 - $ph['fill'])); @endphp
                <rect x="{{ round($left,1) }}" y="{{ 55 - $r }}" width="{{ round($r * 2 * $ph['fill'],1) }}" height="{{ $r * 2 }}" fill="#6a329f" opacity="0.38" clip-path="url(#lcp{{ $ph['cx'] }})"/>
                @endif
                @endforeach
            </svg>
            @break

            @case('retrograde')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <path d="M18 58 C45 58 62 28 90 58 C107 73 107 36 130 36 C153 36 153 74 170 58 C196 35 218 58 262 58" stroke="#6a329f" stroke-width="1.5" opacity="0.48" fill="none" stroke-linecap="round"/>
                <circle cx="90"  cy="58" r="3.5" fill="#6a329f" opacity="0.5"/>
                <circle cx="170" cy="58" r="3.5" fill="#6a329f" opacity="0.5"/>
                <circle cx="130" cy="36" r="6"   fill="#6a329f" opacity="0.32"/>
                <circle cx="130" cy="36" r="10"  stroke="#6a329f" stroke-width="0.75" opacity="0.18"/>
                <text x="130" y="31" font-family="serif" font-size="13" fill="#6a329f" opacity="0.55" text-anchor="middle">℞</text>
                <circle cx="40"  cy="28" r="1.5" fill="#6a329f" opacity="0.32"/>
                <circle cx="240" cy="28" r="1.5" fill="#6a329f" opacity="0.32"/>
                <circle cx="250" cy="88" r="1"   fill="#6a329f" opacity="0.25"/>
                <circle cx="30"  cy="85" r="1"   fill="#6a329f" opacity="0.25"/>
                <path d="M218 22 L219.5 18 L221 22 L225 23.5 L221 25 L219.5 29 L218 25 L214 23.5Z" fill="#6a329f" opacity="0.38"/>
            </svg>
            @break

            @case('weekday')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                {{-- 7 day circles --}}
                @php $dayX = [40,80,120,160,200,240,260]; @endphp
                @foreach([40,80,120,160,200,240,260] as $i => $dx)
                <circle cx="{{ $dx }}" cy="55" r="{{ $i === 0 ? 18 : 14 }}" stroke="#6a329f" stroke-width="{{ $i === 0 ? 1.5 : 0.75 }}" opacity="{{ $i === 0 ? 0.65 : 0.25 }}"/>
                @if($i === 0)
                <circle cx="{{ $dx }}" cy="55" r="10" fill="#6a329f" opacity="0.15"/>
                @endif
                @endforeach
                {{-- Planet symbol above first circle --}}
                <circle cx="40" cy="26" r="7" stroke="#6a329f" stroke-width="1.25" opacity="0.55"/>
                <line x1="40" y1="33" x2="40" y2="37" stroke="#6a329f" stroke-width="1.25" opacity="0.55" stroke-linecap="round"/>
                <line x1="36" y1="35" x2="44" y2="35" stroke="#6a329f" stroke-width="1.25" opacity="0.55" stroke-linecap="round"/>
                {{-- connecting arc --}}
                <path d="M58 55 Q140 30 222 55" stroke="#6a329f" stroke-width="0.5" opacity="0.15" fill="none" stroke-dasharray="3 3"/>
            </svg>
            @break


            @case('planets')
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                {{-- Orbits --}}
                <ellipse cx="140" cy="55" rx="20"  ry="6"  stroke="#6a329f" stroke-width="0.5" opacity="0.2"/>
                <ellipse cx="140" cy="55" rx="36"  ry="11" stroke="#6a329f" stroke-width="0.5" opacity="0.2"/>
                <ellipse cx="140" cy="55" rx="54"  ry="17" stroke="#6a329f" stroke-width="0.5" opacity="0.18"/>
                <ellipse cx="140" cy="55" rx="74"  ry="23" stroke="#6a329f" stroke-width="0.5" opacity="0.15"/>
                <ellipse cx="140" cy="55" rx="96"  ry="30" stroke="#6a329f" stroke-width="0.5" opacity="0.12"/>
                <ellipse cx="140" cy="55" rx="118" ry="36" stroke="#6a329f" stroke-width="0.5" opacity="0.1"/>
                {{-- Sun --}}
                <circle cx="140" cy="55" r="8" fill="#6a329f" opacity="0.45"/>
                <circle cx="140" cy="55" r="13" stroke="#6a329f" stroke-width="0.75" opacity="0.2"/>
                {{-- Planets on orbits --}}
                <circle cx="160" cy="55" r="3"   fill="#6a329f" opacity="0.5"/>
                <circle cx="176" cy="47" r="3.5" fill="#6a329f" opacity="0.48"/>
                <circle cx="194" cy="60" r="4"   fill="#6a329f" opacity="0.45"/>
                <circle cx="214" cy="44" r="3"   fill="#6a329f" opacity="0.42"/>
                {{-- Saturn ring --}}
                <ellipse cx="236" cy="52" rx="10" ry="3" stroke="#6a329f" stroke-width="1" opacity="0.35"/>
                <circle  cx="236" cy="52" r="4.5" fill="#6a329f" opacity="0.38"/>
                {{-- Far planets --}}
                <circle cx="254" cy="60" r="3" fill="#6a329f" opacity="0.3"/>
            </svg>
            @break

            @default
            <svg viewBox="0 0 280 110" fill="none" class="hcard-svg">
                <circle cx="140" cy="55" r="32" stroke="#6a329f" stroke-width="1" opacity="0.3"/>
            </svg>
            @endswitch

            {{-- Glyph badge --}}
            <span class="hcard-glyph">{{ $card['glyph'] }}</span>
        </div>

        {{-- Card body --}}
        <div class="hcard-body">
            <span class="hcard-title">
                {{ $card['title'] }}
            </span>
            <span class="hcard-desc">
                {{ $card['desc'] }}
            </span>
            <span class="hcard-cta">
                {{ $card['cta'] }} →
            </span>
        </div>

    @if($card['disabled'])</div>@else</a>@endif
    @endforeach
</div>

{{-- ── Footer CTA ────────────────────────────────────────────────────────── --}}
<p class="home-cta">
    <a href="{{ route('register') }}" style="color:inherit;text-decoration:underline">{{ __('ui.home.cta_free') }}</a>
</p>

<style>
@media (max-width: 419px) { .home-cards { grid-template-columns: 1fr !important; } }
@media (min-width: 420px) { .home-cards { grid-template-columns: repeat(2, 1fr) !important; } }
</style>

@endsection
