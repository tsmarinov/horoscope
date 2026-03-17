<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitySearchController;
use App\Http\Controllers\NatalController;
use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaticController;
use App\Http\Controllers\StellarProfileController;
use Illuminate\Support\Facades\Route;

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

// Natal chart
Route::middleware('auth')->group(function () {
    Route::get('/natal/{profile}',         [NatalController::class, 'show'])->name('natal.show');
    Route::get('/natal/{profile}/pdf',     [NatalController::class, 'pdf'])->name('natal.pdf');
    Route::post('/natal/{profile}/portrait', [NatalController::class, 'generatePortrait'])->name('natal.portrait');
});

// Stellar Profiles
Route::middleware('auth')->group(function () {
    Route::get('/stellar-profiles',              [StellarProfileController::class, 'index'])->name('stellar-profiles.index');
    Route::post('/stellar-profiles',             [StellarProfileController::class, 'store'])->name('stellar-profiles.store');
    Route::patch('/stellar-profiles/{stellarProfile}', [StellarProfileController::class, 'update'])->name('stellar-profiles.update');
    Route::delete('/stellar-profiles/{stellarProfile}', [StellarProfileController::class, 'destroy'])->name('stellar-profiles.destroy');
});

// Premium
Route::middleware('auth')->group(function () {
    Route::post('/premium/use', [\App\Http\Controllers\PremiumController::class, 'use'])->name('premium.use');
});

// Email change
Route::middleware('auth')->group(function () {
    Route::post('/email/change',         [EmailChangeController::class, 'request'])->name('email.change.request');
    Route::get('/email/change/confirm/{id}', [EmailChangeController::class, 'confirm'])
        ->name('email.change.confirm')
        ->middleware('signed');
    Route::post('/email/change/cancel',  [EmailChangeController::class, 'cancel'])->name('email.change.cancel');
});
