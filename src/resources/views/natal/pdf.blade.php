<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Natal Chart — {{ $profile->name }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10pt;
        color: #1a1a2e;
        line-height: 1.55;
        background: #fff;
    }

    /* ── Page header ─────────────────────────────────────────────────────── */
    .page-header {
        border-bottom: 2px solid #6a329f;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }
    .page-header h1 {
        font-size: 18pt;
        font-weight: 700;
        color: #6a329f;
        letter-spacing: 0.04em;
    }
    .page-header .meta {
        font-size: 9pt;
        color: #666;
        margin-top: 3px;
    }
    .page-header .meta span { margin-right: 8px; }

    /* ── Section ─────────────────────────────────────────────────────────── */
    .section {
        margin-bottom: 18px;
    }
    .section:last-of-type {
        margin-bottom: 0;
    }
    .section-title {
        font-size: 8.5pt;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #6a329f;
        border-bottom: 1px solid #e0d8f0;
        padding-bottom: 3px;
        margin-bottom: 8px;
    }

    /* ── Table ────────────────────────────────────────────────────────────── */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    table th {
        text-align: left;
        font-weight: 600;
        color: #444;
        border-bottom: 1px solid #ddd;
        padding: 3px 6px;
        font-size: 9.5pt;
    }
    table td {
        padding: 3px 6px;
        border-bottom: 1px solid #f0ecf8;
        vertical-align: top;
    }
    table tr:last-child td { border-bottom: none; }
    .accent { color: #6a329f; font-weight: 600; }
    .muted  { color: #666; }
    .num    { text-align: right; font-variant-numeric: tabular-nums; }
    .rx-on  { color: #c03828; font-weight: 700; }

    /* ── Two-column layout ───────────────────────────────────────────────── */
    .two-col { width: 100%; }
    .two-col td { width: 50%; vertical-align: top; padding-right: 12px; }
    .two-col td:last-child { padding-right: 0; }

    /* ── Text entries (aspects etc.) ─────────────────────────────────────── */
    .entry { margin-bottom: 10px; page-break-inside: avoid; }
    .entry-label {
        font-size: 9.5pt;
        font-weight: 700;
        color: #6a329f;
        margin-bottom: 2px;
    }
    .entry-sub {
        font-size: 9pt;
        color: #888;
        margin-bottom: 2px;
    }
    .entry-text {
        font-size: 10pt;
        color: #333;
        line-height: 1.55;
    }
    .entry-text strong { font-weight: 700; color: #1a1a2e; }
    .entry-text em     { font-style: italic; color: #6a329f; }

    /* ── Wheel placeholder ───────────────────────────────────────────────── */
    .wheel-placeholder {
        border: 1px dashed #c8b8e8;
        border-radius: 6px;
        padding: 14px 18px;
        margin-bottom: 18px;
        color: #999;
        font-size: 9pt;
        text-align: center;
    }

    /* ── Footer ──────────────────────────────────────────────────────────── */
    .pdf-footer {
        margin-top: 20px;
        border-top: 1px solid #e0d8f0;
        padding-top: 6px;
        font-size: 8pt;
        color: #aaa;
        text-align: center;
    }
</style>
</head>
<body>

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $bodyGlyphs = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'];
    $bodyNames  = ['Sun','Moon','Mercury','Venus','Mars','Jupiter','Saturn','Uranus','Neptune','Pluto','Chiron','North Node','Lilith'];
    $houseNames = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $aspectGlyphs = [
        'conjunction'=>'☌','opposition'=>'☍','trine'=>'△','square'=>'□',
        'sextile'=>'⚹','quincunx'=>'⚻','semi_sextile'=>'⚺','mutual_reception'=>'⇌',
    ];
    $aspectNames = [
        'conjunction'=>'Conjunction','opposition'=>'Opposition','trine'=>'Trine',
        'square'=>'Square','sextile'=>'Sextile','quincunx'=>'Quincunx',
        'semi_sextile'=>'Semi-sextile','mutual_reception'=>'Mutual Reception',
    ];
    $planets = $chart->planets ?? [];
    $aspects = $chart->aspects ?? [];
    $houses  = $chart->houses  ?? [];

    $ascSign = $chart->ascendant !== null ? (int)floor($chart->ascendant / 30) : null;
    $ascDeg  = $chart->ascendant !== null ? (int)floor(fmod($chart->ascendant, 30)) : null;
    $ascMin  = $chart->ascendant !== null ? (int)round((fmod($chart->ascendant, 30) - $ascDeg) * 60) : null;
@endphp

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
@include('partials.pdf-header', ['pageTitle' => __('ui.natal.page_title')])

{{-- ── Profile info (centered, above wheel) ─────────────────────────────── --}}
<div style="text-align:center;margin-bottom:10px;margin-top:4px">
    <div style="font-size:8pt;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#8a70b8;margin-bottom:5px">{{ __('ui.natal.page_title') }}</div>
    <div style="font-size:16pt;font-weight:700;color:#1a1a2e;letter-spacing:0.02em">{{ $profile->name }}</div>
    <div style="font-size:9pt;color:#666;margin-top:3px">
        @if($profile->birth_date)<span>{{ $profile->birth_date->format('F j, Y') }}</span>@endif
        @if($profile->birth_time)<span style="margin-left:8px">{{ substr($profile->birth_time, 0, 5) }}</span>@endif
        @if($profile->birthCity)<span style="margin-left:8px">{{ $profile->birthCity->name }}</span>@endif
        @if($ascSign !== null)<span style="margin-left:8px">ASC {{ $signGlyphs[$ascSign] }} {{ $signNames[$ascSign] }} {{ $ascDeg }}°{{ str_pad($ascMin, 2, '0', STR_PAD_LEFT) }}'</span>@endif
    </div>
</div>

{{-- ── Natal Wheel ─────────────────────────────────────────────────────── --}}
@php
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
<div style="text-align:center;margin-bottom:18px">
    @include('partials.wheel-natal', [
        'svgId'     => 'natal-wheel',
        'svgWidth'  => '560',
        'svgHeight' => '560',
        'svgClass'  => '',
        'pdfMode'   => true,
    ])
</div>

{{-- ── Portrait ──────────────────────────────────────────────────────── --}}
@if(!empty($portrait))
<div class="section" style="background:#fdf9ec;border:1px solid #e8d88a;border-radius:5px;padding:10px 12px">
    <div style="font-size:10pt;line-height:1.65;color:#1a1a2e">{!! $portrait !!}</div>
</div>
@endif

{{-- ── Planets + Houses (two-column) ───────────────────────────────────── --}}
<div class="section" style="overflow:hidden">
    <div style="float:left;width:48%">
        <div class="section-title">{{ __('ui.natal.section_planets') }}</div>
        <table>
            <thead>
                <tr>
                    <th>Planet</th>
                    <th>Sign</th>
                    <th class="num">Position</th>
                    @if(count($houses))<th>H</th>@endif
                    <th>Rx</th>
                </tr>
            </thead>
            <tbody>
            @foreach($planets as $p)
            @php
                $deg = floor($p['degree']);
                $min = floor(($p['degree'] - $deg) * 60);
            @endphp
            <tr>
                <td><span class="accent">{{ $bodyGlyphs[$p['body']] ?? '' }}</span> {{ $bodyNames[$p['body']] ?? '' }}</td>
                <td class="muted">{{ $signGlyphs[$p['sign']] ?? '' }} {{ $signNames[$p['sign']] ?? '' }}</td>
                <td class="num">{{ $deg }}°{{ str_pad($min, 2, '0', STR_PAD_LEFT) }}'</td>
                @if(count($houses))<td class="accent" style="text-align:center">{{ $houseNames[($p['house'] ?? 1) - 1] ?? '' }}</td>@endif
                <td class="{{ $p['is_retrograde'] ? 'rx-on' : 'muted' }}">{{ $p['is_retrograde'] ? 'Rx' : '·' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if(count($houses))
    <div style="float:right;width:48%">
        <div class="section-title">{{ __('ui.natal.section_houses') }}</div>
        <table>
            <tbody>
            @foreach($houses as $i => $cusp)
            @php
                $s   = (int)floor($cusp / 30);
                $d   = floor(fmod($cusp, 30));
                $m   = round((fmod($cusp, 30) - $d) * 60);
            @endphp
            <tr>
                <td style="{{ in_array($i,[0,3,6,9]) ? 'color:#6a329f' : 'color:#666' }};width:24px">{{ $houseNames[$i] }}</td>
                <td class="muted">{{ $signGlyphs[$s] ?? '' }} {{ $signNames[$s] ?? '' }}</td>
                <td class="num">{{ $d }}°{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}'</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <div style="clear:both"></div>
</div>

{{-- ── Aspects table ─────────────────────────────────────────────────────── --}}
@if(count($aspects))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_aspects_table') }}</div>
    <table>
        <tbody>
        @foreach($aspects as $asp)
        @php $type = $asp['aspect'] ?? ''; @endphp
        @continue($type === 'mutual_reception')
        <tr>
            <td style="width:14px" class="accent">{{ $bodyGlyphs[$asp['body_a'] ?? 0] ?? '' }}</td>
            <td style="width:70px">{{ $bodyNames[$asp['body_a'] ?? 0] ?? '' }}</td>
            <td style="width:16px;text-align:center">{{ $aspectGlyphs[$type] ?? '' }}</td>
            <td style="width:100px" class="muted">{{ $aspectNames[$type] ?? $type }}</td>
            <td style="width:14px" class="accent">{{ $bodyGlyphs[$asp['body_b'] ?? 0] ?? '' }}</td>
            <td>{{ $bodyNames[$asp['body_b'] ?? 0] ?? '' }}</td>
            <td class="num muted" style="width:40px">{{ round(abs($asp['orb'] ?? 0), 1) }}°</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Singleton / Missing Element ─────────────────────────────────────── --}}
@if(count($singletons))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_element_pattern') }}</div>
    @foreach($singletons as $s)
    <div class="entry">
        <div class="entry-label">
            @if($s['type'] === 'singleton')
                Singleton: {{ $bodyNames[$s['planet']['body']] ?? '' }} ({{ $s['element'] }})
            @else
                Missing element: {{ $s['element'] }}
            @endif
        </div>
        @if($s['text'])<div class="entry-text">{{ $s['text'] }}</div>@endif
    </div>
    @endforeach
</div>
@endif

{{-- ── House Lords ───────────────────────────────────────────────────────── --}}
@if(count($houseLords))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_house_lords') }}</div>
    @foreach($houseLords as $hl)
    <div class="entry">
        <div class="entry-label">{{ $hl['label'] }}</div>
        <div class="entry-text">{{ $hl['text'] }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- ── House Lord Aspects ────────────────────────────────────────────────── --}}
@if(count($houseLordAspects))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_house_lord_aspects') }}</div>
    @foreach($houseLordAspects as $item)
    <div class="entry">
        <div class="entry-label">{{ $item['label'] }}</div>
        <div class="entry-sub">{{ $item['lord'] }} · {{ ucfirst(str_replace('_', '-', $item['aspect'])) }} · {{ $item['other'] }}</div>
        <div class="entry-text">{!! strip_tags($item['text'], '<strong><em>') !!}</div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Aspects to Angles ─────────────────────────────────────────────────── --}}
@if(count($angleAspectTexts))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_angle_aspects') }}</div>
    @foreach($angleAspectTexts as $item)
    <div class="entry">
        <div class="entry-label">
            {{ $item['planet'] }} {{ $aspectGlyphs[$item['aspect']] ?? '' }} {{ $aspectNames[$item['aspect']] ?? $item['aspect'] }} {{ $item['angle'] }}
        </div>
        <div class="entry-text">{!! strip_tags($item['text'], '<strong><em>') !!}</div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Natal Aspects (planet-planet) ───────────────────────────────────── --}}
@if(count($aspectTexts))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_aspects') }}</div>
    @foreach($aspectTexts as $item)
    @php
        $nA = \App\Models\PlanetaryPosition::BODY_NAMES[$item['bodyA']] ?? '';
        $nB = \App\Models\PlanetaryPosition::BODY_NAMES[$item['bodyB']] ?? '';
        $aLabel = $aspectNames[$item['aspect']] ?? ucwords(str_replace('_',' ',$item['aspect']));
    @endphp
    <div class="entry">
        <div class="entry-label">{{ $nA }} {{ $aspectGlyphs[$item['aspect']] ?? '' }} {{ $aLabel }} {{ $nB }}</div>
        @if($item['text'])
        <div class="entry-text">{!! strip_tags($item['text'], '<strong><em>') !!}</div>
        @endif
    </div>
    @endforeach
</div>
@endif
<div style="height:0;line-height:0;font-size:0;clear:both;page-break-after:avoid"></div>
@include('partials.pdf-footer')
</body>
</html>
