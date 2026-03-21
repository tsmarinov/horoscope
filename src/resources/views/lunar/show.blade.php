@extends('layouts.app')

@section('title', 'Lunar Calendar — ' . $start->format('F Y'))
@section('main_class', 'page-wrap-narrow')
@section('nav_lunar', 'active')

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $weekdays   = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    // ── Moon photo positioning (tune to center the moon in the photo) ──────
    // scale: >1 zooms in (1.5 = fill height for a 3:2 landscape photo)
    // ox/oy: shift in SVG units relative to circle center (positive = right/down)
    $moonImg = ['src' => '/images/moon.jpg', 'scale' => 61/38, 'ar' => 42.1/61, 'ox' => 1, 'oy' => 0];

    // SVG moon phase from elongation (0–360°) — photo + SVG mask
    $moonSvg = function(float $elongation, int $size = 14, string $darkColor = '#0e0e18', string $litColor = '#ccc8e8') use ($moonImg): string {
        $r    = round($size / 2 - 1, 1);
        $rad  = $elongation * M_PI / 180;
        $tx   = cos($rad);
        $rxT  = round(abs($tx) * $r, 2);
        $waxing = $elongation <= 180;
        $s1   = $waxing ? 1 : 0;
        $s2   = $waxing ? ($tx > 0 ? 0 : 1) : ($tx > 0 ? 1 : 0);
        $path = "M 0 -{$r} A {$r} {$r} 0 0 {$s1} 0 {$r} A {$rxT} {$r} 0 0 {$s2} 0 -{$r} Z";

        static $n = 0; $n++;
        $mid = 'lmm' . $n;
        $dia = round($r * 2, 1);
        $h   = $size / 2;

        // Photo dimensions relative to circle
        $iw = round($dia * $moonImg['scale'], 1);
        $ih = round($iw * $moonImg['ar'], 1);
        $ix = round(-$iw / 2 + $moonImg['ox'], 1);
        $iy = round(-$ih / 2 + $moonImg['oy'], 1);

        $cid = 'lmc' . $n; // circle clipPath id
        return "<svg viewBox='" . (-$h) . ' ' . (-$h) . ' ' . $size . ' ' . $size . "' "
             . "width='{$size}' height='{$size}' style='display:inline-block;vertical-align:middle;flex-shrink:0'>"
             . "<defs>"
             . "<clipPath id='{$cid}'><circle cx='0' cy='0' r='{$r}'/></clipPath>"
             . "<mask id='{$mid}'>"
             . "<rect x='" . (-$h) . "' y='" . (-$h) . "' width='{$size}' height='{$size}' fill='black'/>"
             . "<path d='{$path}' fill='white'/>"
             . "</mask>"
             . "</defs>"
             . "<circle cx='0' cy='0' r='{$r}' fill='{$darkColor}'/>"
             . "<image href='{$moonImg['src']}' x='{$ix}' y='{$iy}' width='{$iw}' height='{$ih}' clip-path='url(#{$cid})' opacity='0.18'/>"
             . "<image href='{$moonImg['src']}' x='{$ix}' y='{$iy}' width='{$iw}' height='{$ih}' mask='url(#{$mid})'/>"
             . "</svg>";
    };
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
            <span class="lunar-nav-title">
                {{ $start->format('M Y') }}
                @if($start->format('Y-m') !== now()->format('Y-m'))
                    <a href="{{ route('lunar.show', [now()->year, now()->format('m')]) }}" class="lunar-nav-today">Today</a>
                @endif
            </span>
            <a href="{{ route('lunar.show', [$nextMonth->year, $nextMonth->format('m')]) }}" class="lunar-nav-btn">
                {{ $nextMonth->format('M Y') }} →
            </a>
        </div>
        <div class="pdf-row">
            <a href="{{ route('lunar.pdf', [$year, str_pad($month, 2, '0', STR_PAD_LEFT)]) }}"
               class="btn-pdf" target="_blank"
               title="{{ __('ui.lunar.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
        </div>
    </div>
</div>

{{-- ── B) Calendar grid card ────────────────────────────────────────────── --}}
<div class="card card-mt">

    @include('partials.lunar-calendar-grid')

</div>

<div class="pdf-row lunar-pdf-row">
    <a href="{{ route('lunar.pdf', [$year, str_pad($month, 2, '0', STR_PAD_LEFT)]) }}"
       class="btn-pdf" target="_blank"
       title="{{ __('ui.lunar.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
</div>

{{-- ── C) Personalized lunation cards ─────────────────────────────────── --}}
@if($profile && count($lunationCards) > 0)
<div class="card card-section card-mt">
    <div class="section-label">🔮 Your Lunations</div>
    @foreach($lunationCards as $card)
        <div class="{{ !$loop->first ? 'item-sep' : '' }}">
            <div class="lunation-title">{!! $moonSvg($card['type'] === 'New Moon' ? 0.0 : 180.0, 22) !!} {{ $card['type'] }} in {{ $card['sign_name'] }} · {{ $card['carbon']->format('j M') }}</div>
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
        <div class="lunar-day-row">
            <span class="lunar-day-date">{{ $day['carbon']->format('j M') }} {{ $weekdays[$day['carbon']->dayOfWeekIso - 1] }}</span>
            <span class="lunar-cell-phase">{!! $moonSvg($day['elongation'], 20) !!}</span>
            @if($day['new_moon'])
                <span class="lunar-day-event">New Moon</span>
            @elseif($day['full_moon'])
                <span class="lunar-day-event">Full Moon</span>
            @endif
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
                {!! $moonSvg($day['elongation'], 16) !!} {{ $day['new_moon'] ? 'New Moon' : 'Full Moon' }} in {{ $signNames[$day['sign_idx']] }} · {{ $day['carbon']->format('j F Y') }}
            </div>
        @endif
    @endforeach
</div>

<div class="pdf-row-end">
    <a href="{{ route('lunar.pdf', [$year, str_pad($month, 2, '0', STR_PAD_LEFT)]) }}"
       class="btn-pdf" target="_blank"
       title="{{ __('ui.lunar.download_pdf') }}"><svg width="9" height="11" viewBox="0 0 9 11" fill="none"><path d="M4.5 1v6M2 5.5l2.5 2.5L7 5.5M1 10h7" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</a>
</div>

{{-- ── F) Back link ─────────────────────────────────────────────────────── --}}
@auth
<div class="back-link-row">
    <a href="{{ route('daily.index') }}" class="back-link">← Horoscope</a>
</div>
@endauth

@endsection
