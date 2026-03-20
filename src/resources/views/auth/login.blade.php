@extends('layouts.app')

@section('title', 'Sign In')
@section('description', 'Sign in to your Stellar Omens account.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div class="page-hero">
        <h1 class="font-display page-title">
            {{ __('ui.auth.login.title') }}
        </h1>
        <p class="page-subtitle">{{ __('ui.auth.login.subtitle') }}</p>
    </div>

    <div class="card card-narrow">

        @if($errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="form-stack">
            @csrf

            <div>
                <label class="form-label">
                    {{ __('ui.auth.login.email') }}
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="form-input"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <div>
                <label class="form-label">
                    {{ __('ui.auth.login.password') }}
                </label>
                <input type="password" name="password" required
                       class="form-input"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <label class="form-check">
                <input type="checkbox" name="remember" class="form-check-input">
                {{ __('ui.auth.login.remember_me') }}
            </label>

            <button type="submit" class="btn-primary">
                {{ __('ui.auth.login.submit') }}
            </button>
        </form>

    </div>

    <p class="auth-note">
        {{ __('ui.auth.login.no_account') }}
        <a href="{{ route('register') }}" class="link-accent">{{ __('ui.auth.login.create_free') }}</a>
    </p>

@endsection
