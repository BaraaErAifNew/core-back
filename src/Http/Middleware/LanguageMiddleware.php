<?php

namespace ApiCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get language from various sources (priority order)
        $locale = $request->query('lang')
            ?? $request->header('Language')
            ?? Session::get('locale')
            ?? config('app.locale', 'en');

        // Normalize locale (extract language code if full locale like 'en-US')
//        if (str_contains($locale, '-')) {
//            $locale = explode('-', $locale)[0];
//        }

        // Validate locale against supported languages
//        $supportedLocales = ['ar', 'en'];
//        if (!in_array($locale, $supportedLocales)) {
//            $locale = config('app.locale', 'en');
//        }

        // Set application locale
        App::setLocale($locale);

        // Store in session for persistence
//        Session::put('locale', $locale);

        return $next($request);
    }
}

