<?php

namespace App\Observers;


use App\Cache\GenderCacheTag;
use Illuminate\Support\Facades\Cache;

trait GenderObserver
{
    use GenderCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($gender) {
            Cache::tags(self::getGenderTag())->flush();
        });

        static::updated(function ($gender) {
            if ($gender->isDirty()) {
                Cache::tags(self::getGenderTag())->flush();
            }
        });

        static::deleted(function ($gender) {
            Cache::tags(self::getGenderTag())->flush();
        });
    }
}
