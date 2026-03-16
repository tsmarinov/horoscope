<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremiumController extends Controller
{
    /**
     * Record a premium generation request and return updated counts.
     * Called via fetch() from the premium button partial.
     */
    public function use(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        if (! config('premium.enabled')) {
            return response()->json(['error' => 'premium_disabled'], 403);
        }

        if (! $user->isPremium()) {
            return response()->json(['error' => 'not_premium'], 403);
        }

        if ($user->premiumRemaining() <= 0) {
            return response()->json(['error' => 'limit_reached', 'limit' => config('premium.monthly_limit')], 403);
        }

        $user->incrementPremiumUsage();

        return response()->json([
            'used'      => $user->premiumUsageThisMonth(),
            'remaining' => $user->premiumRemaining(),
            'limit'     => config('premium.monthly_limit'),
        ]);
    }
}
