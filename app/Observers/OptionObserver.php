<?php


namespace App\Observers;


use App\Cache\OptionCacheTag;
use Illuminate\Support\Facades\Cache;

trait OptionObserver
{
    use OptionCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::tags(self::getOptionCacheTag())->flush();
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                Cache::tags(self::getOptionCacheTag())->flush();
            }
        });

        static::deleted(function ($model) {
            Cache::tags(self::getOptionCacheTag())->flush();
        });
    }
}
