<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Daily Horoscope — {{ $profile->name }} — {{ $carbon->format('M j, Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10pt;
        color: #1a1a2e;
        line-height: 1.55;
        background: #fff;
    }

    .page-header {
        border-bottom: 2px solid #6a329f;
        padding-bottom: 8px;
        margin-bottom: 16px;
        display: table;
        width: 100%;
    }
    .page-header-left  { display: table-cell; vertical-align: middle; }
    .page-header-right { display: table-cell; vertical-align: middle; text-align: right; }
    .brand      { font-size: 18pt; font-weight: 800; color: #6a329f; letter-spacing: 0.03em; line-height: 1.1; }
    .brand-sub  { font-size: 10pt; color: #8a70b8; letter-spacing: 0.04em; font-style: italic; margin-top: 2px; }
    .brand-url  { font-size: 9pt; color: #a090c0; letter-spacing: 0.06em; margin-top: 2px; }
    .header-meta { font-size: 9pt; color: #bbb; }

    .profile-block { text-align: center; margin: 0 0 14px; }
    .profile-type  { font-size: 8pt; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #8a70b8; margin-bottom: 5px; }
    .profile-name  { font-size: 16pt; font-weight: 700; color: #1a1a2e; letter-spacing: 0.02em; }
    .profile-sub   { font-size: 9pt; color: #666; margin-top: 3px; }
    .profile-date  { font-size: 11pt; color: #6a329f; font-weight: 600; margin-top: 5px; }

    .section { margin-bottom: 16px; }
    .section-title {
        font-size: 8.5pt; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        color: #6a329f;
        border-bottom: 1px solid #e0d8f0;
        padding-bottom: 3px; margin-bottom: 8px;
    }
    .section-title-gold {
        font-size: 8.5pt; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        color: #b89000;
        border-bottom: 1px solid #f0e8b0;
        padding-bottom: 3px; margin-bottom: 8px;
    }

    .entry { margin-bottom: 10px; page-break-inside: avoid; }
    .chip  {
        font-size: 9.5pt; font-weight: 700; color: #6a329f;
        margin-bottom: 3px;
    }
    .prose { font-size: 10pt; color: #333; line-height: 1.55; }
    .prose strong { font-weight: 700; color: #1a1a2e; }
    .prose em     { font-style: italic; color: #6a329f; }

    .lunar-meta { font-size: 9.5pt; color: #555; margin-bottom: 6px; }

    .area-row {
        display: table; width: 100%;
        padding: 3px 0;
        border-bottom: 1px solid #f0ecf8;
        font-size: 9.5pt;
    }
    .area-row:last-child { border-bottom: none; }
    .area-name  { display: table-cell; }
    .area-stars { display: table-cell; text-align: right; color: #6a329f; }

    .day-meta { font-size: 9pt; color: #555; margin-top: 14px; }
    .day-meta-row { margin-bottom: 3px; }

    .muted { color: #888; }
    .accent { color: #6a329f; }

    .divider { border: none; border-top: 1px solid #e0d8f0; margin: 12px 0; }
</style>
</head>
<body>

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $bodyGlyphs = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'];
    $bodyNames  = ['Sun','Moon','Mercury','Venus','Mars','Jupiter','Saturn','Uranus','Neptune','Pluto','Chiron','North Node','Lilith'];
    $moonPhaseNames = [
        'new_moon' => 'New Moon', 'waxing_crescent' => 'Waxing Crescent',
        'first_quarter' => 'First Quarter', 'waxing_gibbous' => 'Waxing Gibbous',
        'full_moon' => 'Full Moon', 'waning_gibbous' => 'Waning Gibbous',
        'last_quarter' => 'Last Quarter', 'waning_crescent' => 'Waning Crescent',
    ];
    $moonSignGlyph = $signGlyphs[$dto->moon->signIndex] ?? '';
    $rulerGlyph    = $bodyGlyphs[$dto->dayRuler->body] ?? '';
    $areaEmojis = [
        'love' => '❤', 'home' => '⌂', 'creativity' => '✦',
        'spirituality' => '✦', 'health' => '♡', 'finance' => '$',
        'travel' => '✈', 'career' => '▲', 'personal_growth' => '✿',
        'communication' => '✉', 'contracts' => '✒',
    ];
@endphp

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
@include('partials.pdf-header', ['pageTitle' => __('ui.daily.page_title')])

{{-- ── Profile + date ───────────────────────────────────────────────────── --}}
<div class="profile-block">
    <div class="profile-type">{{ __('ui.daily.page_title') }}</div>
    <div class="profile-name">{{ $profile->name }}</div>
    <div class="profile-sub">
        @if($profile->birth_date)<span>{{ $profile->birth_date->format('F j, Y') }}</span>@endif
        @if($profile->birth_time)<span style="margin-left:8px">{{ substr($profile->birth_time, 0, 5) }}</span>@endif
        @if($profile->birthCity)<span style="margin-left:8px">{{ $profile->birthCity->name }}</span>@endif
    </div>
    <div class="profile-date">{{ $carbon->format('l, j F Y') }}</div>
</div>

{{-- ── Bi-wheel (natal inner + transits outer) ─────────────────────────── --}}
@if(!empty($wheelNatalPlanets))
<div style="text-align:center;margin-bottom:14px">
    @include('partials.wheel-biwheel', [
        'svgId'     => 'biwheel-pdf',
        'svgWidth'  => '680',
        'svgHeight' => '680',
        'svgClass'  => '',
        'pdfMode'   => true,
    ])
</div>
@endif

{{-- ── AI Overview ──────────────────────────────────────────────────────── --}}
@if($aiText)
<div class="section">
    <div class="section-title-gold">{{ __('ui.daily.ai_overview') }}</div>
    <div class="prose">{!! $aiText !!}</div>
</div>
<hr class="divider">
@endif

{{-- ── Key Transit Factors ──────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ __('ui.daily.key_transits') }}</div>
    @if(empty($transitTexts))
    <div class="muted">{{ __('ui.daily.no_transits') }}</div>
    @else
    @foreach($transitTexts as $item)
    <div class="entry">
        <div class="chip">{{ $item['chip'] }}</div>
        @if($item['text'])
        <div class="prose">{!! $item['text'] !!}</div>
        @endif
    </div>
    @endforeach
    @endif
</div>

{{-- ── Lunar Day ────────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ __('ui.daily.lunar_day') }}</div>
    <div class="lunar-meta">
        Moon in {{ $moonSignGlyph }} {{ $dto->moon->signName }}
        · Day {{ $dto->moon->lunarDay }} / 30
        · {{ $dto->moon->phaseName }}
    </div>
    @if($lunarText)
    <div class="prose">{!! $lunarText !!}</div>
    @endif
</div>

{{-- ── Tip of the Day ───────────────────────────────────────────────────── --}}
@if($tipText)
<div class="section">
    <div class="section-title-gold">{{ __('ui.daily.tip_label') }}</div>
    <div class="prose">{!! $tipText !!}</div>
</div>
@endif

{{-- ── Clothing & Jewelry ───────────────────────────────────────────────── --}}
@if($clothingText)
<div class="section">
    <div class="section-title">{{ __('ui.daily.clothing_label') }}</div>
    <div class="lunar-meta">
        {{ $dto->dayRuler->weekday }} · {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}
        @if($dto->natalVenusSign !== null)
        · Venus in {{ $signNames[$dto->natalVenusSign] ?? '' }}
        @endif
    </div>
    <div class="prose">{!! $clothingText !!}</div>
</div>
@endif

{{-- ── Areas of Life ────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ __('ui.daily.areas_of_life') }}</div>
    @foreach($dto->areasOfLife as $area)
    <div class="area-row">
        <div class="area-name">{{ $areaEmojis[$area->slug] ?? '·' }} {{ $area->name }}</div>
        <div class="area-stars">
            @if($area->rating === 0)
            <span class="muted">wait</span>
            @else
            {{ str_repeat('★', $area->rating) }}{{ str_repeat('☆', $area->maxRating - $area->rating) }}
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- ── Day meta ─────────────────────────────────────────────────────────── --}}
<div class="day-meta">
    <div class="day-meta-row">{{ $dto->dayRuler->weekday }} · {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}</div>
    <div class="day-meta-row">Color: {{ $dto->dayRuler->color }} · Gem: {{ $dto->dayRuler->gem }} · Number: {{ $dto->dayRuler->number }}</div>
</div>
@include('partials.pdf-footer')
</body>
</html>
