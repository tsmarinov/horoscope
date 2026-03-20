<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreLaunchMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only active in production
        if (!app()->environment('production')) {
            return $next($request);
        }

        $launchAt = config('app.launch_at');
        $timezone = config('app.launch_timezone', 'Europe/Sofia');

        // No launch date configured — site is open
        if (!$launchAt) {
            return $next($request);
        }

        $launch = \Carbon\Carbon::parse($launchAt, $timezone);

        // Past launch time — site is open
        if (now($timezone)->greaterThanOrEqualTo($launch)) {
            return $next($request);
        }

        // Check if requester IP is whitelisted
        $allowedIps = array_filter(array_map('trim', explode(',', config('app.preview_ips', ''))));
        if (in_array($request->ip(), $allowedIps)) {
            return $next($request);
        }

        // Show coming soon page
        return response()->view('coming-soon', [
            'launch' => $launch,
        ], 200);
    }
}
