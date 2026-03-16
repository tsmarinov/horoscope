@extends('layouts.app')

@section('title', 'Stellar Profiles')
@section('description', 'Manage your birth data profiles for personalised horoscopes.')
@section('nav_profile', 'active')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div style="padding:1.5rem 0 1.25rem">
        <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.25rem">
            Stellar Profiles
        </h1>
        <p style="font-size:0.85rem;color:var(--theme-muted)">Birth data for personalised horoscopes — add yourself, a partner, or anyone you'd like to read for</p>
    </div>

    {{-- Flash messages --}}
    @if(session('status') === 'profile_created')
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#16a34a">
        ✓ Profile created.
    </div>
    @endif
    @if(session('status') === 'profile_updated')
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#16a34a">
        ✓ Profile updated.
    </div>
    @endif
    @if(session('status') === 'profile_deleted')
    <div style="background:rgba(107,114,128,0.08);border:1px solid rgba(107,114,128,0.25);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:var(--theme-muted)">
        Profile deleted.
    </div>
    @endif

    {{-- Search / filter --}}
    <form method="GET" action="{{ route('stellar-profiles.index') }}" style="margin-bottom:1rem">
        <div style="display:flex;gap:0.5rem">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search by name…"
                   style="flex:1;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.45rem 0.75rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            <button type="submit"
                    style="background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.45rem 0.85rem;font-size:0.83rem;color:var(--theme-text);cursor:pointer">
                Search
            </button>
            @if($q)
            <a href="{{ route('stellar-profiles.index') }}"
               style="display:flex;align-items:center;padding:0.45rem 0.75rem;font-size:0.83rem;color:var(--theme-muted);border:1px solid var(--theme-border);border-radius:0.35rem;text-decoration:none">
                ✕ Clear
            </a>
            @endif
        </div>
    </form>

    {{-- Add new profile --}}
    <div x-data="profileForm()" style="margin-bottom:1rem">
        <button @click="open = !open"
                style="display:flex;align-items:center;gap:0.4rem;background:#6a329f;color:#fff;border:none;border-radius:0.4rem;padding:0.55rem 1rem;font-size:0.83rem;font-weight:600;cursor:pointer;letter-spacing:0.03em">
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
                    <div style="display:flex;gap:0.5rem;margin-top:1rem">
                        <button type="submit"
                                style="background:#6a329f;color:#fff;border:none;border-radius:0.35rem;padding:0.5rem 1rem;font-size:0.83rem;font-weight:600;cursor:pointer">
                            Save Profile
                        </button>
                        <button type="button" @click="open = false"
                                style="background:none;border:none;font-size:0.83rem;color:var(--theme-muted);cursor:pointer">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Profile list --}}
    @if($profiles->isEmpty())
    <div class="card" style="text-align:center;padding:2rem 1rem">
        <div style="font-size:2rem;margin-bottom:0.5rem">☽</div>
        <p style="font-size:0.85rem;color:var(--theme-muted)">No stellar profiles yet. Add your birth data to unlock personalised horoscopes.</p>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:0.75rem">
        @foreach($profiles as $profile)
        @php
            $cityName = $profile->birthCity?->name;
            $cityCode = $profile->birthCity?->country_code;
            $cityInit = $cityName ? $cityName . ($cityCode ? ' (' . $cityCode . ')' : '') : '';
            $cityId   = $profile->birth_city_id ?? 'null';
        @endphp
        <div x-data="profileForm('{{ addslashes($cityInit) }}', {{ $cityId }}, {{ request('edit') == $profile->id ? 'true' : 'false' }})"
             @open-edit.window="open = ($event.detail.id === {{ $profile->id }})"
             class="card" style="padding:0">

            {{-- Profile summary row --}}
            <div x-show="!open" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:0.85rem 1rem">
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
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                        <span style="font-size:0.92rem;font-weight:600;color:var(--theme-text)">{{ $profile->name }}</span>
                    </div>
                    <div style="font-size:0.78rem;color:var(--theme-muted);margin-top:0.25rem;display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center">
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
                <div style="display:flex;gap:0.5rem;align-items:center;flex-shrink:0">
                    <a href="{{ route('natal.show', $profile) }}"
                       style="font-size:0.78rem;color:#6a329f;font-weight:600;text-decoration:none;padding:0.25rem 0.5rem">
                        Natal →
                    </a>
                    <button @click="$dispatch('open-edit', { id: {{ $profile->id }} })"
                            style="background:none;border:none;font-size:0.78rem;color:#6a329f;cursor:pointer;font-weight:600;padding:0.25rem 0.5rem">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('stellar-profiles.destroy', $profile) }}" onsubmit="return confirm('Delete this profile? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                style="background:none;border:none;font-size:0.78rem;color:#dc2626;cursor:pointer;font-weight:600;padding:0.25rem 0.5rem;opacity:0.6"
                                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Edit form --}}
            <div x-show="open" x-cloak style="padding:1rem">
                <div class="section-label" style="margin-bottom:0.85rem">Edit Profile</div>
                <form method="POST" action="{{ route('stellar-profiles.update', $profile) }}">
                    @csrf
                    @method('PATCH')
                    @include('stellar-profiles._form', ['profile' => $profile, 'errors' => $errors])
                    <div style="display:flex;gap:0.5rem;margin-top:1rem">
                        <button type="submit"
                                style="background:#6a329f;color:#fff;border:none;border-radius:0.35rem;padding:0.5rem 1rem;font-size:0.83rem;font-weight:600;cursor:pointer">
                            Save Changes
                        </button>
                        <button type="button" @click="open = false"
                                style="background:none;border:none;font-size:0.83rem;color:var(--theme-muted);cursor:pointer">
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
    <div style="display:flex;justify-content:center;align-items:center;gap:0.35rem;margin-top:1rem;flex-wrap:wrap">
        {{-- Previous --}}
        @if($profiles->onFirstPage())
            <span style="padding:0.35rem 0.65rem;font-size:0.8rem;color:var(--theme-muted);border:1px solid var(--theme-border);border-radius:0.35rem;opacity:0.4">←</span>
        @else
            <a href="{{ $profiles->previousPageUrl() }}" style="padding:0.35rem 0.65rem;font-size:0.8rem;color:var(--theme-text);border:1px solid var(--theme-border);border-radius:0.35rem;text-decoration:none" onmouseover="this.style.borderColor='#6a329f'" onmouseout="this.style.borderColor='var(--theme-border)'">←</a>
        @endif

        {{-- Page numbers --}}
        @foreach($profiles->getUrlRange(1, $profiles->lastPage()) as $page => $url)
            @if($page == $profiles->currentPage())
                <span style="padding:0.35rem 0.65rem;font-size:0.8rem;font-weight:600;color:#fff;background:#6a329f;border:1px solid #6a329f;border-radius:0.35rem">{{ $page }}</span>
            @else
                <a href="{{ $url }}" style="padding:0.35rem 0.65rem;font-size:0.8rem;color:var(--theme-text);border:1px solid var(--theme-border);border-radius:0.35rem;text-decoration:none" onmouseover="this.style.borderColor='#6a329f'" onmouseout="this.style.borderColor='var(--theme-border)'">{{ $page }}</span>
            @endif
        @endforeach

        {{-- Next --}}
        @if($profiles->hasMorePages())
            <a href="{{ $profiles->nextPageUrl() }}" style="padding:0.35rem 0.65rem;font-size:0.8rem;color:var(--theme-text);border:1px solid var(--theme-border);border-radius:0.35rem;text-decoration:none" onmouseover="this.style.borderColor='#6a329f'" onmouseout="this.style.borderColor='var(--theme-border)'">→</a>
        @else
            <span style="padding:0.35rem 0.65rem;font-size:0.8rem;color:var(--theme-muted);border:1px solid var(--theme-border);border-radius:0.35rem;opacity:0.4">→</span>
        @endif
    </div>
    @endif

    <div style="padding:0.5rem 0 1.5rem;text-align:center">
        @if(request('edit'))
        <a href="{{ route('stellar-profiles.index') }}" style="font-size:0.8rem;color:var(--theme-muted);text-decoration:underline">← Back to list</a>
        @else
        <a href="{{ route('profile') }}" style="font-size:0.8rem;color:var(--theme-muted);text-decoration:underline">← Back to Account</a>
        @endif
    </div>

@endsection
