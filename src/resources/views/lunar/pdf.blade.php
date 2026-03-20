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
    .day-date       { display: table-cell; width: 5em; font-weight: 500; }
    .day-phase-svg  { display: table-cell; width: 2em; }
    .day-phase-name { display: table-cell; color: #666; }
    .day-sign       { display: table-cell; color: #6a329f; }
    .day-ld         { display: table-cell; text-align: right; color: #999; }

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

    // SVG moon phase from elongation (0–360°) with craters
    $moonSvg = function(float $elongation, int $size = 14, string $darkColor = '#0e0e18', string $litColor = '#ccc8e8'): string {
        $r    = round($size / 2 - 1, 1);
        $rad  = $elongation * M_PI / 180;
        $tx   = cos($rad);
        $rxT  = round(abs($tx) * $r, 2);
        $waxing = $elongation <= 180;
        $s1   = $waxing ? 1 : 0;
        $s2   = $waxing ? ($tx > 0 ? 0 : 1) : ($tx > 0 ? 1 : 0);
        $path = "M 0 -{$r} A {$r} {$r} 0 0 {$s1} 0 {$r} A {$rxT} {$r} 0 0 {$s2} 0 -{$r} Z";

        // Unique ID for clipPath (static counter per closure instance)
        static $n = 0; $n++;
        $id = 'lmc' . $n;

        // Blob helper: smooth closed Catmull-Rom path, pts = [[x,y]…] fractions of $r
        $blob = function(array $pts, float $r): string {
            $n = count($pts);
            $p = array_map(fn($q) => [round($q[0]*$r,1), round($q[1]*$r,1)], $pts);
            $d = "M {$p[0][0]} {$p[0][1]}";
            for ($i = 0; $i < $n; $i++) {
                $p0 = $p[($i-1+$n)%$n]; $p1 = $p[$i];
                $p2 = $p[($i+1)%$n];    $p3 = $p[($i+2)%$n];
                $c1x = round($p1[0]+($p2[0]-$p0[0])/6,1);
                $c1y = round($p1[1]+($p2[1]-$p0[1])/6,1);
                $c2x = round($p2[0]-($p3[0]-$p1[0])/6,1);
                $c2y = round($p2[1]-($p3[1]-$p1[1])/6,1);
                $d  .= " C {$c1x} {$c1y} {$c2x} {$c2y} {$p2[0]} {$p2[1]}";
            }
            return $d.' Z';
        };

        // Maria (seas) — irregular blob paths, fractions of $r
        $mariaBlobDefs = [
            // Mare Imbrium — large, upper-left
            ['op'=>0.11,'pts'=>[[-0.06,-0.38],[-0.20,-0.46],[-0.38,-0.44],[-0.54,-0.30],[-0.58,-0.12],[-0.50, 0.06],[-0.36, 0.10],[-0.18, 0.04],[-0.08,-0.12],[-0.04,-0.26]]],
            // Mare Tranquillitatis — center-right, irregular
            ['op'=>0.09,'pts'=>[[0.06, 0.06],[0.22,-0.02],[0.38, 0.06],[0.40, 0.24],[0.30, 0.38],[0.12, 0.40],[0.00, 0.30],[0.00, 0.14]]],
            // Mare Serenitatis — upper-right
            ['op'=>0.08,'pts'=>[[0.10,-0.36],[0.24,-0.44],[0.36,-0.36],[0.38,-0.20],[0.26,-0.12],[0.12,-0.16]]],
            // Mare Nubium — lower-left
            ['op'=>0.08,'pts'=>[[-0.22, 0.28],[-0.06, 0.26],[0.08, 0.36],[0.04, 0.54],[-0.14, 0.58],[-0.30, 0.50],[-0.30, 0.38]]],
            // Mare Crisium — far right, compact
            ['op'=>0.10,'pts'=>[[0.46,-0.02],[0.56,-0.07],[0.64, 0.04],[0.60, 0.16],[0.50, 0.18],[0.42, 0.10]]],
            // Mare Foecunditatis — lower-right
            ['op'=>0.07,'pts'=>[[0.30, 0.44],[0.46, 0.40],[0.50, 0.54],[0.38, 0.62],[0.22, 0.58],[0.22, 0.46]]],
        ];
        $cSvg = '';
        foreach ($mariaBlobDefs as $mare) {
            $d = $blob($mare['pts'], $r);
            $cSvg .= "<path d='{$d}' fill='{$darkColor}' opacity='{$mare['op']}'/>";
        }

        // Craters as [centerX, centerY, radius, opacity] fractions of $r
        $craterDefs = [
            [-0.30, -0.22, 0.17, 0.32],
            [ 0.18,  0.30, 0.14, 0.28],
            [-0.08,  0.47, 0.10, 0.22],
            [ 0.42, -0.16, 0.11, 0.30],
            [-0.46,  0.18, 0.09, 0.18],
            [ 0.10, -0.44, 0.12, 0.25],
            [-0.20,  0.10, 0.07, 0.14],
            [ 0.32,  0.12, 0.08, 0.20],
            [-0.12, -0.58, 0.07, 0.16],
            [ 0.55,  0.28, 0.06, 0.12],
            [-0.52, -0.30, 0.08, 0.18],
            [ 0.20, -0.10, 0.05, 0.10],
        ];
        foreach ($craterDefs as [$fx, $fy, $fr, $fo]) {
            $cx = round($fx * $r, 1);
            $cy = round($fy * $r, 1);
            $cr = max(0.7, round($fr * $r, 1));
            $cSvg .= "<circle cx='{$cx}' cy='{$cy}' r='{$cr}' fill='{$darkColor}' opacity='{$fo}'/>";
        }

        $h = $size / 2;
        return "<svg viewBox='" . (-$h) . ' ' . (-$h) . ' ' . $size . ' ' . $size . "' "
             . "width='{$size}' height='{$size}' style='display:inline-block;vertical-align:middle;flex-shrink:0'>"
             . "<defs><clipPath id='{$id}'><path d='{$path}'/></clipPath></defs>"
             . "<circle cx='0' cy='0' r='{$r}' fill='{$darkColor}'/>"
             . "<path d='{$path}' fill='{$litColor}'/>"
             . "<g clip-path='url(#{$id})'>{$cSvg}</g>"
             . "</svg>";
    };
@endphp

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="page-header-left">
        <div class="brand">Stellar ✦ Omens</div>
        <div class="brand-sub">Your stars, decoded</div>
        <div class="brand-url">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</div>
    </div>
    <div class="page-header-right">
        <div class="header-meta">{{ __('ui.lunar.page_title') }} · Generated {{ now()->format('M j, Y') }}</div>
    </div>
</div>

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

<div class="footer">Page generated {{ now()->format('M j, Y') }} · Stellar Omens</div>

</body>
</html>
