<?php


namespace App\Observers;


use App\Cache\Policy\PolicyCacheTag;
use Illuminate\Support\Facades\Cache;

trait PolicyObserver
{
    use PolicyCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::tags(self::getPolicyCacheTag())->flush();
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                Cache::tags(self::getPolicyCacheTag())->flush();
            }
        });

        static::deleted(function ($model) {
            Cache::tags(self::getPolicyCacheTag())->flush();
        });
    }
}
