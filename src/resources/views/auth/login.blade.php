@extends('layouts.app')

@section('title', 'Sign In')
@section('description', 'Sign in to your Stellar Omens account.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div style="text-align:center;padding:2rem 0 1.5rem">
        <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.3rem">
            Sign In
        </h1>
        <p style="font-size:0.85rem;color:var(--theme-muted)">Welcome back to Stellar Omens</p>
    </div>

    <div class="card" style="max-width:26rem;margin:0 auto">

        @if($errors->any())
        <div style="background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.25);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#dc2626">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:1rem">
            @csrf

            <div>
                <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--theme-muted);letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.35rem">
                    Email
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       style="width:100%;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.4rem;padding:0.6rem 0.75rem;font-size:0.88rem;color:var(--theme-text);outline:none;box-sizing:border-box"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <div>
                <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--theme-muted);letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.35rem">
                    Password
                </label>
                <input type="password" name="password" required
                       style="width:100%;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.4rem;padding:0.6rem 0.75rem;font-size:0.88rem;color:var(--theme-text);outline:none;box-sizing:border-box"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.83rem;color:var(--theme-muted);cursor:pointer">
                <input type="checkbox" name="remember" style="accent-color:#6a329f">
                Remember me
            </label>

            <button type="submit"
                    style="background:#6a329f;color:#fff;border:none;border-radius:0.4rem;padding:0.65rem 1rem;font-size:0.88rem;font-weight:600;cursor:pointer;letter-spacing:0.03em;transition:background 0.15s"
                    onmouseover="this.style.background='#7e3fbf'" onmouseout="this.style.background='#6a329f'">
                Sign In →
            </button>
        </form>

    </div>

    <p style="text-align:center;font-size:0.82rem;color:var(--theme-muted);margin-top:1.25rem">
        No account?
        <a href="{{ route('register') }}" style="color:#6a329f;font-weight:600;text-decoration:none">Create one free</a>
    </p>

@endsection
