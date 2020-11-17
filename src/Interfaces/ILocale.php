<?php

namespace Omadonex\LaravelLocale\Interfaces;

interface ILocale
{
    /**
     * Возвращает страну по умолчанию
     * @return mixed
     */
    public function getDefaultCountry();

    /**
     * Возвращает валюту по умолчанию
     * @return mixed
     */
    public function getDefaultCurrency();

    /**
     * Возвращает язык по умолчанию
     * @return mixed
     */
    public function getDefaultLanguage();

    /**
     * Возвращает текущую страну
     * @return mixed
     */
    public function getCurrCountry();

    /**
     * Возвращает текущий язык
     * @return mixed
     */
    public function getCurrLanguage();

    /**
     * Возвращает список доступных стран с мета информацией (либо все доступные страны, либо только с подходящими ключами)
     * @param array $languageKeys
     * @return mixed
     */
    public function getCountryList($countryKeys = []);

    /**
     * Возвращает список доступных валют с мета информацией (либо все доступные валюты, либо только с подходящими ключами)
     * @param array $languageKeys
     * @return mixed
     */
    public function getCurrencyList();

    /**
     * Возвращает список доступных языков с мета информацией (либо все доступные языки, либо только с подходящими ключами)
     * @param array $languageKeys
     * @return mixed
     */
    public function getLanguageList($languageKeys = []);

    /**
     * Возвращает нативное название страны
     * @param null $language
     * @return mixed
     */
    public function getCountryNative($country = null);

    /**
     * Возвращает нативное название языка
     * @param null $language
     * @return mixed
     */
    public function getLanguageNative($language = null);

    /**
     * Устанавливает текущий язык
     * @param $language
     * @return mixed
     */
    public function setLanguage($language);

    /**
     * Возвращает данные переводов для App Entry (в зависимости от параметра либо все, либо только текущий язык)
     * @param bool $onlyCurrLang
     * @return mixed
     */
    public function getLanguageDataApp($onlyCurrLang = true);

    /**
     * Возвращает данные переводов для Auth Entry (в зависимости от параметра либо все, либо только текущий язык)
     * @param bool $onlyCurrLang
     * @return mixed
     */
    public function getLanguageDataAuth($onlyCurrLang = true);
}