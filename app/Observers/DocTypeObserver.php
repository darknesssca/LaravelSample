<?php

namespace App\Observers;


use App\Cache\DocTypeCacheTag;
use Illuminate\Support\Facades\Cache;

trait DocTypeObserver
{
    use DocTypeCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($docType) {
            Cache::tags(self::getDocTypeTag())->flush();
        });

        static::updated(function ($docType) {
            if ($docType->isDirty()) {
                Cache::tags(self::getDocTypeTag())->flush();
            }
        });

        static::deleted(function ($docType) {
            Cache::tags(self::getDocTypeTag())->flush();
        });
    }
}
