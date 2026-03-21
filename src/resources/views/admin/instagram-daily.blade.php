@extends('layouts.app')

@section('title', 'Instagram Generator — ' . $date->format('Y-m-d'))

@section('content')
<style>
/* ── Admin page chrome ─────────────────────────────────────────────────── */
.ig-admin { max-width: 920px; margin: 0 auto; padding: 2rem 1rem 4rem; }

.ig-admin h1 {
    font-family: 'Cinzel', serif;
    font-size: 1.4rem;
    color: var(--theme-text);
    margin-bottom: 1.25rem;
}

.ig-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.ig-ctrl-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
    text-decoration: none;
    border: none;
    outline: none;
}
.ig-ctrl-btn:hover { opacity: 0.8; }
.ig-ctrl-btn-nav  { background: var(--theme-raised); color: var(--theme-text); }
.ig-ctrl-btn-date { background: transparent; color: var(--theme-muted); font-weight: 400; font-size: 0.95rem; }
.ig-ctrl-btn-dl   { background: #6a329f; color: #fff; margin-left: auto; }

.ig-grid { display: flex; flex-direction: column; gap: 3rem; }

.ig-preview-wrap {
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 8px 48px rgba(106,50,159,0.18);
    overflow: hidden;
}

.ig-slide-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.75rem;
}

.ig-slide-label { font-size: 0.9rem; color: var(--theme-muted); }

