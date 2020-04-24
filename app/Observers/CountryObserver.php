<?php

namespace App\Observers;


use App\Cache\CountryCacheTags;
use Illuminate\Support\Facades\Cache;

trait CountryObserver
{
    use CountryCacheTags;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($country) {
            Cache::tags(self::getCountryTag())->flush();
        });

        static::updated(function ($country) {
            if ($country->isDirty()) {
                Cache::tags(self::getCountryTag())->flush();
            }
        });

        static::deleted(function ($country) {
            Cache::tags(self::getCountryTag())->flush();
        });
    }
}
