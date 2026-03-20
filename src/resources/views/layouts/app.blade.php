<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="themeManager()"
      x-init="init()"
      :data-theme="theme">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'StellarOmens') — Stellar Omens</title>
    <meta name="description" content="@yield('description', 'Personal astrology — daily, weekly and monthly horoscopes tailored to your birth chart.')">

    @vite(['resources/css/app.scss', 'resources/js/app.js'])

    {{-- Twemoji — flat SVG emojis --}}
    <script src="https://cdn.jsdelivr.net/npm/twemoji@14.0.2/dist/twemoji.min.js" crossorigin="anonymous"></script>
</head>

<body class="min-h-screen">

    {{-- ── Navbar ────────────────────────────────────────────────────────── --}}
    <header class="navbar">
        <div class="navbar-inner">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="logo-text">
                STELLAR <span class="logo-accent">✦ OMENS</span>
            </a>

            {{-- Right controls --}}
            <div class="nav-controls">

                {{-- Desktop only: Home, Theme, Profile --}}
                <div class="navbar-desktop">

                {{-- Home --}}
                <a href="{{ route('home') }}" class="icon-btn" title="Home">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 6.5L8 2l6 4.5V14a1 1 0 01-1 1H3a1 1 0 01-1-1V6.5z"/>
                        <path d="M5.5 15V9.5h5V15"/>
                    </svg>
                </a>

                {{-- Theme toggle --}}
                <button class="icon-btn" @click="toggleTheme()" :title="theme === 'dark' ? '{{ __('ui.nav.switch_to_light') }}' : '{{ __('ui.nav.switch_to_dark') }}'">
                    <span x-show="theme === 'dark'" x-cloak>☀</span>
                    <span x-show="theme === 'light'" x-cloak>☽</span>
                    <span x-show="!theme">◐</span>
                </button>

                {{-- Profile --}}
                <div class="nav-profile">
                    <button class="icon-btn" @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" aria-label="{{ __('ui.nav.account') }}" title="{{ __('ui.nav.account') }}">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="6.5" r="3"/>
                            <path d="M2.5 16c0-3.6 2.9-5.5 6.5-5.5s6.5 1.9 6.5 5.5"/>
                        </svg>
                    </button>

                    <div class="profile-dropdown" x-show="profileOpen" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95">

                        @auth
                        <div class="pd-header">
                            <span class="pd-hello">{{ auth()->user()->name }}</span>
                            <span class="pd-email">{{ auth()->user()->email }}</span>
                        </div>
                        <div class="pd-divider"></div>
                        <a href="{{ route('profile') }}" class="pd-item" @click="profileOpen = false">
                            <span class="pd-icon">👤</span> {{ __('ui.nav.my_profile') }}
                        </a>
                        <a href="{{ route('stellar-profiles.index') }}" class="pd-item" @click="profileOpen = false">
                            <span class="pd-icon">✦</span> {{ __('ui.nav.stellar_profiles') }}
                        </a>
                        <div class="pd-divider"></div>
                        @if(Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="pd-item btn-reset">
                                <span class="pd-icon">↩</span> {{ __('ui.nav.sign_out') }}
                            </button>
                        </form>
                        @endif
                        @else
                        <div class="pd-header">
                            <span class="pd-hello">{{ __('ui.nav.hello_visitor') }}</span>
                        </div>
                        <div class="pd-divider"></div>
                        <a href="{{ route('stellar-profiles.index') }}" class="pd-item" @click="profileOpen = false">
                            <span class="pd-icon">✦</span> {{ __('ui.nav.stellar_profiles') }}
                        </a>
                        <div class="pd-divider"></div>
                        @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="pd-item" @click="profileOpen = false">
                            <span class="pd-icon">↪</span> {{ __('ui.nav.sign_in') }}
                        </a>
                        @endif
                        @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="pd-item" @click="profileOpen = false">
                            <span class="pd-icon">✦</span> {{ __('ui.nav.create_account') }}
                        </a>
                        @endif
                        @endauth

                    </div>
                </div>

                </div>{{-- /navbar-desktop --}}

                {{-- Hamburger --}}
                <button class="icon-btn" @click="menuOpen = true" title="Menu" aria-label="Open menu">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <line x1="2" y1="4.5" x2="16" y2="4.5"/>
                        <line x1="2" y1="9"   x2="16" y2="9"/>
                        <line x1="2" y1="13.5" x2="16" y2="13.5"/>
                    </svg>
                </button>

            </div>
        </div>
    </header>

    {{-- ── Mobile drawer ─────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="menuOpen" x-cloak>

            {{-- Overlay --}}
            <div class="drawer-overlay"
                 @click="menuOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>

            {{-- Drawer panel --}}
            <nav class="drawer"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 role="navigation">

                {{-- Drawer logo + close --}}
                <div class="drawer-logo">
                    <span class="logo-text logo-text-sm">STELLAR <span class="logo-accent">✦ OMENS</span></span>
                    <button class="icon-btn" @click="menuOpen = false" aria-label="Close menu">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <line x1="2" y1="2" x2="14" y2="14"/>
                            <line x1="14" y1="2" x2="2" y2="14"/>
                        </svg>
                    </button>
                </div>

                {{-- Home --}}
                <a href="{{ route('home') }}" class="drawer-item @yield('nav_home')">
                    <span class="di-icon">⌂</span> {{ __('ui.nav.home') }}
                </a>
                <div class="divider divider-sm"></div>

                {{-- Horoscopes --}}
                <div class="drawer-section">{{ __('ui.nav.horoscopes') }}</div>
                <a href="{{ route('daily.index') }}"  class="drawer-item @yield('nav_daily')">
                    <span class="di-icon">☀</span> {{ __('ui.nav.daily') }}
                </a>
                <span class="drawer-item drawer-item-disabled">
                    <span class="di-icon">📅</span> {{ __('ui.nav.weekly') }}
                </span>
                <span class="drawer-item drawer-item-disabled">
                    <span class="di-icon">🌙</span> {{ __('ui.nav.monthly') }}
                </span>
                <span class="drawer-item drawer-item-disabled">
                    <span class="di-icon">✦</span> {{ __('ui.nav.solar_return') }}
                </span>
                <a href="{{ url('/' . app()->getLocale() . '/horoscope/weekday') }}" class="drawer-item @yield('nav_weekday')">
                    <span class="di-icon">🗓</span> {{ __('ui.nav.day_of_week') }}
                </a>

                {{-- Charts --}}
                <div class="drawer-section">{{ __('ui.nav.charts') }}</div>
                <a href="{{ $natalNavUrl }}" class="drawer-item @yield('nav_natal')">
                    <span class="di-icon">♈</span> {{ __('ui.nav.natal_chart') }}
                </a>
                <span class="drawer-item drawer-item-disabled">
                    <span class="di-icon">♾</span> {{ __('ui.nav.synastry') }}
                </span>

                {{-- Tools --}}
                <div class="drawer-section">{{ __('ui.nav.tools') }}</div>
                <a href="{{ route('lunar.index') }}" class="drawer-item @yield('nav_lunar')">
                    <span class="di-icon">🌑</span> {{ __('ui.nav.lunar_calendar') }}
                </a>
                <span class="drawer-item drawer-item-disabled">
                    <span class="di-icon">℞</span> {{ __('ui.nav.retrograde') }}
                </span>
                <span class="drawer-item drawer-item-disabled">
                    <span class="di-icon">🪐</span> {{ __('ui.nav.planet_positions') }}
                </span>

                {{-- Account --}}
                <div class="drawer-section">{{ __('ui.nav.account') }}</div>
                @auth
                <a href="{{ route('profile') }}"          class="drawer-item @yield('nav_profile')">
                    <span class="di-icon">👤</span> {{ __('ui.nav.my_profile') }}
                </a>
                <a href="{{ route('stellar-profiles.index') }}" class="drawer-item @yield('nav_stellar')">
                    <span class="di-icon">✦</span> {{ __('ui.nav.stellar_profiles') }}
                </a>
                @if(Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="drawer-item btn-reset">
                        <span class="di-icon">↩</span> {{ __('ui.nav.sign_out') }}
                    </button>
                </form>
                @endif
                @else
                <a href="{{ route('stellar-profiles.index') }}" class="drawer-item @yield('nav_stellar')">
                    <span class="di-icon">✦</span> {{ __('ui.nav.stellar_profiles') }}
                </a>
                @if(Route::has('login'))
                <a href="{{ route('login') }}"    class="drawer-item">
                    <span class="di-icon">↪</span> {{ __('ui.nav.sign_in') }}
                </a>
                @endif
                @if(Route::has('register'))
                <a href="{{ route('register') }}" class="drawer-item">
                    <span class="di-icon">✦</span> {{ __('ui.nav.create_account') }}
                </a>
                @endif
                @endauth

                {{-- Theme --}}
                <div class="divider divider-sm"></div>
                <button class="drawer-item btn-reset" @click="toggleTheme()">
                    <span class="di-icon">
                        <span x-show="theme === 'dark'" x-cloak>☀</span>
                        <span x-show="theme === 'light'" x-cloak>☽</span>
                        <span x-show="!theme">◐</span>
                    </span>
                    <span x-text="theme === 'dark' ? '{{ __('ui.nav.switch_to_light') }}' : '{{ __('ui.nav.switch_to_dark') }}'">{{ __('ui.nav.theme') }}</span>
                </button>

            </nav>
        </div>
    </template>

    {{-- ── Page content ──────────────────────────────────────────────────── --}}
    <main id="page-{{ str_replace('.', '-', Route::currentRouteName() ?? 'unknown') }}"
          class="@yield('main_class', 'page-wrap')">
        @yield('content')
    </main>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <footer class="site-footer">
        <p class="footer-text">
            <span class="logo-text logo-text-xs">STELLAR <span class="logo-accent">✦ OMENS</span></span>
            &nbsp;·&nbsp;
            <a href="{{ route('terms') }}"   class="footer-link">{{ __('ui.nav.terms') }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('privacy') }}" class="footer-link">{{ __('ui.nav.privacy') }}</a>
            &nbsp;·&nbsp; {{ date('Y') }}
        </p>
    </footer>

    @stack('scripts')

    {{-- Back to top button (global) --}}
    <button id="stt" onclick="window.scrollTo({top:0,behavior:'smooth'})"
            title="{{ __('ui.nav.back_to_top') }}"
            class="scroll-top">↑</button>
    <script>
    (function(){
        var btn = document.getElementById('stt');
        window.addEventListener('scroll', function(){
            btn.style.display = window.scrollY > 400 ? 'flex' : 'none';
        }, {passive: true});
    })();
    </script>
</body>
</html>
