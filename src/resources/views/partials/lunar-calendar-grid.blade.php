{{--
    Lunar Calendar Grid Partial
    Usage (web):  @include('partials.lunar-calendar-grid')
    Usage (PDF):  @include('partials.lunar-calendar-grid', ['pdfMode' => true])

    Requires in scope: $days, $firstWeekday, $weekdays, $signGlyphs, $signNames, $moonSvg
--}}
@php $pdfMode = $pdfMode ?? false; @endphp

@if(!$pdfMode)

{{-- ── WEB: CSS grid ─────────────────────────────────────────────────────── --}}
<div class="lunar-weekdays">
    @foreach($weekdays as $wd)
        <span class="lunar-weekday">{{ $wd }}</span>
    @endforeach
</div>

<div class="lunar-grid">
    @for($i = 1; $i < $firstWeekday; $i++)
        <div class="lunar-cell lunar-cell-empty"></div>
    @endfor

    @foreach($days as $d => $day)
        <div class="lunar-cell {{ $day['is_today'] ? 'lunar-cell-today' : '' }}">
            <span class="lunar-day-num {{ $day['is_today'] ? 'lunar-day-today' : '' }}">{{ $d }}</span>
            @if($day['new_moon'] || $day['full_moon'])<span class="lunar-cell-star">*</span>@endif
            <span class="lunar-cell-phase">{!! $moonSvg($day['elongation'], 40) !!}</span>
            <span class="lunar-cell-phase-name">{{ $day['phase_name'] }}</span>
            <span class="lunar-cell-sign">{{ $signGlyphs[$day['sign_idx']] }} {{ substr($signNames[$day['sign_idx']], 0, 3) }}</span>
        </div>
    @endforeach
</div>

<div class="lunar-legend">
    <span style="color:var(--theme-muted);font-size:0.75rem">* New Moon / Full Moon</span>
</div>

@else

{{-- ── PDF: HTML table (wkhtmltopdf-safe) ──────────────────────────────── --}}
@php
    $pdfDaysArr  = array_values((array) $days);
    $pdfDaysKeys = array_keys((array) $days);
    $pdfEmpty    = $firstWeekday - 1;
    $pdfTotal    = $pdfEmpty + count($pdfDaysArr);
    $pdfRows     = (int) ceil($pdfTotal / 7);
@endphp
<table style="width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:4px">
    <thead>
        <tr>
            @foreach($weekdays as $wd)
            <th style="font-size:8pt;font-weight:700;color:#6a329f;text-align:center;padding:3px 2px;border-bottom:2px solid #6a329f">{{ $wd }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @for($pdfRow = 0; $pdfRow < $pdfRows; $pdfRow++)
        <tr>
            @for($pdfCol = 0; $pdfCol < 7; $pdfCol++)
            @php $ci = $pdfRow * 7 + $pdfCol; @endphp
            @if($ci < $pdfEmpty || $ci >= $pdfEmpty + count($pdfDaysArr))
            <td style="border:1px solid #e0d8f0;padding:2px"></td>
            @else
            @php $di = $ci - $pdfEmpty; $d = $pdfDaysKeys[$di]; $day = $pdfDaysArr[$di]; @endphp
            <td style="border:1px solid #e0d8f0;padding:3px 2px;text-align:center;vertical-align:top">
                <div style="display:table;width:100%;margin-bottom:2px">
                    <span style="display:table-cell;font-size:9pt;font-weight:700;color:#1a1a2e;text-align:left">{{ $d }}</span>
                    <span style="display:table-cell;font-size:7.5pt;color:#6a329f;text-align:right">@if($day['new_moon'] || $day['full_moon'])*@endif</span>
                </div>
                <div style="margin:2px 0;line-height:1">{!! $moonSvg($day['elongation'], 52) !!}</div>
                <div style="font-size:6.5pt;color:#555;line-height:1.3">{{ $day['phase_name'] }}</div>
                <div style="font-size:7.5pt;color:#6a329f;line-height:1.3;font-weight:600">{{ $signGlyphs[$day['sign_idx']] }} {{ substr($signNames[$day['sign_idx']], 0, 3) }}</div>
            </td>
            @endif
            @endfor
        </tr>
        @endfor
    </tbody>
</table>
<div style="font-size:7.5pt;color:#888;margin-bottom:10px">* New Moon / Full Moon</div>

@endif
