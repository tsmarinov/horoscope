@extends('layouts.app')

@section('title', 'Stellar Profiles')
@section('description', 'Manage your birth data profiles for personalised horoscopes.')
@section('nav_profile', 'active')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div class="page-hero-sm">
        <h1 class="font-display page-title page-title-v">
            {{ __('ui.stellar_profiles.title') }}
        </h1>
        <p class="page-subtitle">{{ __('ui.stellar_profiles.subtitle') }}</p>
    </div>

    {{-- Flash messages --}}
    @if(session('status') === 'profile_created')
    <div class="alert-success">
        {{ __('ui.stellar_profiles.flash_created') }}
    </div>
    @endif
    @if(session('status') === 'profile_updated')
    <div class="alert-success">
        {{ __('ui.stellar_profiles.flash_updated') }}
    </div>
    @endif
    @if(session('status') === 'profile_deleted')
    <div class="alert-info">
        {{ __('ui.stellar_profiles.flash_deleted') }}
    </div>
    @endif

    {{-- Search / filter --}}
    <form method="GET" action="{{ route('stellar-profiles.index') }}" style="margin-bottom:1rem"
          x-data="{
              q: '{{ addslashes($q) }}',
              open: false,
              names: {{ $allNames->toJson() }},
              get suggestions() {
                  if (this.q.length < 1) return [];
                  var q = this.q.toLowerCase();
                  return this.names.filter(function(n) { return n.toLowerCase().includes(q); });
              },
              pick(name) { this.q = name; this.open = false; }
          }"
          @click.outside="open = false">
        <div style="display:flex;gap:0.5rem">
            <div style="position:relative;flex:1">
                <input type="text" name="q" x-model="q" placeholder="{{ __('ui.stellar_profiles.search_placeholder') }}"
                       @input="open = true"
                       @keydown.escape="open = false"
                       @keydown.enter="open = false"
                       class="form-input-sm"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
                <div x-show="open && suggestions.length > 0" x-cloak
                     style="position:absolute;z-index:50;top:100%;left:0;right:0;margin-top:0.2rem;background:var(--theme-card);border:1px solid var(--theme-border);border-radius:0.35rem;box-shadow:0 4px 16px rgba(0,0,0,0.12);overflow:hidden">
                    <template x-for="name in suggestions" :key="name">
                        <div @mousedown.prevent="pick(name)"
                             x-text="name"
                             style="padding:0.45rem 0.75rem;font-size:0.83rem;cursor:pointer;color:var(--theme-text);border-bottom:1px solid var(--theme-border)"
                             onmouseover="this.style.background='rgba(106,50,159,0.07)'"
                             onmouseout="this.style.background=''">
                        </div>
                    </template>
                </div>
            </div>
            <button type="submit"
                    style="background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.45rem 0.85rem;font-size:0.83rem;color:var(--theme-text);cursor:pointer">
                {{ __('ui.stellar_profiles.search') }}
            </button>
            @if($q)
            <a href="{{ route('stellar-profiles.index') }}"
               style="display:flex;align-items:center;padding:0.45rem 0.75rem;font-size:0.83rem;color:var(--theme-muted);border:1px solid var(--theme-border);border-radius:0.35rem;text-decoration:none">
                ✕ {{ __('ui.stellar_profiles.clear') }}
            </a>
            @endif
        </div>
    </form>

    {{-- Add new profile --}}
    <div x-data="profileForm()" @open-edit.window="open = false" style="margin-bottom:1rem">
        <button @click="open = !open; if (open) $dispatch('open-add')" class="btn-add">
            <span x-text="open ? '✕ Cancel' : '+ Add Stellar Profile'"></span>
        </button>

        <div x-show="open" x-cloak x-transition style="margin-top:0.75rem">
            <div class="card">
                <div class="section-label" style="margin-bottom:0.85rem">New Profile</div>
                @if($errors->any() && ! request()->route()->getName())
                <div style="font-size:0.78rem;color:#dc2626;margin-bottom:0.65rem">Please fix the errors below.</div>
                @endif
                <form method="POST" action="{{ route('stellar-profiles.store') }}">
                    @csrf
                    @include('stellar-profiles._form', ['profile' => null, 'errors' => $errors])
                    <div class="row-actions">
                        <button type="submit" class="btn-primary-sm" style="padding:0.5rem 1rem;font-size:0.83rem;">
                            Save Profile
                        </button>
                        <button type="button" @click="open = false" class="btn-text-muted">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Profile list --}}
    @if($profiles->isEmpty())
    <div class="card empty-state">
        <div class="empty-icon">☽</div>
        <p class="page-subtitle">No stellar profiles yet. Add your birth data to unlock personalised horoscopes.</p>
    </div>
    @else
    <div class="sp-list">
        @foreach($profiles as $profile)
        @php
            $cityName = $profile->birthCity?->name;
            $cityCode = $profile->birthCity?->country_code;
            $cityInit = $cityName ? $cityName . ($cityCode ? ' (' . $cityCode . ')' : '') : '';
            $cityId   = $profile->birth_city_id ?? 'null';
        @endphp
        <div id="{{ $profile->uuid }}"
             x-data="profileForm('{{ addslashes($cityInit) }}', {{ $cityId }}, {{ request('edit') === $profile->uuid ? 'true' : 'false' }})"
             @open-edit.window="open = ($event.detail.uuid === '{{ $profile->uuid }}')"
             @open-add.window="open = false"
             class="card" style="padding:0">

            {{-- Profile summary row --}}
            <div x-show="!open" class="sp-card-header">
                <div>
                    @php
                        $tier    = $profile->getChartTier();
                        $sign    = $profile->sunSign();
                        $age     = $profile->age();
                        $genderLabel = match($profile->gender?->value) {
                            'male'   => 'Male',
                            'female' => 'Female',
                            default  => 'Other',
                        };
                    @endphp
                    <div class="sp-card-info">
                        <span class="sp-card-name">{{ $profile->name }}</span>
                    </div>
                    <div class="sp-card-meta">
                        @if($sign)
                            <span>{{ $sign['glyph'] }} {{ $sign['name'] }}</span>
                            <span>·</span>
                        @endif
                        @if($age !== null)
                            <span>{{ $profile->birth_date->format('Y') }} · {{ $age }} y.o.</span>
                            <span>·</span>
                        @endif
                        <span>{{ $genderLabel }}</span>
                        @if($profile->birth_time)
                            <span>· {{ substr($profile->birth_time, 0, 5) }}</span>
                        @endif
                        @if($cityName)
                            <span>· {{ $cityName }}</span>
                        @endif
                    </div>
                </div>
                <div class="sp-card-actions">
                    <a href="{{ route('natal.show', $profile) }}" class="sp-card-btn">
                        Natal →
                    </a>
                    <button @click="$dispatch('open-edit', { uuid: '{{ $profile->uuid }}' })"
                            class="sp-card-btn" style="background:none;border:none;cursor:pointer;font-family:inherit;">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('stellar-profiles.destroy', $profile) }}" onsubmit="return confirm('Delete this profile? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sp-card-btn-del"
                                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Edit form --}}
            <div x-show="open" x-cloak class="sp-card-edit">
                <div class="section-label" style="margin-bottom:0.85rem">Edit Profile</div>
                <form method="POST" action="{{ route('stellar-profiles.update', $profile) }}">
                    @csrf
                    @method('PATCH')
                    @include('stellar-profiles._form', ['profile' => $profile, 'errors' => $errors])
                    <div class="row-actions">
                        <button type="submit" class="btn-primary-sm" style="padding:0.5rem 1rem;font-size:0.83rem;">
                            Save Changes
                        </button>
                        <button type="button" @click="open = false" class="btn-text-muted">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

        </div>
        @endforeach
    </div>
    @endif

    {{-- Pagination --}}
    @if($profiles->hasPages())
    <div class="pag">
        {{-- Previous --}}
        @if($profiles->onFirstPage())
            <span class="pag-disabled">←</span>
        @else
            <a href="{{ $profiles->previousPageUrl() }}" class="pag-btn">←</a>
        @endif

        {{-- Page numbers --}}
        @foreach($profiles->getUrlRange(1, $profiles->lastPage()) as $page => $url)
            @if($page == $profiles->currentPage())
                <span class="pag-active">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="pag-btn">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($profiles->hasMorePages())
            <a href="{{ $profiles->nextPageUrl() }}" class="pag-btn">→</a>
        @else
            <span class="pag-disabled">→</span>
        @endif
    </div>
    @endif

    <div class="back-link-row" style="padding:0.5rem 0 1.5rem;text-align:center">
        @if(request('edit'))
        <a href="{{ route('stellar-profiles.index') }}" class="link-muted">← Back to list</a>
        @else
        <a href="{{ route('profile') }}" class="link-muted">← Back to Account</a>
        @endif
    </div>

@endsection
