<?php

namespace App\Http\Controllers;

use App\Facades\AspectCalculator;
use App\Models\Profile;

class NatalController extends Controller
{
    public function show(Profile $profile)
    {
        abort_if($profile->user_id !== auth()->id(), 403);

        $profile->loadMissing('birthCity');

        $chart = AspectCalculator::calculate($profile);

        return view('natal.show', compact('profile', 'chart'));
    }
}
