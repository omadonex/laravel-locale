<?php

namespace Omadonex\LaravelLocale\Commands;

use Illuminate\Console\Command;

class Initialize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omx:locale:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all data for locale based on config files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!file_exists(base_path('vendor/umpirsky/country-list'))) {
            $this->error('Error: "umpirsky/country-list" package is not installed!');

            return ;
        }

        if (!file_exists(base_path('vendor/umpirsky/currency-list'))) {
            $this->error('Error: "umpirsky/currency-list" package is not installed!');

            return ;
        }

        if (!file_exists(base_path('vendor/umpirsky/language-list'))) {
            $this->error('Error: "umpirsky/language-list" package is not installed!');

            return ;
        }

        $langList = config('omx.locale.langList', []);
        $currencyList = config('omx.locale.currencyList', []);

        if (!file_exists(config_path('omx/locale'))) {
            mkdir(config_path('omx/locale'));
        }

        foreach ($langList as $lang) {
            if (!file_exists(config_path("omx/locale/{$lang}"))) {
                mkdir(config_path("omx/locale/{$lang}"));
            }
            copy(base_path("vendor/umpirsky/country-list/data/{$lang}/country.php"), config_path("omx/locale/{$lang}/country.php"));

            $localizedCurrencyList = include(base_path("vendor/umpirsky/currency-list/data/{$lang}/currency.php"));
            $filteredCurrencyList = array_filter($localizedCurrencyList, function ($key) use ($currencyList) {
                return in_array($key, $currencyList);
            }, ARRAY_FILTER_USE_KEY);
            file_put_contents(config_path("omx/locale/{$lang}/currency.php"), "<?php return " . var_export($filteredCurrencyList, true) . ";");

            $localizedLangList = include(base_path("vendor/umpirsky/language-list/data/{$lang}/language.php"));
            $filteredLangList = array_filter($localizedLangList, function ($key) use ($langList) {
                return in_array($key, $langList);
            }, ARRAY_FILTER_USE_KEY);
            file_put_contents(config_path("omx/locale/{$lang}/lang.php"), "<?php return " . var_export($filteredLangList, true) . ";");
        }
    }
}
