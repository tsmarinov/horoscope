<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['en', 'es'];
    private const DEFAULT   = 'en';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (!$locale || !in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale', self::DEFAULT);
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        if ($request->route() && $request->route()->hasParameter('locale')) {
            $request->route()->forgetParameter('locale');
        }

        return $next($request);
    }
}
