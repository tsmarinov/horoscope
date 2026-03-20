@extends('layouts.app')

@section('title', 'My Profile')
@section('description', 'Your Stellar Omens profile and birth charts.')
@section('nav_profile', 'active')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div class="page-hero-sm">
        <h1 class="font-display page-title page-title-v">
            {{ __('ui.profile.title') }}
        </h1>
        <p class="page-subtitle">{{ __('ui.profile.subtitle') }}</p>
    </div>

    {{-- Status messages --}}
    @if(session('status') === 'name_updated')
    <div class="alert-success">
        {{ __('ui.profile.flash_name_updated') }}
    </div>
    @endif
    @if(session('status') === 'email_change_sent')
    <div class="alert-accent">
        {{ __('ui.profile.flash_email_change_sent') }}
    </div>
    @endif
    @if(session('status') === 'email_changed')
    <div class="alert-success">
        {{ __('ui.profile.flash_email_changed') }}
    </div>
    @endif
    @if(session('status') === 'email_change_cancelled')
    <div class="alert-info">
        {{ __('ui.profile.flash_email_change_cancelled') }}
    </div>
    @endif
    @if(session('status') === 'email_confirmed')
    <div class="alert-success">
        {{ __('ui.profile.flash_email_confirmed') }}
    </div>
    @endif
    @if(session('status') === 'confirmation_sent')
    <div class="alert-accent">
        {{ __('ui.profile.flash_confirmation_sent') }}
    </div>
    @endif
    @if(session('status') === 'already_confirmed')
    <div class="alert-success">
        {{ __('ui.profile.flash_already_confirmed') }}
    </div>
    @endif

    {{-- Email confirmation banner --}}
    @if(! $user->email_confirmed_at)
    <div class="alert-warn">
        <div>
            <div class="alert-warn-title">{{ __('ui.profile.confirm_email_title') }}</div>
            <div class="alert-warn-body">
                {{ __('ui.profile.confirm_email_inbox') }} <strong>{{ $user->email }}</strong> for a confirmation link.
            </div>
            @error('resend')
            <div style="font-size:0.75rem;color:#dc2626;margin-top:0.2rem">{{ $message }}</div>
            @enderror
        </div>
        <form method="POST" action="{{ route('email.resend') }}" class="alert-warn-form">
            @csrf
            <button type="submit" class="btn-warn"
                    onmouseover="this.style.background='rgba(234,179,8,0.1)'"
                    onmouseout="this.style.background='none'">
                {{ __('ui.profile.resend_email') }}
            </button>
        </form>
    </div>
    @endif

    {{-- Pending email change banner --}}
    @if($user->pending_email)
    <div class="alert-warn">
        <div>
            <div class="alert-warn-title">{{ __('ui.profile.email_change_pending') }}</div>
            <div class="alert-warn-body">
                Check <strong>{{ $user->pending_email }}</strong> for a confirmation link.
            </div>
        </div>
        <form method="POST" action="{{ route('email.change.cancel') }}" class="alert-warn-form">
            @csrf
            <button type="submit" class="btn-warn">
                {{ __('ui.profile.cancel') }}
            </button>
        </form>
    </div>
    @endif

    {{-- Account info --}}
    <div class="card" x-data="{ editName: {{ $errors->has('name') ? 'true' : 'false' }}, editEmail: {{ $errors->has('email') ? 'true' : 'false' }} }">
        <div class="section-label">Account</div>
        <div class="col-stack">

            {{-- Name row --}}
            <div>
                <div x-show="!editName" class="row-between">
                    <span class="profile-field-label">{{ __('ui.profile.label_name') }}</span>
                    <div class="row-gap-sm">
                        <span class="profile-field-value">{{ $user->name }}</span>
                        <button @click="editName = true" class="btn-text-accent">
                            {{ __('ui.profile.edit') }}
                        </button>
                    </div>
                </div>
                <div x-show="editName" x-cloak>
                    @if($errors->has('name'))
                    <div class="field-error">{{ $errors->first('name') }}</div>
                    @endif
                    <form method="POST" action="{{ route('profile.update') }}" class="field-inline">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="field-inline-input"
                               onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'"
                               x-ref="nameInput" @focus="$nextTick(() => $refs.nameInput.select())">
                        <button type="submit" class="btn-primary-sm">
                            {{ __('ui.profile.save') }}
                        </button>
                        <button type="button" @click="editName = false" class="btn-text-muted">
                            {{ __('ui.profile.cancel') }}
                        </button>
                    </form>
                </div>
            </div>
            <hr class="divider">
            <div>
                <div x-show="!editEmail" class="row-between">
                    <span class="profile-field-label">{{ __('ui.profile.label_email') }}</span>
                    <div class="row-gap-sm">
                        <span class="profile-field-value-plain">
                            {{ $user->email }}
                            @if($user->email_confirmed_at)
                                <span class="field-ok">✓</span>
                            @endif
                        </span>
                        @if(! $user->pending_email)
                        <button @click="editEmail = true" class="btn-text-accent">
                            {{ __('ui.profile.edit') }}
                        </button>
                        @endif
                    </div>
                </div>
                <div x-show="editEmail" x-cloak>
                    @if($errors->has('email'))
                    <div class="field-error">{{ $errors->first('email') }}</div>
                    @endif
                    <form method="POST" action="{{ route('email.change.request') }}" class="field-inline">
                        @csrf
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="New email address" required
                               class="field-inline-input"
                               onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'"
                               x-ref="emailInput" @focus="$nextTick(() => $refs.emailInput.select())">
                        <button type="submit" class="btn-primary-sm">
                            {{ __('ui.profile.send_link') }}
                        </button>
                        <button type="button" @click="editEmail = false" class="btn-text-muted">
                            {{ __('ui.profile.cancel') }}
                        </button>
                    </form>
                    <p class="field-hint">
                        {{ __('ui.profile.email_change_hint') }}
                    </p>
                </div>
            </div>
            <hr class="divider">
            <div class="row-between">
                <span class="profile-field-label">{{ __('ui.profile.member_since') }}</span>
                <span class="profile-field-value-plain">{{ $user->created_at->format('M j, Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Birth charts --}}
    <div class="card">
        <div class="row-between" style="margin-bottom:0.75rem">
            <div class="section-label section-label-0">{{ __('ui.profile.last_used_charts') }}</div>
            <a href="{{ route('stellar-profiles.index') }}" class="link-accent-sm">
                {{ __('ui.profile.manage') }}
            </a>
        </div>

        @if($profiles->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">♈</div>
            <p class="empty-text">
                {{ __('ui.profile.no_chart_yet') }}
            </p>
            <a href="{{ url('/natal') }}" class="btn-primary-inline">
                {{ __('ui.profile.create_natal_chart') }}
            </a>
        </div>
        @else
        <div class="col-stack-lg">
            @foreach($profiles as $profile)
            <a href="{{ route('natal.show', $profile) }}" class="sp-item"
               onmouseover="this.style.borderColor='#6a329f'" onmouseout="this.style.borderColor='var(--theme-border)'">
                <div>
                    <div class="sp-item-name">{{ $profile->name }}</div>
                    <div class="sp-item-meta">
                        @php $sign = $profile->sunSign(); @endphp
                        @if($sign)<span>{{ $sign['glyph'] }} {{ $sign['name'] }}</span>@endif
                        <span>· {{ $profile->birth_date?->format('M j, Y') }}</span>
                        @if($profile->age() !== null)<span>· {{ $profile->age() }} y.o.</span>@endif
                        @if($profile->birth_time)<span>· {{ substr($profile->birth_time, 0, 5) }}</span>@endif
                        @if($profile->birthCity)<span>· {{ $profile->birthCity->name }}</span>@endif
                    </div>
                </div>
                <span class="sp-item-cta">{{ __('ui.profile.view') }}</span>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Sign out --}}
    <div class="back-link-row">
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn-text-underline">
                {{ __('ui.profile.sign_out') }}
            </button>
        </form>
    </div>

    {{-- Delete account --}}
    <div x-data="{ open: false }" class="delete-zone">
        <button @click="open = !open"
                class="btn-text-underline" style="opacity:0.55;padding:0;"
                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.55'">
            {{ __('ui.profile.delete_account') }}
        </button>

        <div x-show="open" x-cloak x-transition class="alert-danger">
            <p style="font-size:0.83rem;font-weight:600;margin-bottom:0.4rem">
                {{ __('ui.profile.delete_permanent') }}
            </p>
            <p class="delete-body" style="font-size:0.78rem;margin-bottom:0.85rem">
                {{ __('ui.profile.delete_description') }} <strong class="delete-confirm-word">{{ __('ui.profile.delete_confirm_word') }}</strong> below.
            </p>

            @if($errors->has('confirm'))
            <div class="delete-error">{{ $errors->first('confirm') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="delete-row">
                    <input type="text" name="confirm" placeholder="{{ __('ui.profile.delete_confirm_word') }}" autocomplete="off"
                           class="delete-input"
                           onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='rgba(220,38,38,0.3)'">
                    <button type="submit" class="btn-danger"
                            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        {{ __('ui.profile.delete_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
