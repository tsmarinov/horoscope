<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Lunar Calendar — {{ $start->format('F Y') }}</title>
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
    .month-title   { font-size: 18pt; font-weight: 700; color: #1a1a2e; letter-spacing: 0.02em; margin-top: 4px; }
    .profile-sub   { font-size: 9pt; color: #666; margin-top: 3px; }

    .section { margin-bottom: 16px; }
    .section-title {
        font-size: 8.5pt; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        color: #6a329f;
        border-bottom: 1px solid #e0d8f0;
        padding-bottom: 3px; margin-bottom: 8px;
    }

    .lunation-row { font-size: 10pt; margin-bottom: 5px; color: #1a1a2e; }

    .day-row { display: table; width: 100%; border-bottom: 1px solid #e0d8f0; padding: 3px 0; }
    .day-row:last-child { border-bottom: none; }
    .day-date       { display: table-cell; width: 8.5em; font-weight: 500; white-space: nowrap; }
    .day-phase-svg  { display: table-cell; width: 2em; }
    .day-phase-name { display: table-cell; width: 11em; color: #666; }
    .day-sign       { display: table-cell; color: #6a329f; }
    .day-ld         { display: table-cell; text-align: right; color: #999; width: 3.5em; white-space: nowrap; }

    .sign-text { font-size: 9.5pt; color: #444; margin: 4px 0 6px 0; line-height: 1.5; }

    .lun-card { margin-bottom: 10px; page-break-inside: avoid; }
    .lun-card-title { font-size: 10pt; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
    .lun-card-meta  { font-size: 9pt; color: #666; margin-bottom: 4px; }
    .lun-card-text  { font-size: 9.5pt; color: #333; line-height: 1.55; }

    .divider { border: none; border-top: 1px solid #e0d8f0; margin: 12px 0; }
    .muted { color: #888; }
    .accent { color: #6a329f; }

    .footer { font-size: 8pt; color: #bbb; text-align: center; margin-top: 20px; border-top: 1px solid #e0d8f0; padding-top: 6px; }
</style>
</head>
<body>

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $weekdays   = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    // SVG moon phase from elongation (0–360°) — path-based (wkhtmltopdf-safe)
    $moonSvg = function(float $elongation, int $size = 14, string $darkColor = '#12102a', string $litColor = '#f0eaff'): string {
        $r    = round($size / 2 - 1, 1);
        $rad  = $elongation * M_PI / 180;
        $tx   = cos($rad);
        $rxT  = round(abs($tx) * $r, 2);
        $waxing = $elongation <= 180;
        $s1   = $waxing ? 1 : 0;
        $s2   = $waxing ? ($tx > 0 ? 0 : 1) : ($tx > 0 ? 1 : 0);
        $path = "M 0 -{$r} A {$r} {$r} 0 0 {$s1} 0 {$r} A {$rxT} {$r} 0 0 {$s2} 0 -{$r} Z";
        static $n = 0; $n++;
        $cid = 'plc' . $n;
        $h   = $size / 2;
        return "<svg viewBox='" . (-$h) . ' ' . (-$h) . ' ' . $size . ' ' . $size . "' "
             . "width='{$size}' height='{$size}' style='display:inline-block;vertical-align:middle;flex-shrink:0'>"
             . "<defs><clipPath id='{$cid}'><circle cx='0' cy='0' r='{$r}'/></clipPath></defs>"
             . "<circle cx='0' cy='0' r='{$r}' fill='{$darkColor}'/>"
             . "<path d='{$path}' fill='{$litColor}' clip-path='url(#{$cid})'/>"
             . "</svg>";
    };
@endphp

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
@include('partials.pdf-header', ['pageTitle' => __('ui.lunar.page_title')])

{{-- ── Profile + month title ─────────────────────────────────────────────── --}}
<div class="profile-block">
    <div class="profile-type">{{ __('ui.lunar.page_title') }}</div>
    <div class="month-title">{{ $start->format('F Y') }}</div>
    @if($profile)
    <div class="profile-sub">
        {{ $profile->name }}
        @if($profile->birth_date)<span style="margin-left:8px">{{ $profile->birth_date->format('F j, Y') }}</span>@endif
    </div>
    @endif
</div>

{{-- ── Calendar Grid ────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ $start->format('F Y') }}</div>
    @include('partials.lunar-calendar-grid', ['pdfMode' => true])
</div>

<hr class="divider">

{{-- ── Lunations This Month ─────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Lunations This Month</div>
    @foreach($days as $d => $day)
        @if($day['new_moon'] || $day['full_moon'])
        <div class="lunation-row">
            {!! $moonSvg($day['elongation'], 16) !!}
            {{ $day['new_moon'] ? 'New Moon' : 'Full Moon' }} in {{ $signNames[$day['sign_idx']] }} · {{ $day['carbon']->format('j M Y') }}
        </div>
        @endif
    @endforeach
</div>

<hr class="divider">

{{-- ── Day by Day ───────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Day by Day</div>
    @php $prevSign = -1; @endphp
    @foreach($days as $d => $day)
    <div class="day-row">
        <div class="day-date">{{ $day['carbon']->format('j M') }} {{ $weekdays[$day['carbon']->dayOfWeekIso - 1] }}</div>
        <div class="day-phase-svg">{!! $moonSvg($day['elongation'], 14) !!}</div>
        <div class="day-phase-name">{{ $day['phase_name'] }}</div>
        <div class="day-sign">{{ $signGlyphs[$day['sign_idx']] }} {{ $signNames[$day['sign_idx']] }}</div>
        <div class="day-ld">{{ $day['lunar_day'] }} ld</div>
    </div>
    @if($day['sign_idx'] !== $prevSign && isset($moonTexts[$day['sign_idx']]) && $moonTexts[$day['sign_idx']])
    <p class="sign-text">{{ $moonTexts[$day['sign_idx']] }}</p>
    @endif
    @php $prevSign = $day['sign_idx']; @endphp
    @endforeach
</div>

{{-- ── Your Lunations (personalized) ──────────────────────────────────── --}}
@if($profile && count($lunationCards) > 0)
<hr class="divider">
<div class="section">
    <div class="section-title">Your Lunations</div>
    @foreach($lunationCards as $card)
    <div class="lun-card">
        <div class="lun-card-title">
            {!! $moonSvg($card['type'] === 'New Moon' ? 0.0 : 180.0, 16) !!}
            {{ $card['type'] }} in {{ $card['sign_name'] }} · {{ $card['carbon']->format('j M') }}
        </div>
        <div class="lun-card-meta">
            {{ $card['house_ord'] }} house{{ $card['conjunctions'] ? ' · ☌ natal ' . implode(', ', $card['conjunctions']) : '' }}
        </div>
        @if($card['text'])
        <div class="lun-card-text">{{ $card['text'] }}</div>
        @endif
    </div>
    @endforeach
</div>
@endif

@include('partials.pdf-footer')

</body>
</html>
