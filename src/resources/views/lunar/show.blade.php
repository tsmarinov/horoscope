@extends('layouts.app')

@section('title', '🌙 Lunar Calendar — ' . $start->format('F Y'))
@section('main_class', 'page-wrap-narrow')
@section('nav_lunar', 'active')

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $weekdays   = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
@endphp

@section('content')

{{-- ── A) Page header ──────────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="lunar-header">
        <div class="section-label">🌙 Lunar Calendar</div>
        <div class="lunar-title-month">{{ $start->format('F Y') }}</div>
        <div class="lunar-nav">
            <a href="{{ route('lunar.show', [$prevMonth->year, $prevMonth->format('m')]) }}" class="lunar-nav-btn">
                ← {{ $prevMonth->format('M Y') }}
            </a>
            <span class="lunar-nav-title">{{ $start->format('M Y') }}</span>
            <a href="{{ route('lunar.show', [$nextMonth->year, $nextMonth->format('m')]) }}" class="lunar-nav-btn">
                {{ $nextMonth->format('M Y') }} →
            </a>
        </div>
    </div>
</div>

{{-- ── B) Calendar grid card ────────────────────────────────────────────── --}}
<div class="card card-mt">

    {{-- Weekday headers --}}
    <div class="lunar-weekdays">
        @foreach($weekdays as $wd)
            <span class="lunar-weekday">{{ $wd }}</span>
        @endforeach
    </div>

    {{-- Calendar grid --}}
    <div class="lunar-grid">

        {{-- Empty leading cells --}}
        @for($i = 1; $i < $firstWeekday; $i++)
            <div class="lunar-cell lunar-cell-empty"></div>
        @endfor

        {{-- Day cells --}}
        @foreach($days as $d => $day)
            <div class="lunar-cell {{ $day['is_today'] ? 'lunar-cell-today' : '' }} {{ $day['new_moon'] ? 'lunar-cell-new' : '' }} {{ $day['full_moon'] ? 'lunar-cell-full' : '' }}">
                <span class="lunar-day-num {{ $day['is_today'] ? 'lunar-day-today' : '' }}">{{ $d }}</span>
                <span class="lunar-cell-phase">{{ $day['phase'] }}</span>
                <span class="lunar-cell-sign">{{ $signGlyphs[$day['sign_idx']] }}</span>
            </div>
        @endforeach

    </div>

    {{-- Legend --}}
    <div class="lunar-legend">
        <span>
            <span class="lunar-legend-swatch lunar-swatch-new"></span>New Moon
        </span>
        <span>
            <span class="lunar-legend-swatch lunar-swatch-full"></span>Full Moon
        </span>
        <span>
            <span class="lunar-legend-swatch lunar-swatch-today"></span>Today
        </span>
    </div>

</div>

{{-- ── C) Personalized lunation cards ─────────────────────────────────── --}}
@if($profile && count($lunationCards) > 0)
<div class="card card-section card-mt">
    <div class="section-label">🔮 Your Lunations</div>
    @foreach($lunationCards as $card)
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="lunation-title">{{ $card['icon'] }} {{ $card['type'] }} in {{ $card['sign_name'] }} · {{ $card['carbon']->format('j M') }}</div>
            <div class="lunation-meta">{{ $card['house_ord'] }} house{{ $card['conjunctions'] ? ' · ☌ natal ' . implode(', ', $card['conjunctions']) : '' }}</div>
            @if($card['text'])
                <p class="prose">{{ $card['text'] }}</p>
            @else
                <p class="prose empty-msg">[text pending]</p>
            @endif
        </div>
    @endforeach
</div>
@endif

{{-- ── D) Day-by-day list ───────────────────────────────────────────────── --}}
<div class="card card-mt">
    <div class="section-label">Day by Day</div>
    @php $prevSign = -1; @endphp
    @foreach($days as $d => $day)
        @if($day['new_moon'])
            <div class="lunar-list-marker">🌑 New Moon</div>
        @elseif($day['full_moon'])
            <div class="lunar-list-marker">🌕 Full Moon</div>
        @endif
        <div class="lunar-day-row">
            <span class="lunar-day-date">{{ $day['carbon']->format('j M') }} {{ $weekdays[$day['carbon']->dayOfWeekIso - 1] }}</span>
            <span class="lunar-cell-phase">{{ $day['phase'] }}</span>
            <span class="lunar-day-sign">{{ $signGlyphs[$day['sign_idx']] }} {{ $signNames[$day['sign_idx']] }}</span>
            <span class="lunar-day-lday">{{ $day['lunar_day'] }} ld</span>
        </div>
        @if($day['sign_idx'] !== $prevSign && isset($moonTexts[$day['sign_idx']]) && $moonTexts[$day['sign_idx']])
            <p class="prose">{{ $moonTexts[$day['sign_idx']] }}</p>
        @endif
        @php $prevSign = $day['sign_idx']; @endphp
    @endforeach
</div>

{{-- ── E) Lunations this month ─────────────────────────────────────────── --}}
<div class="card card-mt">
    <div class="section-label">Lunations This Month</div>
    @foreach($days as $d => $day)
        @if($day['new_moon'] || $day['full_moon'])
            <div class="lunar-lunation-row">
                {{ $day['new_moon'] ? '🌑 New Moon' : '🌕 Full Moon' }} in {{ $signNames[$day['sign_idx']] }} · {{ $day['carbon']->format('j F Y') }}
            </div>
        @endif
    @endforeach
</div>

{{-- ── F) Back link ─────────────────────────────────────────────────────── --}}
<div class="back-link-row">
    @auth
        <a href="{{ route('daily.index') }}" class="back-link">← Horoscope</a>
    @else
        <a href="/" class="back-link">← Home</a>
    @endauth
</div>

@endsection
