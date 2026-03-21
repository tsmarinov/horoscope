<?php

namespace App\Http\Controllers;

use App\Services\SunSignHoroscopeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SunSignController extends Controller
{
    public const SIGNS = [
        'aries'       => ['glyph' => '♈', 'emoji' => '🐏', 'element' => 'Fire',  'dates' => 'Mar 21 – Apr 19'],
        'taurus'      => ['glyph' => '♉', 'emoji' => '🐂', 'element' => 'Earth', 'dates' => 'Apr 20 – May 20'],
        'gemini'      => ['glyph' => '♊', 'emoji' => '👥', 'element' => 'Air',   'dates' => 'May 21 – Jun 20'],
        'cancer'      => ['glyph' => '♋', 'emoji' => '🦀', 'element' => 'Water', 'dates' => 'Jun 21 – Jul 22'],
        'leo'         => ['glyph' => '♌', 'emoji' => '🦁', 'element' => 'Fire',  'dates' => 'Jul 23 – Aug 22'],
        'virgo'       => ['glyph' => '♍', 'emoji' => '🌾', 'element' => 'Earth', 'dates' => 'Aug 23 – Sep 22'],
        'libra'       => ['glyph' => '♎', 'emoji' => '⚖️', 'element' => 'Air',   'dates' => 'Sep 23 – Oct 22'],
        'scorpio'     => ['glyph' => '♏', 'emoji' => '🦂', 'element' => 'Water', 'dates' => 'Oct 23 – Nov 21'],
        'sagittarius' => ['glyph' => '♐', 'emoji' => '🏹', 'element' => 'Fire',  'dates' => 'Nov 22 – Dec 21'],
        'capricorn'   => ['glyph' => '♑', 'emoji' => '🐐', 'element' => 'Earth', 'dates' => 'Dec 22 – Jan 19'],
        'aquarius'    => ['glyph' => '♒', 'emoji' => '🏺', 'element' => 'Air',   'dates' => 'Jan 20 – Feb 18'],
        'pisces'      => ['glyph' => '♓', 'emoji' => '🐟', 'element' => 'Water', 'dates' => 'Feb 19 – Mar 20'],
    ];

    public function index(Request $request, SunSignHoroscopeService $service): View|RedirectResponse
    {
        $today    = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $date     = $request->filled('date')
            ? (Carbon::createFromFormat('Y-m-d', $request->input('date'))?->startOfDay() ?? $today)
            : $today;

        $adminIps    = array_filter(array_map('trim', explode(',', env('ADMIN_IPS', ''))));
        $adminEmails = array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', ''))));
        $isAdmin     = in_array($request->ip(), $adminIps)
                    || (!empty($adminEmails) && $request->user() && in_array($request->user()->email, $adminEmails));

        if (!$isAdmin && $date->gt($tomorrow)) {
            return Redirect::route('sun-sign.index');
        }

        if ($date->lt($today)) {
            $hasContent = \App\Models\SunSignHoroscope::whereDate('date', $date->toDateString())->exists();
            if (!$hasContent) {
                return Redirect::route('sun-sign.index');
            }
        }

        return view('horoscope.sun-sign.index', [
            'signs'      => self::SIGNS,
            'date'       => $date,
            'horoscopes' => $service->getForDate($date, app()->getLocale()),
            'isAdmin'    => $isAdmin,
        ]);
    }

}
