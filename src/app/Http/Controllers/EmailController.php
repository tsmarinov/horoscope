<?php

namespace App\Http\Controllers;

use App\Notifications\ConfirmEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailController extends Controller
{
    public function verify(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if ($user->id !== $id) {
            abort(403);
        }

        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired confirmation link.');
        }

        if (! $user->email_confirmed_at) {
            $user->update(['email_confirmed_at' => now()]);
        }

        return redirect()->route('profile')->with('status', 'email_confirmed');
    }

    public function resend(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user->email_confirmed_at) {
            return back()->with('status', 'already_confirmed');
        }

        $key = 'resend-confirm:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['resend' => "Too many requests. Try again in {$seconds} seconds."]);
        }

        RateLimiter::hit($key, 300); // 5-minute window

        $user->notify(new ConfirmEmailNotification());

        return back()->with('status', 'confirmation_sent');
    }
}
