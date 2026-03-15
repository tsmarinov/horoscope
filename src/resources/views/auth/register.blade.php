@extends('layouts.app')

@section('title', 'Create Account')
@section('description', 'Create your free Stellar Omens account.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div style="text-align:center;padding:2rem 0 1.5rem">
        <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.3rem">
            Create Account
        </h1>
        <p style="font-size:0.85rem;color:var(--theme-muted)">Free access to your personal birth chart</p>
    </div>

    <div class="card" style="max-width:26rem;margin:0 auto">

        @if($errors->any())
        <div style="background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.25);border-radius:0.4rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.83rem;color:#dc2626">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" style="display:flex;flex-direction:column;gap:1rem">
            @csrf

            <div>
                <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--theme-muted);letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.35rem">
                    Name
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       style="width:100%;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.4rem;padding:0.6rem 0.75rem;font-size:0.88rem;color:var(--theme-text);outline:none;box-sizing:border-box"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            <div>
                <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--theme-muted);letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.35rem">
                    Email
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required
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

            <div>
                <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--theme-muted);letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.35rem">
                    Confirm Password
                </label>
                <input type="password" name="password_confirmation" required
                       style="width:100%;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.4rem;padding:0.6rem 0.75rem;font-size:0.88rem;color:var(--theme-text);outline:none;box-sizing:border-box"
                       onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            </div>

            {{-- T&C --}}
            <div style="display:flex;flex-direction:column;gap:0.55rem;padding:0.25rem 0">
                <label style="display:flex;align-items:flex-start;gap:0.6rem;font-size:0.82rem;color:var(--theme-muted);cursor:pointer;line-height:1.5">
                    <input type="checkbox" name="terms" required style="accent-color:#6a329f;margin-top:0.2rem;flex-shrink:0">
                    <span>I agree to the
                        <a href="{{ route('terms') }}" target="_blank" style="color:#6a329f;font-weight:600;text-decoration:none">Terms &amp; Conditions</a>
                        and
                        <a href="{{ route('privacy') }}" target="_blank" style="color:#6a329f;font-weight:600;text-decoration:none">Privacy Policy</a>
                    </span>
                </label>
                <label style="display:flex;align-items:flex-start;gap:0.6rem;font-size:0.82rem;color:var(--theme-muted);cursor:pointer;line-height:1.5">
                    <input type="checkbox" name="newsletter" style="accent-color:#6a329f;margin-top:0.2rem;flex-shrink:0">
                    <span>Send me the Stellar Omens newsletter — astrological insights, new features, and seasonal guides</span>
                </label>
            </div>

            <button type="submit"
                    style="background:#6a329f;color:#fff;border:none;border-radius:0.4rem;padding:0.65rem 1rem;font-size:0.88rem;font-weight:600;cursor:pointer;letter-spacing:0.03em;transition:background 0.15s"
                    onmouseover="this.style.background='#7e3fbf'" onmouseout="this.style.background='#6a329f'">
                Create Account →
            </button>
        </form>

    </div>

    <p style="text-align:center;font-size:0.82rem;color:var(--theme-muted);margin-top:1.25rem">
        Already have an account?
        <a href="{{ route('login') }}" style="color:#6a329f;font-weight:600;text-decoration:none">Sign in</a>
    </p>

@endsection
