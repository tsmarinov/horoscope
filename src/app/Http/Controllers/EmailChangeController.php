<?php

namespace App\Http\Controllers;

use App\Models\DeletedAccount;
use App\Notifications\ConfirmEmailChangeNotification;
use Illuminate\Http\Request;

class EmailChangeController extends Controller
{
    public function request(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = $request->user();

        if ($request->email === $user->email) {
            return back()->withErrors(['email' => 'That is already your current email address.']);
        }

        $user->update(['pending_email' => $request->email]);

        if (config('mail.enabled', false)) {
            // Notify the NEW address
            $user->routeNotificationForMail = fn() => $request->email;
            $user->notify(new ConfirmEmailChangeNotification($request->email));
        }

        return back()->with('status', 'email_change_sent');
    }

    public function confirm(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired confirmation link.');
        }

        $user = $request->user();

        if (! $user->pending_email) {
            return redirect()->route('profile')->with('status', 'email_already_confirmed');
        }

        // Audit log — record old email
        DeletedAccount::create([
            'event'         => 'email_changed',
            'email'         => $user->email,
            'registered_at' => $user->created_at,
            'deleted_at'    => now(),
            'meta'          => ['new_email' => $user->pending_email],
        ]);

        $user->update([
            'email'              => $user->pending_email,
            'pending_email'      => null,
            'email_confirmed_at' => now(),
        ]);

        return redirect()->route('profile')->with('status', 'email_changed');
    }

    public function cancel(Request $request)
    {
        $request->user()->update(['pending_email' => null]);

        return back()->with('status', 'email_change_cancelled');
    }
}
