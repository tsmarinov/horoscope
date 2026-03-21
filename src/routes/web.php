<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitySearchController;
use App\Http\Controllers\DailyHoroscopeController;
use App\Http\Controllers\LunarCalendarController;
use App\Http\Controllers\NatalController;
use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaticController;
use App\Http\Controllers\StellarProfileController;
use App\Http\Controllers\SunSignController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/en/'));

Route::prefix('{locale}')
    ->where(['locale' => 'en|es'])
    ->middleware('locale')
    ->group(function () {

        Route::get('/', [HomeController::class, 'index'])->name('home');

        // Static pages
        Route::get('/terms',   [StaticController::class, 'terms'])->name('terms');
        Route::get('/privacy', [StaticController::class, 'privacy'])->name('privacy');

        // Auth
        Route::middleware('guest')->group(function () {
            Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
            Route::post('/login',    [AuthController::class, 'login']);
            Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [AuthController::class, 'register'])->name('register.store');
        });

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

        // Email confirmation
        Route::middleware('auth')->group(function () {
            Route::get('/email/verify/{id}/{hash}', [EmailController::class, 'verify'])
                ->name('email.verify')
                ->middleware('signed');
            Route::post('/email/resend', [EmailController::class, 'resend'])
                ->name('email.resend');
        });

        // Profile
        Route::middleware('auth')->group(function () {
            Route::get('/profile',    [ProfileController::class, 'index'])->name('profile');
            Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        // City search (public — no auth needed for autocomplete)
        Route::get('/api/cities', [CitySearchController::class, 'search'])->name('api.cities');

        // Natal chart — guest-accessible
        Route::get('/natal',               [NatalController::class, 'redirect'])->name('natal.index');
        Route::get('/natal/{profile}',     [NatalController::class, 'show'])->name('natal.show');
        Route::get('/natal/{profile}/pdf', [NatalController::class, 'pdf'])->name('natal.pdf');
        // Natal chart — auth-only (AI L1)
        Route::middleware('auth')->group(function () {
            Route::post('/natal/{profile}/portrait',       [NatalController::class, 'generatePortrait'])->name('natal.portrait');
            Route::get('/natal/{profile}/portrait/status', [NatalController::class, 'portraitStatus'])->name('natal.portrait.status');
        });

        // Daily horoscope — guest-accessible
        Route::get('/horoscope/personal/daily',                        [DailyHoroscopeController::class, 'redirect'])->name('daily.index');
        Route::get('/horoscope/personal/daily/{profile}/{date}/pdf',   [DailyHoroscopeController::class, 'pdf'])->name('daily.pdf');
        Route::get('/horoscope/personal/daily/{profile}/{date?}',      [DailyHoroscopeController::class, 'show'])->name('daily.show');
        // Daily horoscope — auth-only (AI L1)
        Route::middleware('auth')->group(function () {
            Route::post('/horoscope/personal/daily/{profile}/{date}/synthesis', [DailyHoroscopeController::class, 'generateSynthesis'])->name('daily.synthesis');
        });

        // Stellar Profiles — guest-accessible
        Route::get('/stellar-profiles',                       [StellarProfileController::class, 'index'])->name('stellar-profiles.index');
        Route::post('/stellar-profiles',                      [StellarProfileController::class, 'store'])->name('stellar-profiles.store');
        Route::patch('/stellar-profiles/{stellarProfile}',    [StellarProfileController::class, 'update'])->name('stellar-profiles.update');
        Route::delete('/stellar-profiles/{stellarProfile}',   [StellarProfileController::class, 'destroy'])->name('stellar-profiles.destroy');

        // Premium
        Route::middleware('auth')->group(function () {
            Route::post('/premium/use',       [\App\Http\Controllers\PremiumController::class, 'use'])->name('premium.use');
            Route::get('/premium/remaining',  [\App\Http\Controllers\PremiumController::class, 'remaining'])->name('premium.remaining');
        });

        // Email change
        Route::middleware('auth')->group(function () {
            Route::post('/email/change',         [EmailChangeController::class, 'request'])->name('email.change.request');
            Route::get('/email/change/confirm/{id}', [EmailChangeController::class, 'confirm'])
                ->name('email.change.confirm')
                ->middleware('signed');
            Route::post('/email/change/cancel',  [EmailChangeController::class, 'cancel'])->name('email.change.cancel');
        });

        // Sun-sign daily horoscope (public)
        Route::get('/horoscope/daily', [SunSignController::class, 'index'])->name('sun-sign.index');

        // Lunar calendar (public)
        Route::get('/lunar-calendar', [LunarCalendarController::class, 'redirect'])->name('lunar.index');
        Route::get('/lunar-calendar/{year}/{month}', [LunarCalendarController::class, 'show'])->name('lunar.show');
        Route::get('/lunar-calendar/{year}/{month}/pdf', [LunarCalendarController::class, 'pdf'])->name('lunar.pdf');

    });

// Admin tools
Route::get('/admin/instagram/daily/{date?}', [\App\Http\Controllers\AdminInstagramController::class, 'daily'])
    ->where('date', '\d{4}-\d{2}-\d{2}');
Route::get('/admin/instagram/daily/{date}/slide/{num}.png', [\App\Http\Controllers\AdminInstagramController::class, 'slide'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->where('num', '[1-6]');

// Redirect legacy URLs (no locale prefix) → /en/...
Route::get('{any}', function ($any) {
    if (preg_match('#^(en|es)(/|$)#', $any)) {
        abort(404);
    }
    return redirect('/en/' . $any, 301);
})->where('any', '.*');
