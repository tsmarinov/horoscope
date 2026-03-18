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
            <h1 class="font-display profile-name">
                {{ $profile->name }}
            </h1>
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

    {{-- Bi-wheel placeholder --}}
    <div class="card card-wheel card-center">
        <div class="section-label section-label-sm">
            {{ $isToday ? "Today's Transits" : 'Transits · ' . $carbon->format('j M Y') }}
        </div>
        <svg viewBox="0 0 280 280" width="100%" class="transit-svg" aria-label="Transit bi-wheel placeholder">
            <circle cx="140" cy="140" r="138" fill="transparent" stroke="var(--theme-border)" stroke-width="1"/>
            <circle cx="140" cy="140" r="110" fill="none" stroke="var(--theme-border)" stroke-width="0.8"/>
            <circle cx="140" cy="140" r="80"  fill="none" stroke="#4a4a70" stroke-width="1.5"/>
            <circle cx="140" cy="140" r="44"  fill="transparent" stroke="var(--theme-border)" stroke-width="1"/>
            <text x="140" y="136" fill="#3a3a55" font-size="9" text-anchor="middle" font-family="sans-serif">natal</text>
            <text x="140" y="148" fill="#3a3a55" font-size="9" text-anchor="middle" font-family="sans-serif">+ transits</text>
        </svg>
        @if($transitSubtitle)
        <div class="transit-subtitle">{{ $transitSubtitle }}</div>
        @endif
    </div>

    {{-- Transit planet list --}}
    <div class="card card-mt">
        <div class="section-label">Planets Today</div>
        <div class="planet-grid">
            @foreach($dto->positions as $pos)
            <div class="planet-row">
                <span class="planet-glyph">{{ $bodyGlyphs[$pos->body] ?? '' }}</span>
                <span>{{ $pos->name }}</span>
                <span class="planet-in">in</span>
                <span>{{ $signGlyphs[$pos->signIndex] ?? '' }} {{ $pos->signName }}@if($pos->isRetrograde) <span class="rx-badge">Rx</span>@endif</span>
            </div>
            @endforeach
        </div>
    </div>

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
            {{ $phaseEmoji }} Moon in {{ $moonSignGlyph }} {{ $dto->moon->signName }}
            &nbsp;·&nbsp; Day {{ $dto->moon->lunarDay }} / 30
            &nbsp;·&nbsp; {{ $dto->moon->phaseName }}
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
            <span class="area-stars">{{ str_repeat('★', $area->rating) }}{{ str_repeat('☆', $area->maxRating - $area->rating) }}</span>
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

    {{-- Footer --}}
    <div class="back-link-row">
        <a href="{{ route('natal.show', $profile) }}" class="back-link">
            {{ __('ui.daily.back_to_natal') }}
        </a>
    </div>

@endsection

{{-- Scroll-to-top button --}}
<button id="stt" onclick="window.scrollTo({top:0,behavior:'smooth'})"
        title="Back to top"
        class="scroll-top">↑</button>
<script>
(function(){
    var btn = document.getElementById('stt');
    window.addEventListener('scroll', function(){
        btn.style.display = window.scrollY > 400 ? 'flex' : 'none';
    }, {passive: true});
})();
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
