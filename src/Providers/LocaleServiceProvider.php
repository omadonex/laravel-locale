<?php

namespace Omadonex\LaravelLocale\Providers;

use Illuminate\Support\ServiceProvider;
use Omadonex\LaravelLocale\Commands\Initialize;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $pathRoot = realpath(__DIR__.'/../..');

        $this->loadTranslationsFrom("{$pathRoot}/resources/lang", 'locale');

        $this->publishes([
            "{$pathRoot}/config/locale.php" => config_path('omx/locale.php'),
        ], 'config');
        $this->publishes([
            "{$pathRoot}/resources/lang" => resource_path('lang/vendor/locale'),
        ], 'translations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Initialize::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
