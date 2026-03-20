@extends('layouts.app')

@section('title', 'My Profile')
@section('description', 'Your Stellar Omens profile and birth charts.')
@section('nav_profile', 'active')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div style="padding:1.5rem 0 1.25rem">
        <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.25rem">
            {{ __('ui.profile.title') }}
        </h1>
        <p style="font-size:0.85rem;color:var(--theme-muted)">{{ __('ui.profile.subtitle') }}</p>
    </div>

    {{-- Status messages --}}
    @if(session('status') === 'name_updated')
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#16a34a">
        {{ __('ui.profile.flash_name_updated') }}
    </div>
    @endif
    @if(session('status') === 'email_change_sent')
    <div style="background:rgba(106,50,159,0.08);border:1px solid rgba(106,50,159,0.25);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#6a329f">
        {{ __('ui.profile.flash_email_change_sent') }}
    </div>
    @endif
    @if(session('status') === 'email_changed')
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#16a34a">
        {{ __('ui.profile.flash_email_changed') }}
    </div>
    @endif
    @if(session('status') === 'email_change_cancelled')
    <div style="background:rgba(107,114,128,0.08);border:1px solid rgba(107,114,128,0.25);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:var(--theme-muted)">
        {{ __('ui.profile.flash_email_change_cancelled') }}
    </div>
    @endif
    @if(session('status') === 'email_confirmed')
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#16a34a">
        {{ __('ui.profile.flash_email_confirmed') }}
    </div>
    @endif
    @if(session('status') === 'confirmation_sent')
    <div style="background:rgba(106,50,159,0.08);border:1px solid rgba(106,50,159,0.25);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#6a329f">
        {{ __('ui.profile.flash_confirmation_sent') }}
    </div>
    @endif
    @if(session('status') === 'already_confirmed')
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#16a34a">
        {{ __('ui.profile.flash_already_confirmed') }}
    </div>
    @endif

    {{-- Email confirmation banner --}}
    @if(! $user->email_confirmed_at)
    <div style="background:rgba(234,179,8,0.08);border:1px solid rgba(234,179,8,0.35);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <div style="font-size:0.83rem;font-weight:600;color:var(--theme-text)">{{ __('ui.profile.confirm_email_title') }}</div>
            <div style="font-size:0.78rem;color:var(--theme-muted);margin-top:0.15rem">
                {{ __('ui.profile.confirm_email_inbox') }} <strong>{{ $user->email }}</strong> for a confirmation link.
            </div>
            @error('resend')
            <div style="font-size:0.75rem;color:#dc2626;margin-top:0.2rem">{{ $message }}</div>
            @enderror
        </div>
        <form method="POST" action="{{ route('email.resend') }}" style="flex-shrink:0">
            @csrf
            <button type="submit"
                    style="background:none;border:1px solid rgba(234,179,8,0.5);border-radius:0.35rem;padding:0.4rem 0.8rem;font-size:0.78rem;font-weight:600;color:var(--theme-text);cursor:pointer;white-space:nowrap;transition:background 0.12s"
                    onmouseover="this.style.background='rgba(234,179,8,0.1)'"
                    onmouseout="this.style.background='none'">
                {{ __('ui.profile.resend_email') }}
            </button>
        </form>
    </div>
    @endif

    {{-- Pending email change banner --}}
    @if($user->pending_email)
    <div style="background:rgba(234,179,8,0.08);border:1px solid rgba(234,179,8,0.35);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <div style="font-size:0.83rem;font-weight:600;color:var(--theme-text)">{{ __('ui.profile.email_change_pending') }}</div>
            <div style="font-size:0.78rem;color:var(--theme-muted);margin-top:0.15rem">
                Check <strong>{{ $user->pending_email }}</strong> for a confirmation link.
            </div>
        </div>
        <form method="POST" action="{{ route('email.change.cancel') }}" style="flex-shrink:0">
            @csrf
            <button type="submit"
                    style="background:none;border:1px solid rgba(234,179,8,0.5);border-radius:0.35rem;padding:0.4rem 0.8rem;font-size:0.78rem;font-weight:600;color:var(--theme-text);cursor:pointer;white-space:nowrap">
                {{ __('ui.profile.cancel') }}
            </button>
        </form>
    </div>
    @endif

    {{-- Account info --}}
    <div class="card" x-data="{ editName: {{ $errors->has('name') ? 'true' : 'false' }}, editEmail: {{ $errors->has('email') ? 'true' : 'false' }} }">
        <div class="section-label" style="margin-bottom:0.75rem">Account</div>
        <div style="display:flex;flex-direction:column;gap:0.5rem">

            {{-- Name row --}}
            <div>
                <div x-show="!editName" style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:0.82rem;color:var(--theme-muted)">{{ __('ui.profile.label_name') }}</span>
                    <div style="display:flex;align-items:center;gap:0.6rem">
                        <span style="font-size:0.88rem;color:var(--theme-text);font-weight:500">{{ $user->name }}</span>
                        <button @click="editName = true"
                                style="background:none;border:none;font-size:0.75rem;color:#6a329f;cursor:pointer;font-weight:600;padding:0">
                            {{ __('ui.profile.edit') }}
                        </button>
                    </div>
                </div>
                <div x-show="editName" x-cloak>
                    @if($errors->has('name'))
                    <div style="font-size:0.75rem;color:#dc2626;margin-bottom:0.35rem">{{ $errors->first('name') }}</div>
                    @endif
                    <form method="POST" action="{{ route('profile.update') }}" style="display:flex;gap:0.4rem;align-items:center">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               style="flex:1;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                               onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'"
                               x-ref="nameInput" @focus="$nextTick(() => $refs.nameInput.select())">
                        <button type="submit"
                                style="background:#6a329f;color:#fff;border:none;border-radius:0.35rem;padding:0.45rem 0.75rem;font-size:0.8rem;font-weight:600;cursor:pointer;white-space:nowrap">
                            {{ __('ui.profile.save') }}
                        </button>
                        <button type="button" @click="editName = false"
                                style="background:none;border:none;font-size:0.8rem;color:var(--theme-muted);cursor:pointer">
                            {{ __('ui.profile.cancel') }}
                        </button>
                    </form>
                </div>
            </div>
            <hr class="divider">
            <div>
                <div x-show="!editEmail" style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:0.82rem;color:var(--theme-muted)">{{ __('ui.profile.label_email') }}</span>
                    <div style="display:flex;align-items:center;gap:0.6rem">
                        <span style="font-size:0.88rem;color:var(--theme-text)">
                            {{ $user->email }}
                            @if($user->email_confirmed_at)
                                <span style="font-size:0.72rem;color:#16a34a;margin-left:0.35rem">✓</span>
                            @endif
                        </span>
                        @if(! $user->pending_email)
                        <button @click="editEmail = true"
                                style="background:none;border:none;font-size:0.75rem;color:#6a329f;cursor:pointer;font-weight:600;padding:0">
                            {{ __('ui.profile.edit') }}
                        </button>
                        @endif
                    </div>
                </div>
                <div x-show="editEmail" x-cloak>
                    @if($errors->has('email'))
                    <div style="font-size:0.75rem;color:#dc2626;margin-bottom:0.35rem">{{ $errors->first('email') }}</div>
                    @endif
                    <form method="POST" action="{{ route('email.change.request') }}" style="display:flex;gap:0.4rem;align-items:center">
                        @csrf
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="New email address" required
                               style="flex:1;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                               onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'"
                               x-ref="emailInput" @focus="$nextTick(() => $refs.emailInput.select())">
                        <button type="submit"
                                style="background:#6a329f;color:#fff;border:none;border-radius:0.35rem;padding:0.45rem 0.75rem;font-size:0.8rem;font-weight:600;cursor:pointer;white-space:nowrap">
                            {{ __('ui.profile.send_link') }}
                        </button>
                        <button type="button" @click="editEmail = false"
                                style="background:none;border:none;font-size:0.8rem;color:var(--theme-muted);cursor:pointer">
                            {{ __('ui.profile.cancel') }}
                        </button>
                    </form>
                    <p style="font-size:0.75rem;color:var(--theme-muted);margin-top:0.35rem">
                        {{ __('ui.profile.email_change_hint') }}
                    </p>
                </div>
            </div>
            <hr class="divider">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:0.82rem;color:var(--theme-muted)">{{ __('ui.profile.member_since') }}</span>
                <span style="font-size:0.88rem;color:var(--theme-text)">{{ $user->created_at->format('M j, Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Birth charts --}}
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
            <div class="section-label" style="margin-bottom:0">{{ __('ui.profile.last_used_charts') }}</div>
            <a href="{{ route('stellar-profiles.index') }}"
               style="font-size:0.75rem;color:#6a329f;font-weight:600;text-decoration:none">
                {{ __('ui.profile.manage') }}
            </a>
        </div>

        @if($profiles->isEmpty())
        <div style="text-align:center;padding:1.5rem 0">
            <div style="font-size:2rem;margin-bottom:0.5rem">♈</div>
            <p style="font-size:0.85rem;color:var(--theme-muted);margin-bottom:1rem">
                {{ __('ui.profile.no_chart_yet') }}
            </p>
            <a href="{{ url('/natal') }}"
               style="display:inline-block;background:#6a329f;color:#fff;border-radius:0.4rem;padding:0.55rem 1.1rem;font-size:0.83rem;font-weight:600;text-decoration:none;letter-spacing:0.03em">
                {{ __('ui.profile.create_natal_chart') }}
            </a>
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:0.75rem">
            @foreach($profiles as $profile)
            <a href="{{ route('natal.show', $profile) }}"
               style="display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0.75rem;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.4rem;text-decoration:none;transition:border-color 0.15s"
               onmouseover="this.style.borderColor='#6a329f'" onmouseout="this.style.borderColor='var(--theme-border)'">
                <div>
                    <div style="font-size:0.88rem;font-weight:600;color:var(--theme-text)">{{ $profile->name }}</div>
                    <div style="font-size:0.75rem;color:var(--theme-muted);margin-top:0.1rem;display:flex;gap:0.35rem;flex-wrap:wrap">
                        @php $sign = $profile->sunSign(); @endphp
                        @if($sign)<span>{{ $sign['glyph'] }} {{ $sign['name'] }}</span>@endif
                        <span>· {{ $profile->birth_date?->format('M j, Y') }}</span>
                        @if($profile->age() !== null)<span>· {{ $profile->age() }} y.o.</span>@endif
                        @if($profile->birth_time)<span>· {{ substr($profile->birth_time, 0, 5) }}</span>@endif
                        @if($profile->birthCity)<span>· {{ $profile->birthCity->name }}</span>@endif
                    </div>
                </div>
                <span style="font-size:0.75rem;color:#6a329f;font-weight:600">{{ __('ui.profile.view') }}</span>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Sign out --}}
    <div style="text-align:center;padding:0.25rem 0 0.75rem">
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit"
                    style="background:none;border:none;font-size:0.82rem;color:var(--theme-muted);cursor:pointer;text-decoration:underline">
                {{ __('ui.profile.sign_out') }}
            </button>
        </form>
    </div>

    {{-- Delete account --}}
    <div x-data="{ open: false }" style="margin-bottom:2rem">
        <button @click="open = !open"
                style="background:none;border:none;font-size:0.78rem;color:var(--theme-muted);cursor:pointer;opacity:0.55;padding:0;text-decoration:underline"
                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.55'">
            {{ __('ui.profile.delete_account') }}
        </button>

        <div x-show="open" x-cloak x-transition
             style="margin-top:0.75rem;background:rgba(220,38,38,0.05);border:1px solid rgba(220,38,38,0.2);border-radius:0.5rem;padding:1rem">
            <p style="font-size:0.83rem;color:var(--theme-text);font-weight:600;margin-bottom:0.4rem">
                {{ __('ui.profile.delete_permanent') }}
            </p>
            <p style="font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.85rem;line-height:1.6">
                {{ __('ui.profile.delete_description') }} <strong style="color:var(--theme-text)">{{ __('ui.profile.delete_confirm_word') }}</strong> below.
            </p>

            @if($errors->has('confirm'))
            <div style="font-size:0.78rem;color:#dc2626;margin-bottom:0.5rem">{{ $errors->first('confirm') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                    <input type="text" name="confirm" placeholder="{{ __('ui.profile.delete_confirm_word') }}" autocomplete="off"
                           style="flex:1;min-width:120px;background:var(--theme-raised);border:1px solid rgba(220,38,38,0.3);border-radius:0.35rem;padding:0.5rem 0.65rem;font-size:0.85rem;color:var(--theme-text);outline:none;font-family:monospace;letter-spacing:0.05em"
                           onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='rgba(220,38,38,0.3)'">
                    <button type="submit"
                            style="background:#dc2626;color:#fff;border:none;border-radius:0.35rem;padding:0.5rem 0.9rem;font-size:0.82rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:background 0.12s"
                            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        {{ __('ui.profile.delete_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
