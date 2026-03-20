@extends('layouts.app')

@section('title', 'Create Account')
@section('description', 'Create your free Stellar Omens account.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div class="page-hero">
        <h1 class="font-display page-title">
            {{ __('ui.auth.register.title') }}
        </h1>
        <p class="page-subtitle">{{ __('ui.auth.register.subtitle') }}</p>
    </div>

    <div class="card card-narrow">

        @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="form-stack">
            @csrf

            <div>
                <label class="form-label">
                    {{ __('ui.auth.register.name') }}
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="form-input"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <div>
                <label class="form-label">
                    {{ __('ui.auth.register.email') }}
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="form-input"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <div>
                <label class="form-label">
                    {{ __('ui.auth.register.password') }}
                </label>
                <input type="password" name="password" required
                       class="form-input"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <div>
                <label class="form-label">
                    {{ __('ui.auth.register.confirm_password') }}
                </label>
                <input type="password" name="password_confirmation" required
                       class="form-input"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            {{-- T&C --}}
            <div class="form-stack-sm">
                <label class="form-check-top">
                    <input type="checkbox" name="terms" required class="form-check-input-top">
                    <span>{{ __('ui.auth.register.agree_terms_pre') }}
                        <a href="{{ route('terms') }}" target="_blank" class="link-accent">{{ __('ui.auth.register.terms_link') }}</a>
                        {{ __('ui.auth.register.agree_terms_and') }}
                        <a href="{{ route('privacy') }}" target="_blank" class="link-accent">{{ __('ui.auth.register.privacy_link') }}</a>
                    </span>
                </label>
                <label class="form-check-top">
                    <input type="checkbox" name="newsletter" class="form-check-input-top">
                    <span>{{ __('ui.auth.register.newsletter') }}</span>
                </label>
            </div>

            <button type="submit" class="btn-primary">
                {{ __('ui.auth.register.submit') }}
            </button>
        </form>

    </div>

    <p class="auth-note">
        {{ __('ui.auth.register.have_account') }}
        <a href="{{ route('login') }}" class="link-accent">{{ __('ui.auth.register.sign_in') }}</a>
    </p>

@endsection