.ig-dl-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 1.1rem;
    background: var(--theme-raised);
    border: 1px solid var(--theme-border);
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--theme-text);
    cursor: pointer;
    transition: background 0.15s;
}
.ig-dl-btn:hover { background: #6a329f; color: #fff; border-color: #6a329f; }
.ig-dl-btn.loading { opacity: 0.6; pointer-events: none; }

/* ── Instagram slide: 1080×1080, light theme ──────────────────────────── */
.ig-slide {
    width: 1080px;
    height: 1080px;
    position: relative;
    overflow: hidden;
    background: #f5f0fc;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

/* SVG icon colours inside slide — light mode purple palette */
.ig-sign-icon circle                         { fill: #8060b8; }
.ig-sign-icon g[fill]                        { fill: rgba(106,50,159,0.15); }
.ig-sign-icon g[stroke]                      { stroke: #9060c0; }
.ig-sign-icon .zc-bright circle:nth-child(1) { fill: rgba(106,50,159,0.2); }
.ig-sign-icon .zc-bright circle:nth-child(2) { fill: #6a329f; }
.ig-sign-icon .zc-bright circle:last-child   { fill: #9860d8; }
</style>

<div class="ig-admin">
    <h1>Instagram Daily Horoscope</h1>

    <div class="ig-controls">
        <a href="/admin/instagram/daily/{{ $date->copy()->subDay()->toDateString() }}" class="ig-ctrl-btn ig-ctrl-btn-nav">← Prev</a>
        <span class="ig-ctrl-btn ig-ctrl-btn-date">{{ $date->format('l, F j, Y') }}</span>
        <a href="/admin/instagram/daily/{{ $date->copy()->addDay()->toDateString() }}" class="ig-ctrl-btn ig-ctrl-btn-nav">Next →</a>
        @if(!$date->isToday())
        <a href="/admin/instagram/daily" class="ig-ctrl-btn ig-ctrl-btn-nav">Today</a>
        @endif
        <button onclick="downloadAll()" class="ig-ctrl-btn ig-ctrl-btn-dl" id="dl-all-btn">↓ Download All 6</button>
    </div>

    <div class="ig-grid">
        @foreach($slides as $i => $pair)

        <div>
            <div class="ig-preview-wrap">
                <div class="ig-slide" id="slide-{{ $i + 1 }}">

                    {{-- Slide content --}}
                    <div style="position:relative;z-index:1;width:100%;height:100%;padding:25px;box-sizing:border-box;display:flex;flex-direction:column;">

                        {{-- Header --}}
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
                            <div style="font-family:'Cinzel',serif;font-size:30px;color:#6a329f;letter-spacing:0.09em;font-weight:600">STELLAR ✦ OMENS</div>
                            <div style="font-size:25px;color:#9a88b8;letter-spacing:0.03em">{{ $date->format('F j, Y') }}</div>
                        </div>

                        {{-- Divider --}}
                        <div style="height:1px;background:linear-gradient(90deg,rgba(106,50,159,0.5) 0%,rgba(106,50,159,0.15) 60%,rgba(106,50,159,0) 100%);margin-bottom:30px;flex-shrink:0"></div>

                        {{-- Two sign cards --}}
                        <div style="display:flex;gap:28px;flex:1;min-height:0">

                            @foreach($pair as $slug => $sign)
                            @php
                                $elementBg = match($sign['element']) {
                                    'Fire'  => 'rgba(180,50,30,0.80)',
                                    'Earth' => 'rgba(60,100,40,0.80)',
                                    'Air'   => 'rgba(40,90,140,0.80)',
                                    'Water' => 'rgba(30,55,130,0.80)',
                                    default => 'rgba(80,50,120,0.80)',
                                };
                                $elementColor = match($sign['element']) {
                                    'Fire'  => '#b43218',
                                    'Earth' => '#3c6428',
                                    'Air'   => '#28598c',
                                    'Water' => '#1e3782',
                                    default => '#50327a',
                                };
                                $horoText = strip_tags($horoscopes[$slug] ?? '', '<strong><em>');
                            @endphp

                            <div style="flex:1;background:#ffffff;border:1px solid rgba(106,50,159,0.18);border-left:3px solid #6a329f;border-radius:20px;padding:15px;display:flex;flex-direction:column;box-shadow:0 4px 24px rgba(106,50,159,0.08);">

                                {{-- Header row: name+meta left, icon right --}}
                                <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px">

                                    {{-- Left: name + dates + element --}}
                                    <div style="flex:1">
                                        <div style="font-family:'Cinzel',serif;font-size:40px;font-weight:700;color:#1a0830;letter-spacing:0.05em;margin-bottom:10px">{{ ucfirst($slug) }}</div>
                                        <div style="color:#9a88b8;font-size:24px;margin-bottom:10px">{{ $sign['dates'] }}</div>
                                        <div style="display:flex;align-items:center;gap:12px">
                                            <span style="font-size:40px;color:{{ $elementColor }};line-height:1;font-variant-emoji:text">{{ $sign['glyph'] }}&#xFE0E;</span>
                                            <span style="background:{{ $elementBg }};color:#fff;font-size:18px;font-weight:700;padding:4px 16px;border-radius:20px;letter-spacing:0.04em">{{ $sign['element'] }}</span>
                                        </div>
                                    </div>

                                    {{-- Right: constellation icon --}}
                                    <div class="ig-sign-icon" style="flex-shrink:0;background:rgba(106,50,159,0.15);border:1.5px solid rgba(106,50,159,0.35);border-radius:14px;padding:8px">
                                        @include('partials.zodiac-picture', ['sign' => $slug, 'size' => 130])
                                    </div>

                                </div>

                                {{-- Divider --}}
                                <div style="height:1px;background:rgba(106,50,159,0.12);margin-bottom:22px;flex-shrink:0"></div>

                                {{-- Horoscope text --}}
                                <div style="font-size:23px;color:#3a2a50;line-height:1.70;overflow:hidden;flex:1">
                                    {!! $horoText !!}
                                </div>

                            </div>
                            @endforeach

                        </div>

                        {{-- Footer --}}
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;flex-shrink:0">
                            <div style="color:#c0b0d8;font-size:16px;letter-spacing:0.04em">stellaromens.com</div>
                            <div style="color:#c0b0d8;font-size:16px">{{ $i + 1 }} / 6</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Controls below slide --}}
            <div class="ig-slide-footer">
                <span class="ig-slide-label">
                    Slide {{ $i + 1 }} / 6 &nbsp;·&nbsp;
                    @foreach($pair as $slug => $sign){{ $sign['glyph'] }} {{ ucfirst($slug) }}{{ !$loop->last ? '  &  ' : '' }}@endforeach
                </span>
                <button class="ig-dl-btn" onclick="downloadSlide({{ $i + 1 }}, this)">↓ Download PNG</button>
            </div>
        </div>

        @endforeach
    </div>
</div>

<script>
const DATE = '{{ $date->toDateString() }}';

function applyScales() {
    document.querySelectorAll('.ig-preview-wrap').forEach(wrap => {
        const w     = wrap.clientWidth;
        const scale = w / 1080;
        const slide = wrap.querySelector('.ig-slide');
        slide.style.transform       = `scale(${scale})`;
        slide.style.transformOrigin = 'top left';
        wrap.style.height           = w + 'px';
    });
}
applyScales();
window.addEventListener('resize', applyScales);

function downloadSlide(num, btn) {
    if (btn) { btn.textContent = '⏳ Генерира…'; btn.classList.add('loading'); }
    const url = `/admin/instagram/daily/${DATE}/slide/${num}.png`;
    fetch(url)
        .then(r => {
            if (!r.ok) throw new Error('Server error ' + r.status);
            return r.blob();
        })
        .then(blob => {
            const link = document.createElement('a');
            link.download = `stellar-omens-${DATE}-slide-${num}.png`;
            link.href = URL.createObjectURL(blob);
            link.click();
            URL.revokeObjectURL(link.href);
        })
        .catch(e => alert('Failed: ' + e.message))
        .finally(() => {
            if (btn) { btn.textContent = '↓ Download PNG'; btn.classList.remove('loading'); }
        });
}

async function downloadAll() {
    const btn = document.getElementById('dl-all-btn');
    btn.disabled = true;
    for (let i = 1; i <= 6; i++) {
        btn.textContent = `⏳ Slide ${i}/6…`;
        await new Promise((resolve, reject) => {
            const url = `/admin/instagram/daily/${DATE}/slide/${i}.png`;
            fetch(url)
                .then(r => { if (!r.ok) throw new Error('Server error ' + r.status); return r.blob(); })
                .then(blob => {
                    const link = document.createElement('a');
                    link.download = `stellar-omens-${DATE}-slide-${i}.png`;
                    link.href = URL.createObjectURL(blob);
                    link.click();
                    URL.revokeObjectURL(link.href);
                    setTimeout(resolve, 400);
                })
                .catch(e => { alert(`Slide ${i} failed: ` + e.message); resolve(); });
        });
    }
    btn.textContent = '↓ Download All 6';
    btn.disabled = false;
}
</script>

@endsection
