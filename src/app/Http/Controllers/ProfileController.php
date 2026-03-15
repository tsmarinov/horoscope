<?php

namespace App\Http\Controllers;

use App\Models\DeletedAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $profiles = $user->profile()->with('birthCity')->latest()->limit(5)->get();

        return view('profile.index', compact('user', 'profiles'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update(['name' => $request->name]);

        return back()->with('status', 'name_updated');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ], [
            'confirm.in' => 'Please type DELETE to confirm.',
        ]);

        $user = $request->user();

        // Audit log — before wiping the account
        DeletedAccount::create([
            'event'         => 'deleted',
            'email'         => $user->email,
            'registered_at' => $user->created_at,
            'deleted_at'    => now(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('home')->with('status', 'account_deleted');
    }
}
