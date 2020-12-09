<?php

namespace Omadonex\LaravelLocale\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Omadonex\LaravelLocale\Interfaces\ILocaleService;

class LocaleRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var ILocaleService $localeService */
        $localeService = resolve(ILocaleService::class);
        $lang = $request->segment(1);

        if ($lang && $localeService->isLangCorrect($lang)) {
            if ($lang === $localeService->getLangDefault()) {
                session()->reflash();

                return new RedirectResponse($localeService->getUrlWithoutLang($request->fullUrl()), 302, ['Vary' => 'Accept-Language']);
            }

            if ($localeService->isLangSupported($lang)) {
                session(['lang' => $lang]);

                return $next($request);
            }

            abort(406);
        }

        return $next($request);
    }
}
