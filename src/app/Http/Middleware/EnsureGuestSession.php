<?php

namespace App\Http\Middleware;

use App\Models\Guest;
use Closure;
use Illuminate\Http\Request;

class EnsureGuestSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check()) {
            $guest = Guest::findOrCreateFromCookie();
            // Rolling 100-day cookie
            cookie()->queue('guest_uuid', $guest->uuid, 60 * 24 * 100);
            $request->attributes->set('guest', $guest);
        }
        return $next($request);
    }
}
