@extends('layouts.app')

@section('title', 'Privacy Policy')
@section('description', 'Stellar Omens Privacy Policy.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div style="padding:1.5rem 0 1rem">
        <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.25rem">
            Privacy Policy
        </h1>
        <p style="font-size:0.82rem;color:var(--theme-muted)">Last updated: {{ date('F j, Y') }}</p>
    </div>

    <div class="card" style="font-size:0.88rem;color:var(--theme-text);line-height:1.8">

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">1. What Data We Collect</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            We collect the information you provide when creating an account: name, email address, and birth data (date, time, city). We also collect standard server logs (IP address, browser type) for security and performance monitoring.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">2. How We Use Your Data</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            Your data is used exclusively to provide the Service — generating horoscopes, birth charts, and personalised astrological content. If you opted in, we may send you a periodic newsletter. We do not sell, rent, or share your personal data with third parties for marketing purposes.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">3. Email Communications</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            We send transactional emails (email confirmation, password reset). If you consented to marketing communications during registration, we may also send astrological newsletters. You may unsubscribe at any time via the link in any email or through your Profile settings.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">4. Data Retention</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            We retain your data for as long as your account is active. When you delete your account, all personal data and associated birth charts are permanently deleted within 30 days.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">5. Your Rights (GDPR)</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            If you are in the EU/EEA, you have the right to access, correct, export, or delete your personal data at any time. You may exercise these rights through your Profile page or by contacting us directly.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">6. Cookies</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            We use a single session cookie for authentication and a localStorage key for your theme preference. We do not use tracking or advertising cookies.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">7. Contact</h2>
        <p style="color:var(--theme-muted);margin-bottom:0">
            For any privacy-related questions or data requests, contact us at <a href="mailto:hello@stellaromens.com" style="color:#6a329f">hello@stellaromens.com</a>.
        </p>

    </div>

@endsection
