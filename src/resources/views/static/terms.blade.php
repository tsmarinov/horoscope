@extends('layouts.app')

@section('title', 'Terms & Conditions')
@section('description', 'Stellar Omens Terms & Conditions.')
@section('main_class', 'page-wrap-narrow')

@section('content')

    <div class="page-hero-static">
        <h1 class="font-display page-title page-title-v">
            Terms &amp; Conditions
        </h1>
        <p class="page-subtitle-sm">Last updated: {{ date('F j, Y') }}</p>
    </div>

    <div class="card static-card">

        <h2>1. Acceptance of Terms</h2>
        <p>
            By accessing or using Stellar Omens ("the Service"), you agree to be bound by these Terms &amp; Conditions. If you do not agree, please do not use the Service.
        </p>

        <h2>2. Nature of the Service</h2>
        <p>
            Stellar Omens provides astrological readings, horoscopes, and birth chart interpretations for entertainment and informational purposes only. The content should not be used as a substitute for professional advice — medical, legal, financial, or otherwise. Astrological interpretations are not predictions of future events.
        </p>

        <h2>3. Account Registration</h2>
        <p>
            You must provide accurate and complete information when creating an account. You are responsible for maintaining the confidentiality of your credentials and for all activity under your account. You must be at least 16 years old to register.
        </p>

        <h2>4. User Data</h2>
        <p>
            You retain ownership of the birth data and personal information you provide. By submitting this data, you grant Stellar Omens the right to use it solely to generate and display your astrological content. We do not sell your personal data to third parties.
        </p>

        <h2>5. Intellectual Property</h2>
        <p>
            All content, design, and software on Stellar Omens is the property of Stellar Omens and may not be reproduced, distributed, or used without written permission.
        </p>

        <h2>6. Limitation of Liability</h2>
        <p>
            Stellar Omens is provided "as is" without warranties of any kind. We are not liable for any direct, indirect, or consequential damages arising from your use of the Service.
        </p>

        <h2>7. Changes to These Terms</h2>
        <p>
            We may update these Terms from time to time. Continued use of the Service after changes constitutes acceptance of the new Terms. We will notify registered users by email of material changes.
        </p>

    </div>

    <p class="static-contact">
        Questions? Contact us at <a href="mailto:hello@stellaromens.com" class="text-accent">hello@stellaromens.com</a>
    </p>

@endsection
