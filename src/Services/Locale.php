<?php

namespace Omadonex\LaravelLocale\Services;

use Omadonex\LaravelLocale\Interfaces\ILocale;

class Locale implements ILocale
{
    const ENTRY_AUTH = 'auth';
    const ENTRY_APP = 'app';

    private $app;
    private $language;
    private $modules;
    private $langList;
    private $currencyList;

    protected $entriesModules = [];

    public function __construct($modules = [])
    {
        $this->app = app();
        $this->modules = $modules;

        $this->langList = $this->getLangSupportedList();
        $this->currencyList = $this->getCurrencySupportedList();
    }

    /**
     * @return string
     */
    public function getLangDefault(): string
    {
        return config('app.locale');
    }

    /**
     * @return string
     */
    public function getLangCurrent(): string
    {
        return $this->app->getLocale();
    }

    /**
     * @return string
     */
    public function getCurrencyDefault(): string
    {
        return config('omx.locale.currencyDefault');
    }

    /**
     * @return array
     */
    protected function getLangSupportedList(): array
    {
        return config('omx.locale.langList');
    }

    /**
     * @return array
     */
    protected function getCurrencySupportedList(): array
    {
        return config('omx.locale.currencyList');
    }

    /**
     * @param string|null $langTrans
     * @return string
     */
    private function getLangTransFact(string $langTrans = null): string
    {
        if (($langTrans === null) || !in_array($langTrans, $this->langList)) {
            return $this->getLangDefault();
        }

        return $langTrans;
    }

    /**
     * @param array $langList
     * @param string|null $langTrans
     * @param bool $addNative
     *
     * @return array
     */
    public function getLangList(array $langList = [], string $langTrans = null, bool $addNative = true): array
    {
        $langList = $langList ? array_intersect($langList, $this->langList) : $this->langList;
        $langTrans = $this->getLangTransFact($langTrans);

        $list = [];
        foreach ($langList as $lang) {
            $item = [
                'lang' => $lang,
                'name' => config("omx.locale.{$langTrans}.lang")[$lang],
            ];

            if ($addNative) {
                $item['native'] = config("omx.locale.{$lang}.lang")[$lang];
            }

            $list[] = $item;
        }

        return $list;
    }

    public function getCountryList(string $langTrans = null): array
    {
        $langTrans = $this->getLangTransFact($langTrans);

        return config("omx.locale.{$langTrans}.country");
    }

    public function getCurrencyList(array $currencyList = [], string $langTrans = null, $addNative = true): array
    {
        $langTrans = $this->getLangTransFact($langTrans);

        $list = [];
        foreach ($langList as $lang) {
            $item = [
                'lang' => $lang,
                'name' => config("omx.locale.{$langTrans}.lang")[$lang],
            ];

            if ($addNative) {
                $item['native'] = config("omx.locale.{$lang}.lang")[$lang];
            }

            $list[] = $item;
        }

        return $list;

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