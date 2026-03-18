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
    <div style="padding:0.5rem 0 1.5rem"
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
        <div style="position:relative">
        <button @click="open = !open"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:0.45rem 0.75rem;border-radius:6px;border:1px solid var(--theme-border);background:var(--theme-card);color:var(--theme-text);font-size:0.85rem;cursor:pointer;font-family:inherit;text-align:left">
            <span>
                <span x-text="current.name" style="font-weight:500"></span>
                <span x-text="current.sub ? ' · ' + current.sub : ''" style="color:var(--theme-muted)"></span>
            </span>
            <span style="color:var(--theme-muted);font-size:0.7rem" x-text="open ? '▲' : '▼'"></span>
        </button>
        <div x-show="open" x-cloak
             style="position:absolute;z-index:200;left:0;right:0;margin-top:4px;background:var(--theme-card);border:1px solid var(--theme-border);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.15);overflow:hidden">
            <div style="padding:0.5rem 0.75rem">
                <input x-ref="search" x-model="search" @keydown.escape="open = false"
                       placeholder="Search profiles…"
                       style="width:100%;padding:0.4rem 0.65rem;border:1px solid var(--theme-border);border-radius:5px;background:var(--theme-bg);color:var(--theme-text);font-size:0.82rem;font-family:inherit;box-sizing:border-box"
                       x-init="$watch('open', v => v && $nextTick(() => $refs.search.focus()))">
            </div>
            <div style="max-height:220px;overflow-y:auto">
                <template x-for="p in filtered" :key="p.id">
                    <a :href="p.url"
                       :style="`display:flex;align-items:center;justify-content:space-between;padding:0.45rem 0.75rem;text-decoration:none;font-size:0.85rem;background:${p.active ? 'var(--theme-raised)' : 'transparent'}`"
                       @mouseover="$el.style.background='var(--theme-raised)'"
                       @mouseout="$el.style.background=p.active ? 'var(--theme-raised)' : 'transparent'">
                        <span>
                            <span x-text="p.name" :style="p.active ? 'font-weight:600;color:#6a329f' : 'color:var(--theme-text)'"></span>
                            <span x-text="p.sub ? ' · ' + p.sub : ''" style="color:var(--theme-muted)"></span>
                        </span>
                        <span x-show="p.active" style="color:#6a329f;font-size:0.75rem">✓</span>
                    </a>
                </template>
                <div x-show="filtered.length === 0"
                     style="padding:0.75rem;text-align:center;font-size:0.82rem;color:var(--theme-muted)">
                    No profiles found
                </div>
            </div>
        </div>
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div style="padding:0 1rem 1rem">
        <div style="text-align:center">
            <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.2rem;font-weight:600">
                {{ $profile->name }}
            </h1>
            <div style="font-size:0.82rem;color:var(--theme-muted);margin-bottom:0.9rem">
                {{ $carbon->format('l, j F Y') }}
            </div>

            {{-- Date navigation --}}
            <div style="display:flex;align-items:center;justify-content:center;gap:1.25rem;margin-bottom:0.9rem">
                <a href="{{ route('daily.show', [$profile, $prevDate]) }}"
                   style="font-size:0.8rem;color:var(--theme-muted);text-decoration:none;white-space:nowrap"
                   onmouseover="this.style.color='#6a329f'" onmouseout="this.style.color='var(--theme-muted)'">
                    ← {{ $carbon->copy()->subDay()->format('j M') }}
                </a>
                <span style="font-size:0.85rem;font-weight:600;color:var(--theme-text)">
                    {{ $isToday ? __('ui.daily.today') : $carbon->format('j M') }}
                </span>
                <a href="{{ route('daily.show', [$profile, $nextDate]) }}"
                   style="font-size:0.8rem;color:var(--theme-muted);text-decoration:none;white-space:nowrap"
                   onmouseover="this.style.color='#6a329f'" onmouseout="this.style.color='var(--theme-muted)'">
                    {{ $carbon->copy()->addDay()->format('j M') }} →
                </a>
            </div>

            {{-- Period tabs --}}
            <div style="display:flex;justify-content:center;gap:0.3rem">
                <span style="padding:0.28rem 0.85rem;border-radius:20px;font-size:0.78rem;font-family:sans-serif;background:#6a329f;color:#fff;font-weight:600">Day</span>
                <a href="#" style="padding:0.28rem 0.85rem;border-radius:20px;font-size:0.78rem;font-family:sans-serif;color:var(--theme-muted);text-decoration:none;border:1px solid var(--theme-border)">Week</a>
                <a href="#" style="padding:0.28rem 0.85rem;border-radius:20px;font-size:0.78rem;font-family:sans-serif;color:var(--theme-muted);text-decoration:none;border:1px solid var(--theme-border)">Month</a>
                <a href="#" style="padding:0.28rem 0.85rem;border-radius:20px;font-size:0.78rem;font-family:sans-serif;color:var(--theme-muted);text-decoration:none;border:1px solid var(--theme-border)">Year</a>
            </div>
        </div>
    </div>

    {{-- Bi-wheel placeholder --}}
    <div class="card" style="padding:0.75rem;overflow:hidden;margin-bottom:0.75rem;text-align:center">
        <div class="section-label" style="margin-bottom:0.5rem">
            {{ $isToday ? "Today's Transits" : 'Transits · ' . $carbon->format('j M Y') }}
        </div>
        <svg viewBox="0 0 280 280" width="100%" style="max-width:260px;display:block;margin:0 auto" aria-label="Transit bi-wheel placeholder">
            <circle cx="140" cy="140" r="138" fill="transparent" stroke="var(--theme-border)" stroke-width="1"/>
            <circle cx="140" cy="140" r="110" fill="none" stroke="var(--theme-border)" stroke-width="0.8"/>
            <circle cx="140" cy="140" r="80"  fill="none" stroke="#4a4a70" stroke-width="1.5"/>
            <circle cx="140" cy="140" r="44"  fill="transparent" stroke="var(--theme-border)" stroke-width="1"/>
            <text x="140" y="136" fill="#3a3a55" font-size="9" text-anchor="middle" font-family="sans-serif">natal</text>
            <text x="140" y="148" fill="#3a3a55" font-size="9" text-anchor="middle" font-family="sans-serif">+ transits</text>
        </svg>
        @if($transitSubtitle)
        <div style="font-size:0.78rem;color:var(--theme-muted);font-family:sans-serif;margin-top:0.5rem">
            {{ $transitSubtitle }}
        </div>
        @endif
    </div>

    {{-- Transit planet list --}}
    <div class="card" style="margin-top:0.75rem">
        <div class="section-label" style="margin-bottom:0.65rem">Planets Today</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.3rem 0.75rem">
            @foreach($dto->positions as $pos)
            <div style="font-size:0.82rem;color:var(--theme-muted);display:flex;align-items:center;gap:0.3rem">
                <span style="color:#6a329f;width:1rem;text-align:center">{{ $bodyGlyphs[$pos->body] ?? '' }}</span>
                <span style="color:var(--theme-text)">{{ $pos->name }}</span>
                <span>{{ $signGlyphs[$pos->signIndex] ?? '' }} {{ $pos->signName }}</span>
                @if($pos->isRetrograde)<span style="color:#e87070;font-size:0.75rem">Rx</span>@endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- AI Synthesis + Short Version toggle --}}
    <div style="margin-top:0.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem">
        @include('partials.premium-button', ['context' => 'daily', 'generated' => ($aiText !== null)])

        {{-- Short Version toggle --}}
        <label style="display:inline-flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.82rem;color:var(--theme-muted);user-select:none">
            <span style="position:relative;display:inline-block;width:36px;height:20px">
                <input id="short-ver-chk" type="checkbox" onchange="applyState(this.checked)"
                       style="opacity:0;width:0;height:0;position:absolute">
                <span id="short-ver-track"
                      style="position:absolute;inset:0;border-radius:20px;background:#a09ab8;transition:background 0.2s"></span>
                <span id="short-ver-thumb"
                      style="position:absolute;left:3px;top:3px;width:14px;height:14px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform 0.2s"></span>
            </span>
            Short Version
        </label>
    </div>

    {{-- AI synthesis card --}}
    <div id="synthesis-card" class="card"
         style="margin-top:0.75rem;padding:0.75rem 1rem;background:rgba(212,175,55,0.08);border-color:rgba(212,175,55,0.25){{ $aiText === null ? ';display:none' : '' }}">
        <div class="section-label" style="margin-bottom:0.75rem;color:#c9a84c">{{ __('ui.daily.ai_overview') }}</div>
        <div id="synthesis-text" class="prose">
            {!! $aiText ?? '' !!}
        </div>
    </div>

    {{-- KEY TRANSIT FACTORS --}}
    @foreach([['full', $transitTexts], ['short', $transitTextsShort]] as [$ver, $items])
    <div class="card" data-ver="{{ $ver }}"
         style="margin-top:0.75rem{{ $ver === 'short' ? ';display:none' : '' }}">
        <div class="section-label" style="margin-bottom:0.75rem">{{ __('ui.daily.key_transits') }}</div>

        @if(empty($items))
        <div style="font-size:0.85rem;color:var(--theme-muted);text-align:center;padding:0.25rem 0">
            {{ __('ui.daily.no_transits') }}
        </div>
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
    <div class="card" data-ver="{{ $ver }}"
         style="margin-top:0.75rem;border-left:3px solid #4a4a90{{ $ver === 'short' ? ';display:none' : '' }}">
        <div class="section-label" style="margin-bottom:0.5rem">{{ __('ui.daily.lunar_day') }}</div>
        <div style="font-size:0.82rem;color:var(--theme-muted);font-family:sans-serif;margin-bottom:{{ $text ? '0.6rem' : '0' }}">
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
    <div class="card" data-ver="{{ $ver }}"
         style="margin-top:0.75rem;border-left:3px solid #c9a84c{{ $ver === 'short' ? ';display:none' : '' }}">
        <div class="label-gold">{{ __('ui.daily.tip_label') }}</div>
        <div class="prose">{!! $text !!}</div>
    </div>
    @endif
    @endforeach

    {{-- Clothing & Jewelry --}}
    @foreach([['full', $clothingText], ['short', $clothingTextShort]] as [$ver, $text])
    @if($text)
    <div class="card" data-ver="{{ $ver }}"
         style="margin-top:0.75rem;border-left:3px solid #8a60a0{{ $ver === 'short' ? ';display:none' : '' }}">
        <div class="label-purple">{{ __('ui.daily.clothing_label') }}</div>
        <div style="font-size:0.82rem;color:var(--theme-muted);font-family:sans-serif;margin-bottom:0.5rem">
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
    <div class="card" style="margin-top:0.75rem;padding:0;overflow:hidden">
        <div style="padding:0.75rem 1rem 0.5rem">
            <div class="section-label">{{ __('ui.areas.title') }}</div>
        </div>
        @foreach($dto->areasOfLife as $area)
        @php
            $emoji  = $areaEmojis[$area->slug] ?? '';
            $isWait = $area->rating === 0;
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 1rem;border-top:1px solid var(--theme-border);font-size:0.88rem{{ $isWait ? ';opacity:0.55' : '' }}">
            <span>{{ $emoji }} {{ $area->name }}</span>
            @if($isWait)
            <span style="font-family:sans-serif;font-size:0.78rem;color:var(--theme-muted);font-style:italic">{{ __('ui.rating_wait') }}</span>
            @else
            <span style="color:#c9a84c;font-size:0.82rem;letter-spacing:0.05em">{{ str_repeat('★', $area->rating) }}{{ str_repeat('☆', $area->maxRating - $area->rating) }}</span>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Day meta --}}
    <div class="card" style="margin-top:0.75rem;font-size:0.85rem;font-family:sans-serif;color:var(--theme-muted);line-height:1.9">
        <div>🗓 <strong style="color:#c9a84c">{{ $dto->dayRuler->weekday }}</strong>
            &nbsp;·&nbsp; {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}
        </div>
        <div>🎨 {{ $dto->dayRuler->color }}
            &nbsp;·&nbsp; 💎 {{ $dto->dayRuler->gem }}
            &nbsp;·&nbsp; 🔢 {{ $dto->dayRuler->number }}
        </div>
    </div>

    {{-- Footer --}}
    <div style="padding:0.5rem 0 1.5rem;text-align:center">
        <a href="{{ route('natal.show', $profile) }}"
           style="font-size:0.8rem;color:var(--theme-muted);text-decoration:underline">
            {{ __('ui.daily.back_to_natal') }}
        </a>
    </div>

@endsection

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
