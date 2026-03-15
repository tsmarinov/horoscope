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
        'semisextile'  => '⚺', 'semisquare'   => '∠', 'sesquiquadrate' => '⊼',
    ];
    $planets = $chart->planets ?? [];
    $aspects = $chart->aspects ?? [];
    $houses  = $chart->houses  ?? [];
    $sign    = $profile->sunSign();
    $age     = $profile->age();
@endphp

@section('content')
    {{-- Header --}}
    <div style="padding:0 1rem 1rem">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.3rem">
                    {{ $profile->name }}
                </h1>
                <div style="font-size:0.82rem;color:var(--theme-muted);display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center">
                    @if($sign)<span>{{ $sign['glyph'] }} {{ $sign['name'] }}</span><span>·</span>@endif
                    <span>{{ $profile->birth_date?->format('M j, Y') }}</span>
                    @if($age !== null)<span>· {{ $age }} y.o.</span>@endif
                    @if($profile->birth_time)<span>· {{ substr($profile->birth_time, 0, 5) }}</span>@endif
                    @if($profile->birthCity)<span>· {{ $profile->birthCity->name }}</span>@endif
                    @if($chart->ascendant !== null)
                        @php $ascSign = (int)floor($chart->ascendant / 30); @endphp
                        <span>· ASC {{ $signGlyphs[$ascSign] }} {{ $signNames[$ascSign] }}</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('stellar-profiles.index', ['edit' => $profile->id]) }}"
               style="font-size:0.78rem;color:var(--theme-muted);text-decoration:none;white-space:nowrap;margin-top:0.2rem">
                ← Edit Profile
            </a>
        </div>
    </div>

    {{-- Planets --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:0.75rem 1rem 0.5rem">
            <div class="section-label">Planets</div>
        </div>
        <div style="overflow-x:auto">
            <table class="ct">
                <thead>
                    <tr>
                        <th style="text-align:left">Planet</th>
                        <th style="text-align:left">Sign</th>
                        <th style="text-align:right">Position</th>
                        @if(count($houses))<th>House</th>@endif
                        <th>Rx</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($planets as $planet)
                    @php
                        $deg = floor($planet['degree']);
                        $min = round(($planet['degree'] - $deg) * 60);
                        if ($min >= 60) { $deg++; $min = 0; }
                    @endphp
                    <tr>
                        <td>
                            <span style="font-size:1rem;margin-right:0.35rem;color:#6a329f">{{ $bodyGlyphs[$planet['body']] ?? '?' }}</span>
                            <span style="font-weight:500">{{ $bodyNames[$planet['body']] ?? 'Body ' . $planet['body'] }}</span>
                        </td>
                        <td class="ct-muted">
                            <span style="margin-right:0.25rem">{{ $signGlyphs[$planet['sign']] ?? '' }}</span>{{ $signNames[$planet['sign']] ?? '' }}
                        </td>
                        <td class="ct-num">{{ $deg }}°{{ str_pad($min, 2, '0', STR_PAD_LEFT) }}'</td>
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
    <div class="card" style="padding:0;overflow:hidden;margin-top:0.75rem">
        <div style="padding:0.75rem 1rem 0.5rem">
            <div class="section-label">Houses (Placidus)</div>
        </div>
        <div style="overflow-x:auto">
            <table class="ct">
                <tbody>
                    @foreach($houses as $i => $cusp)
                    @php
                        $sign = (int) floor($cusp / 30);
                        $deg  = floor(fmod($cusp, 30));
                        $min  = round((fmod($cusp, 30) - $deg) * 60);
                        if ($min >= 60) { $deg++; $min = 0; }
                    @endphp
                    <tr>
                        <td style="font-weight:600;color:{{ in_array($i, [0,3,6,9]) ? '#6a329f' : 'var(--theme-muted)' }};width:2rem">{{ $houseNames[$i] }}</td>
                        <td class="ct-muted"><span style="margin-right:0.25rem">{{ $signGlyphs[$sign] }}</span>{{ $signNames[$sign] }}</td>
                        <td class="ct-num">{{ $deg }}°{{ str_pad($min, 2, '0', STR_PAD_LEFT) }}'</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Aspects --}}
    @if(count($aspects))
    <div class="card" style="margin-top:0.75rem">
        <div class="section-label" style="margin-bottom:0.75rem">Aspects</div>
        <div style="display:flex;flex-direction:column;gap:0.3rem">
            @foreach($aspects as $asp)
            @php
                $a   = $asp['body_a'] ?? 0;
                $b   = $asp['body_b'] ?? 0;
                $orb = round(abs($asp['orb'] ?? 0), 2);
                $type = $asp['aspect'] ?? '';
                $glyph = $aspectGlyphs[$type] ?? '∗';
            @endphp
            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem">
                <span style="color:#6a329f;font-size:1rem;width:1.1rem;text-align:center">{{ $bodyGlyphs[$a] ?? '?' }}</span>
                <span style="color:var(--theme-muted)">{{ $bodyNames[$a] ?? $a }}</span>
                <span style="color:var(--theme-text);font-size:1rem;width:1.1rem;text-align:center">{{ $glyph }}</span>
                <span style="color:var(--theme-muted);flex:1">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                <span style="color:#6a329f;font-size:1rem;width:1.1rem;text-align:center">{{ $bodyGlyphs[$b] ?? '?' }}</span>
                <span style="color:var(--theme-muted)">{{ $bodyNames[$b] ?? $b }}</span>
                <span style="color:var(--theme-muted);width:3rem;text-align:right">{{ $orb }}°</span>
                @if(!empty($asp['applying']))
                    <span style="color:#6a329f;width:1.2rem;text-align:center">↑</span>
                @else
                    <span style="color:var(--theme-muted);width:1.2rem;text-align:center">↓</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div style="padding:0.5rem 0 1.5rem;text-align:center">
        <a href="{{ route('stellar-profiles.index') }}" style="font-size:0.8rem;color:var(--theme-muted);text-decoration:underline">← Stellar Profiles</a>
    </div>

@endsection
