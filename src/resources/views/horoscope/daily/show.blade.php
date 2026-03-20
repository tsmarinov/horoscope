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

    $moonImg = ['src' => '/images/moon.jpg', 'scale' => 61/38, 'ar' => 42.1/61, 'ox' => 1, 'oy' => 0];
    $moonSvg = function(float $elongation, int $size = 22, string $darkColor = '#0e0e18', string $litColor = '#ccc8e8') use ($moonImg): string {
        $r    = round($size / 2 - 1, 1);
        $rad  = $elongation * M_PI / 180;
        $tx   = cos($rad);
        $rxT  = round(abs($tx) * $r, 2);
        $waxing = $elongation <= 180;
        $s1   = $waxing ? 1 : 0;
        $s2   = $waxing ? ($tx > 0 ? 0 : 1) : ($tx > 0 ? 1 : 0);
        $path = "M 0 -{$r} A {$r} {$r} 0 0 {$s1} 0 {$r} A {$rxT} {$r} 0 0 {$s2} 0 -{$r} Z";
        static $n = 0; $n++;
        $mid = 'dlm' . $n; $cid = 'dlc' . $n;
        $h   = $size / 2; $dia = round($r * 2, 1);
        $iw  = round($dia * $moonImg['scale'], 1);
        $ih  = round($iw * $moonImg['ar'], 1);
        $ix  = round(-$iw / 2 + $moonImg['ox'], 1);
        $iy  = round(-$ih / 2 + $moonImg['oy'], 1);
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
        @include('partials.wheel-biwheel', ['svgId' => 'biwheel', 'svgClass' => 'wheel-svg'])
        @if($transitSubtitle)
        <div class="transit-subtitle">{{ $transitSubtitle }}</div>
        @endif
        <div style="text-align:center;margin-top:0.4rem">
            <a href="{{ route('natal.show', $profile) }}" style="font-size:0.78rem;color:var(--theme-muted);text-decoration:none;opacity:0.7" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">View Natal Chart →</a>
        </div>
    </div>

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
            <a href="{{ route('lunar.show', [$carbon->year, $carbon->format('m')]) }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem">
                {!! $moonSvg($dto->moon->elongation, 22) !!} Moon in {{ $moonSignGlyph }} {{ $dto->moon->signName }}
                &nbsp;·&nbsp; Day {{ $dto->moon->lunarDay }} / 30
                &nbsp;·&nbsp; {{ $dto->moon->phaseName }}
            </a>
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
            <span class="area-stars">{{ str_repeat('★', $area->rating) }}<span style="opacity:0.25">{{ str_repeat('☆', $area->maxRating - $area->rating) }}</span></span>
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
