@php
$icons = [
    'aries' => '
        <line x1="50" y1="80" x2="50" y2="54"/>
        <path d="M50,54 C45,43 32,25 17,27"/>
        <path d="M50,54 C55,43 68,25 83,27"/>
    ',
    'taurus' => '
        <circle cx="50" cy="65" r="23"/>
        <path d="M27,58 C27,44 34,17 50,13 C66,17 73,44 73,58"/>
    ',
    'gemini' => '
        <line x1="35" y1="22" x2="35" y2="78"/>
        <line x1="65" y1="22" x2="65" y2="78"/>
        <path d="M20,22 Q50,36 80,22"/>
        <path d="M20,78 Q50,64 80,78"/>
    ',
    'cancer' => '
        <path d="M36,36 C36,21 55,14 68,22 C80,30 80,47 70,53"/>
        <circle cx="31" cy="39" r="7" fill="currentColor"/>
        <path d="M64,64 C64,79 45,86 32,78 C20,70 20,53 30,47"/>
        <circle cx="69" cy="61" r="7" fill="currentColor"/>
    ',
    'leo' => '
        <circle cx="42" cy="61" r="22"/>
        <path d="M64,61 C76,61 84,48 80,34 C76,22 65,20 60,28 C55,37 63,48 71,44 C79,40 79,23 69,16"/>
    ',
    'virgo' => '
        <path d="M22,18 L22,75"/>
        <path d="M22,18 Q32,8 42,18 L42,75"/>
        <path d="M42,18 Q52,8 62,18 L62,69 C62,81 74,85 80,77 C86,69 80,60 72,65"/>
    ',
    'libra' => '
        <path d="M22,53 Q50,26 78,53"/>
        <line x1="12" y1="70" x2="88" y2="70"/>
    ',
    'scorpio' => '
        <path d="M22,18 L22,75"/>
        <path d="M22,18 Q32,8 42,18 L42,75"/>
        <path d="M42,18 Q52,8 62,18 L62,68 Q64,81 77,77 L71,70 M77,77 L71,83"/>
    ',
    'sagittarius' => '
        <line x1="20" y1="80" x2="78" y2="22"/>
        <polyline points="50,22 78,22 78,50"/>
        <line x1="18" y1="50" x2="60" y2="50"/>
    ',
    'capricorn' => '
        <path d="M20,24 L44,78"/>
        <path d="M20,24 Q32,16 44,24"/>
        <line x1="44" y1="24" x2="44" y2="50"/>
        <path d="M44,50 C44,68 58,78 70,72 C84,64 84,48 74,40 C64,32 52,38 50,50 C48,62 58,70 70,66"/>
    ',
    'aquarius' => '
        <path d="M15,40 C25,30 35,50 50,40 C65,30 75,50 85,40"/>
        <path d="M15,60 C25,50 35,70 50,60 C65,50 75,70 85,60"/>
    ',
    'pisces' => '
        <path d="M60,14 C76,24 82,38 82,50 C82,62 76,76 60,86"/>
        <path d="M40,14 C24,24 18,38 18,50 C18,62 24,76 40,86"/>
        <line x1="14" y1="50" x2="86" y2="50"/>
    ',
];
@endphp
<svg viewBox="0 0 100 100"
     width="{{ $size ?? 48 }}" height="{{ $size ?? 48 }}"
     fill="none"
     stroke="currentColor"
     stroke-width="{{ $strokeWidth ?? 5.5 }}"
     stroke-linecap="round"
     stroke-linejoin="round"
     class="{{ $class ?? '' }}"
     aria-hidden="true">{!! $icons[$sign] ?? '' !!}</svg>
