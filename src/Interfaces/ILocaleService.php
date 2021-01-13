<?php

namespace Omadonex\LaravelLocale\Interfaces;

interface ILocaleService
{
    const PROP_LANG_DEFAULT = 'langDefault';

    const ENTRY_AUTH = 'auth';
    const ENTRY_ALL = 'all';

    /**
     * Returns default language key
     *
     * @return string
     */
    public function getLangDefault(): string;

    /**
     * Returns current language key
     *
     * @return string
     */
    public function getLangCurrent(): string;

    /**
     * Returns default currency key
     *
     * @return string
     */
    public function getCurrencyDefault(): string;

    /**
     * Returns language list
     *
     * @param array $langList
     * @param string|null $langTrans
     * @param bool $addNative
     *
     * @return array
     */
    public function getLangList(array $langList = [], string $langTrans = null, bool $addNative = true): array;

    /**
     * Returns currency list
     *
     * @param array $currencyList
     * @param string|null $langTrans
     *
     * @return array
     */
    public function getCurrencyList(array $currencyList = [], string $langTrans = null): array;

    /**
     * Returns country list
     *
     * @param string|null $langTrans
     *
     * @return array
     */
    public function getCountryList(string $langTrans = null): array;

    /**
     * Set current language of application
     *
     * @param string|null $lang
     */
    public function setLang(string $lang): void;
}