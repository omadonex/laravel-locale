<?php

namespace Omadonex\LaravelLocale\Traits;

trait TranslateTrait
{
    public function translates()
    {
        return $this->hasMany(get_class() . 'Translate', 'model_id');
    }

    public function getTranslate($lang = null, $defaultLangProp = null)
    {
        $langKey = $lang ?: app('locale')->getLangCurrent();
        $filtered = $this->translates->filter(function ($value, $key) use ($langKey) {
            return $value->lang === $langKey;
        });

        if ($filtered->count()) {
            return $filtered->first();
        }

        $defaultLangKey = app('locale')->getLangDefault();
        if ($defaultLangProp && property_exists($this, $defaultLangProp)) {
            $defaultLangKey = $this->$defaultLangProp;
        }

        return $this->translates->filter(function ($value, $key) use ($defaultLangKey) {
            return $value->lang === $defaultLangKey;
        })->first();
    }

    public function hasTranslateForLang($lang = null)
    {
        $langKey = $lang ?: app('locale')->getLangCurrent();

        return in_array($langKey, $this->translates->pluck('lang')->all());
    }

    public function getAvailableLangList()
    {
        return app('locale')->getLangList($this->translates->pluck('lang')->all());
    }
}
