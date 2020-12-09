<?php

namespace Omadonex\LaravelLocale\Services;

use Omadonex\LaravelLocale\Interfaces\ILocaleService;
use Omadonex\LaravelSupport\Classes\Utils\UtilsCustom;

class LocaleService implements ILocaleService
{
    const ENTRY_AUTH = 'auth';
    const ENTRY_APP = 'app';

    private $app;
    private $modules;
    private $langList;
    private $langAllList;
    private $currencyList;

    protected $entriesModules = [];

    /**
     * Locale constructor.
     * @param array $modules
     */
    public function __construct($modules = [])
    {
        $this->app = app();
        $this->modules = $modules;

        $this->langList = $this->getLangSupportedList();
        $this->langAllList = config('omx.locale.lang');
        $this->currencyList = $this->getCurrencySupportedList();
    }

    /**
     * @return string
     */
    public function getLangDefault(): string
    {
        return config('app.fallback_locale');
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
     * @param string|null $lang
     *
     * @return string
     */
    private function getLangFact(string $lang = null): string
    {
        if (($lang === null) || !$this->isLangSupported($lang)) {
            return $this->getLangDefault();
        }

        return $lang;
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
        $langTrans = $this->getLangFact($langTrans);

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

    /**
     * @param array $currencyList
     * @param string|null $langTrans
     *
     * @return array
     */
    public function getCurrencyList(array $currencyList = [], string $langTrans = null): array
    {
        $currencyList = $currencyList ? array_intersect($currencyList, $this->currencyList) : $this->currencyList;
        $langTrans = $this->getLangFact($langTrans);

        $list = [];
        foreach ($currencyList as $currency) {
            $list[] = [
                'currency' => $currency,
                'name' => config("omx.locale.{$langTrans}.currency")[$currency],
            ];
        }

        return $list;
    }

    /**
     * @param string|null $langTrans
     *
     * @return array
     */
    public function getCountryList(string $langTrans = null): array
    {
        $langTrans = $this->getLangFact($langTrans);

        return config("omx.locale.{$langTrans}.country");
    }

    /**
     * @param string|null $lang
     */
    public function setLang(string $lang = null): void
    {
        $lang = $this->getLangFact($lang);
        if ($lang !== $this->getLangCurrent()) {
            $this->app->setLocale($lang);
            //Carbon::setLocale($language);
        }
    }

    /**
     * @return string|null
     */
    public function setLangFromRoute(): ?string
    {
        $lang = $this->app->request->segment(1);
        if ($lang && $this->isLangCorrect($lang)) {
            $this->setLang($this->isLangSupported($lang) ? $lang : null);

            return $lang;
        }

        $this->setLang();

        return null;
    }

    /**
     * @param string $lang
     * @return bool
     */
    public function isLangCorrect(string $lang): bool
    {
        return in_array($lang, $this->langAllList);
    }

    /**
     * @param string $lang
     * @return bool
     */
    public function isLangSupported(string $lang): bool
    {
        return in_array($lang, $this->langList);
    }

    /**
     * @return array
     */
    public function getLangAllList(): array
    {
        return $this->langAllList;
    }

    /**
     * @param string $url
     * @return string
     */
    public function getUrlWithoutLang(string $url): string
    {
        $parsed = parse_url($url);
        if (!array_key_exists('path', $parsed)) {
            return $url;
        }

        $segments = explode('/', $parsed['path']);
        $lang = $segments[1];

        if ($this->isLangCorrect($lang)) {
            if (count($segments) > 2) {
                return preg_replace("/\/{$lang}\//", '/', $url, 1);
            }

            return preg_replace("/\/{$lang}/", '', $url, 1);
        }

        return $url;
    }

    /**
     * @param string $currentUrl
     * @return array
     */
    public function getRouteLangList(string $currentUrl): array
    {
        $currentUrlWithoutLang = $this->getUrlWithoutLang($currentUrl);
        $parsed = parse_url($currentUrlWithoutLang);
        $path = $parsed['path'] ?? '';

        $list = [];
        foreach ($this->getLangList() as $langItem) {
            if ($langItem['lang'] !== $this->getLangDefault()) {
                $parsed['path'] = "/{$langItem['lang']}{$path}";
            } else {
                $parsed['path'] = $path;
            }

            $list[] = [
                'lang' => $langItem['lang'],
                'name' => $langItem['native'],
                'url' => UtilsCustom::buildUrl($parsed),
                'flag' => $this->getFlag($langItem['lang']),
            ];
        }

        return $list;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     *
     * @return string
     */
    public function route(string $name, array $parameters = [], $absolute = true): string
    {
        $url = route($name, $parameters, $absolute);

        $lang = $this->getLangCurrent();
        if ($lang === $this->getLangDefault()) {
            return $url;
        }

        $parsed = parse_url($url);
        $parsed['path'] = "/{$lang}{$parsed['path']}";

        return UtilsCustom::buildUrl($parsed);
    }

    /**
     * @param string $lang
     *
     * @return string
     */
    public function getFlag(string $lang): string
    {
        switch ($lang) {
            case 'en': return 'us';
        }

        return $lang;
    }

    /**
     * @return string
     */
    public function getFlagCurrent(): string
    {
        return $this->getFlag($this->getLangCurrent());
    }

    /*
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
    */
}