@extends('layouts.app')

@section('title', 'Welcome')

@section('content')

<div style="text-align:center;padding:3rem 0 2rem">
    <h1 class="font-display" style="font-size:1.5rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--theme-text)">
        STELLAR <span class="logo-accent">✦ OMENS</span>
    </h1>
    <p style="color:var(--theme-muted);font-size:0.9rem;margin-top:0.5rem">
        Personal astrology for your chart
    </p>
</div>

{{-- Design system preview --}}
<div class="card">
    <p class="section-label" style="margin-bottom:0.75rem">Chips &amp; badges</p>
    <p style="display:flex;flex-wrap:wrap;gap:0.4rem">
        <span class="chip">☿ Mercury Rx</span>
        <span class="chip chip-accent">✦ New Moon</span>
        <span class="chip">♀ Venus in Pisces</span>
        <span class="chip">♄ Saturn trine ☉ Sun</span>
    </p>
</div>

<div class="card">
    <p class="section-label" style="margin-bottom:0.6rem">Star ratings</p>
    <div style="display:flex;flex-direction:column;gap:0.35rem;font-size:0.88rem">
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="color:var(--theme-muted)">❤️ Love</span>
            <span class="stars">★★★★★</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="color:var(--theme-muted)">💼 Career</span>
            <span class="stars">★★★<span class="empty">★★</span></span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="color:var(--theme-muted)">💚 Health</span>
            <span class="stars">★<span class="empty">★★★★</span></span>
        </div>
    </div>
</div>

<div class="card">
    <p class="section-label" style="margin-bottom:0.5rem">Typography</p>
    <h2 class="font-display" style="font-size:1.1rem;margin-bottom:0.25rem">Cinzel — display headings</h2>
    <p style="font-size:0.88rem;color:var(--theme-muted)">
        Inter — body text, readable at all sizes.
        <em style="color:#6a329f">Italic in accent.</em>
        <strong style="color:var(--theme-text)">Bold for emphasis.</strong>
    </p>
</div>

<div class="tier-lock">🔒 Add birth time &amp; place to unlock full chart features</div>

@endsection
