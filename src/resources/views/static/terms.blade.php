@extends('layouts.app')

@section('title', 'Terms & Conditions')
@section('description', 'Stellar Omens Terms & Conditions.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div style="padding:1.5rem 0 1rem">
        <h1 class="font-display" style="font-size:1.1rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--theme-text);margin-bottom:0.25rem">
            Terms &amp; Conditions
        </h1>
        <p style="font-size:0.82rem;color:var(--theme-muted)">Last updated: {{ date('F j, Y') }}</p>
    </div>

    <div class="card" style="font-size:0.88rem;color:var(--theme-text);line-height:1.8">

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">1. Acceptance of Terms</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            By accessing or using Stellar Omens ("the Service"), you agree to be bound by these Terms &amp; Conditions. If you do not agree, please do not use the Service.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">2. Nature of the Service</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            Stellar Omens provides astrological readings, horoscopes, and birth chart interpretations for entertainment and informational purposes only. The content should not be used as a substitute for professional advice — medical, legal, financial, or otherwise. Astrological interpretations are not predictions of future events.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">3. Account Registration</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            You must provide accurate and complete information when creating an account. You are responsible for maintaining the confidentiality of your credentials and for all activity under your account. You must be at least 16 years old to register.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">4. User Data</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            You retain ownership of the birth data and personal information you provide. By submitting this data, you grant Stellar Omens the right to use it solely to generate and display your astrological content. We do not sell your personal data to third parties.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">5. Intellectual Property</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            All content, design, and software on Stellar Omens is the property of Stellar Omens and may not be reproduced, distributed, or used without written permission.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">6. Limitation of Liability</h2>
        <p style="color:var(--theme-muted);margin-bottom:1.25rem">
            Stellar Omens is provided "as is" without warranties of any kind. We are not liable for any direct, indirect, or consequential damages arising from your use of the Service.
        </p>

        <h2 style="font-size:0.92rem;font-weight:600;margin:0 0 0.5rem">7. Changes to These Terms</h2>
        <p style="color:var(--theme-muted);margin-bottom:0">
            We may update these Terms from time to time. Continued use of the Service after changes constitutes acceptance of the new Terms. We will notify registered users by email of material changes.
        </p>

    </div>

    <p style="text-align:center;font-size:0.78rem;color:var(--theme-muted);margin-top:1rem">
        Questions? Contact us at <a href="mailto:hello@stellaromens.com" style="color:#6a329f">hello@stellaromens.com</a>
    </p>

@endsection
