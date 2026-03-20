<?php

namespace App\Http\Controllers;

class StaticController extends Controller
{
    public function terms()
    {
        return view('static.terms');
    }

    public function privacy()
    {
        return view('static.privacy');
    }
}
