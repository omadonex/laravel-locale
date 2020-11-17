<?php

namespace Omadonex\LaravelLocale\Services;

use Omadonex\LaravelLocale\Interfaces\ILocale;

class Locale implements ILocale
{
    const ENTRY_AUTH = 'auth';
    const ENTRY_APP = 'app';

    private $language;
    private $modules;

    protected $entriesModules = [];

    public function __construct($modules = [])
    {
        $this->modules = $modules;
        $this->language = null;
    }

    public function getDefaultCountry()
    {
        return config('omx.locale.country.default');
    }

    public function getDefaultCurrency()
    {
        return config('omx.locale.currency.default');
    }

    public function getDefaultLanguage()
    {
        return config('app.fallback_locale');
    }

    public function getCurrCountry()
    {
        $localeKey = $this->getCurrLanguage();
        if (!in_array($localeKey, array_keys($this->getSupportedCountries()))) {
            return $this->getDefaultCountry();
        }

        return $localeKey;
    }

    public function getCurrLanguage()
    {
        return $this->language;
    }

    public function getCountryList($countryKeys = [])
    {
        $countries = $this->getSupportedCountries();
        $keys = ($countryKeys === []) ? array_keys($countries) : $countryKeys;
        $countryList = [];
        foreach ($keys as $key) {
            $countryList[] = [
                'key' => $key,
                'name' => $countries[$key]['name'],
                'native' => $countries[$key]['native'],
            ];
        }

        return $countryList;
    }

    public function getCurrencyList()
    {
        return $this->getSupportedCurrencies();
    }

    public function getLanguageList($languageKeys = [])
    {
        $langs = $this->getSupportedLocales();
        $keys = ($languageKeys === []) ? array_keys($langs) : array_intersect($languageKeys, array_keys($langs));
        $languageList = [];
        foreach ($keys as $key) {
            $languageList[] = [
                'key' => $key,
                'name' => $langs[$key]['name'],
                'native' => $langs[$key]['native'],
            ];
        }

        return $languageList;
    }

    public function getCountryNative($country = null)
    {
        return $this->getSupportedCountries()[$country ?: $this->getCurrCountry()]['native'];
    }

    public function getLanguageNative($language = null)
    {
        return $this->getSupportedLocales()[$language ?: $this->getCurrLanguage()]['native'];
    }

    public function setLanguage($language)
    {
        if ($language) {
            if (!in_array($language, array_keys($this->getSupportedLocales()))) {
                $language = $this->getDefaultLanguage();
            }

            $this->language = $language;
            $this->setCurrentLocale($language);

            Carbon::setLocale($language);
        }
    }

    public function getLanguageDataApp($onlyCurrLang = true)
    {
        return $this->getLanguageDataEntry(self::ENTRY_APP, $onlyCurrLang);
    }

    public function getLanguageDataAuth($onlyCurrLang = true)
    {
        return $this->getLanguageDataEntry(self::ENTRY_AUTH, $onlyCurrLang);
    }

    protected function getSupportedCountries()
    {
        return config('omx.locale.country.supported');
    }

    protected function getSupportedCurrencies()
    {
        return config('omx.locale.currency.supported');
    }

    protected function getSupportedLocales()
    {
        return config('omx.locale.lang.supported');
    }

    protected function setCurrentLocale($language)
    {
        App::setLocale($language);
    }

    protected function getLanguageDataEntry($entry, $onlyCurrLang = true)
    {
        $currLang = $this->getCurrLanguage();
        $translations = [];
        $data['currLang'] = $currLang;
        $data['langList'] = $this->getLanguageList();

        $entryModules = $this->getEntriesModules($entry);
        if (count($entryModules)) {
            if ($entryModules[0] === '*') {
                $entryModules = array_keys($this->modules);
            } elseif ($entryModules[0] === '^') {
                $entryModules = array_diff(array_keys($this->modules), array_slice($entryModules, 0));
            }
        }

        $languages = $onlyCurrLang ? [$currLang] : array_keys($this->getSupportedLocales());
        foreach ($languages as $language) {
            $translations[$language]['app'] = $this->getTranslations($language);
            $translations[$language]['vendor'] = $this->getTranslationsVendor($language);
            foreach ($entryModules as $moduleKey) {
                $module = $this->modules[$moduleKey];
                $trans = $this->getTranslations($language, $module);
                if (!is_null($trans)) {
                    $translations[$language][$module->getLowerName()] = $trans;
                }
            }
        }

        $data['translations'] = $translations;

        return $data;
    }

    private function getEntriesModules($entry = null)
    {
        $data = array_merge($this->entriesModules, [
            self::ENTRY_AUTH => [],
            self::ENTRY_APP => ['*'],
        ]);

        if ($entry && array_key_exists($entry, $data)) {
            return $data[$entry];
        }

        return $data;
    }

    private function getTranslations($lang, $module = null)
    {
        $trans = [];
        if (is_null($module)) {
            $pathPart = "lang/{$this->getDefaultLanguage()}";
            $path = resource_path($pathPart);
        } else {
            $pathPart = "Resources/lang/{$this->getDefaultLanguage()}";
            $path = $module->getExtraPath($pathPart);
        }

        if (is_dir($path)) {
            $files = scandir($path);
            unset($files[0]);
            unset($files[1]);
            foreach ($files as $file) {
                $name = explode('.', $file)[0];
                $filePathPart = "{$pathPart}/{$file}";
                $filePath = $module ? $module->getExtraPath($filePathPart) : resource_path($filePathPart);
                $fileTranslations = include $filePath;
                $trans[$name] = $this->getTranslationsArray($lang, $fileTranslations, $name, $module);
            }
        }

        return $trans;
    }

    private function getTranslationsArray($lang, $arr, $transKey, $module)
    {
        $transArr = [];
        foreach ($arr as $key => $value) {
            $newTransKey = "{$transKey}.{$key}";

            if (is_array($value)) {
                $trans = $this->getTranslationsArray($lang, $value, $newTransKey, $module);
            } else {
                $prefix = is_null($module) ? '' : "{$module->getLowerName()}::";
                $trans = trans($prefix . $newTransKey, [], $lang);
            }

            $transArr[$key] = $trans;
        }

        return $transArr;
    }

    private function getTranslationsVendor($lang)
    {
        $vendorPath = resource_path("lang/vendor");
        $trans = [];
        if (is_dir($vendorPath)) {
            $packages = scandir($vendorPath);
            unset($packages[0]);
            unset($packages[1]);
            foreach ($packages as $dir) {
                $pathFiles = "{$vendorPath}/{$dir}/{$this->getDefaultLanguage()}";
                $trans[$dir] = [];
                if (is_dir($pathFiles)) {
                    $files = scandir($pathFiles);
                    unset($files[0]);
                    unset($files[1]);
                    foreach ($files as $file) {
                        $name = explode('.', $file)[0];
                        $filePath = "{$pathFiles}/{$file}";
                        $fileTranslations = include $filePath;
                        $trans[$dir][$name] = $this->getTranslationsArray($lang, $fileTranslations, "{$dir}::{$name}", null);
                    }
                }
            }
        }

        return $trans;
    }
}