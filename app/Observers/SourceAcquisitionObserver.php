<?php

namespace App\Observers;


use App\Cache\SourceAcquisitionCacheTag;
use Illuminate\Support\Facades\Cache;

trait SourceAcquisitionObserver
{
    use SourceAcquisitionCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::tags(self::getSourceAcquisitionTag())->flush();
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                Cache::tags(self::getSourceAcquisitionTag())->flush();
            }
        });

        static::deleted(function ($model) {
            Cache::tags(self::getSourceAcquisitionTag())->flush();
        });
    }
}
