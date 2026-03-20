@extends('layouts.app')

@section('title', 'Privacy Policy')
@section('description', 'Stellar Omens Privacy Policy.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div class="page-hero-static">
        <h1 class="font-display page-title page-title-v">
            Privacy Policy
        </h1>
        <p class="page-subtitle-sm">Last updated: {{ date('F j, Y') }}</p>
    </div>

    <div class="card static-card">

        <h2>1. What Data We Collect</h2>
        <p>
            We collect the information you provide when creating an account: name, email address, and birth data (date, time, city). We also collect standard server logs (IP address, browser type) for security and performance monitoring.
        </p>

        <h2>2. How We Use Your Data</h2>
        <p>
            Your data is used exclusively to provide the Service — generating horoscopes, birth charts, and personalised astrological content. If you opted in, we may send you a periodic newsletter. We do not sell, rent, or share your personal data with third parties for marketing purposes.
        </p>

        <h2>3. Email Communications</h2>
        <p>
            We send transactional emails (email confirmation, password reset). If you consented to marketing communications during registration, we may also send astrological newsletters. You may unsubscribe at any time via the link in any email or through your Profile settings.
        </p>

        <h2>4. Data Retention</h2>
        <p>
            We retain your data for as long as your account is active. When you delete your account, all personal data and associated birth charts are permanently deleted within 30 days.
        </p>

        <h2>5. Your Rights (GDPR)</h2>
        <p>
            If you are in the EU/EEA, you have the right to access, correct, export, or delete your personal data at any time. You may exercise these rights through your Profile page or by contacting us directly.
        </p>

        <h2>6. Cookies</h2>
        <p>
            We use a single session cookie for authentication and a localStorage key for your theme preference. We do not use tracking or advertising cookies.
        </p>

        <h2>7. Contact</h2>
        <p>
            For any privacy-related questions or data requests, contact us at <a href="mailto:hello@stellaromens.com" class="text-accent">hello@stellaromens.com</a>.
        </p>

    </div>

@endsection
