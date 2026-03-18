<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function currentGuest(): ?\App\Models\Guest
    {
        return request()->attributes->get('guest');
    }

    protected function ownsProfile(\App\Models\Profile $profile): bool
    {
        if (auth()->check()) {
            return $profile->user_id === auth()->id();
        }
        $guest = $this->currentGuest();
        return $guest !== null && $profile->guest_id === $guest->id;
    }
}
