@extends('layouts.app')

@section('title', __('ui.sun_sign.page_title'))
@section('nav_sun_sign', 'drawer-item-active')

@section('content')

<div class="page-hero">
    <h1 class="page-title">{{ __('ui.sun_sign.page_title') }}</h1>
</div>

{{-- ── Date navigation ────────────────────────────────────────────────────── --}}
@php
    $tomorrow = \Carbon\Carbon::tomorrow();
    $prev     = $date->copy()->subDay();
    $next     = $date->copy()->addDay();
    $isToday  = $date->isToday();
@endphp

<div class="date-nav-row">
    <a href="{{ route('sun-sign.index') }}?date={{ $prev->toDateString() }}" class="date-nav-arrow">←</a>

    <span class="date-nav-label">
        {{ $date->format('l, F j, Y') }}
        @if(!$isToday)
        <a href="{{ route('sun-sign.index') }}" class="date-nav-today">{{ __('ui.nav.today') }}</a>
        @endif
    </span>

    @if($date->lt($tomorrow) || !empty($isAdmin))
    <a href="{{ route('sun-sign.index') }}?date={{ $next->toDateString() }}" class="date-nav-arrow">→</a>
    @else
    <span class="date-nav-arrow date-nav-arrow-disabled">→</span>
    @endif
</div>

{{-- ── 12 sign cards ───────────────────────────────────────────────────────── --}}
<div class="zsign-grid">
    @foreach($signs as $slug => $sign)
    <div class="zsign-card">
        <div class="zsign-card-img">
            @include('partials.zodiac-picture', ['sign' => $slug, 'size' => 64])
            <span class="zsign-glyph-badge">{{ $sign['glyph'] }}</span>
        </div>
        <div class="zsign-meta">
            <div class="zsign-meta-header">
                <span class="zsign-name">{{ ucfirst($slug) }}</span>
                <span class="zsign-element zsign-element-{{ strtolower($sign['element']) }}">{{ $sign['element'] }}</span>
            </div>
            <span class="zsign-dates">{{ $sign['dates'] }}</span>
            @if(!empty($horoscopes[$slug]))
            <p class="zsign-preview">{!! $horoscopes[$slug] !!}</p>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endsection
