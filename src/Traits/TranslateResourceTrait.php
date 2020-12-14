<?php

namespace Omadonex\LaravelLocale\Traits;

use Omadonex\LaravelLocale\Interfaces\ILocaleService;

trait TranslateResourceTrait
{
    public function getTranslateIfLoaded($translateResourceClass, $full = true, $lang = null)
    {
        $lang = $lang ?: app('locale')->getLangCurrent();
        $data = [];

        if ($this->resource->relationLoaded('translates')) {
            $data['t'] = new $translateResourceClass($this->getTranslate($lang, ILocaleService::PROP_LANG_DEFAULT));
            if ($full) {
                $data['tHas'] = $this->hasTranslateForLang();
                $data['tList'] = $this->getAvailableLangList();
            }
        }

        return $data;
    }
}
