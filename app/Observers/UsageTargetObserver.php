<?php

namespace App\Observers;


use App\Cache\UsageTargetCacheTag;
use Illuminate\Support\Facades\Cache;

trait UsageTargetObserver
{
    use UsageTargetCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::tags(self::getUsageTargetTag())->flush();
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                Cache::tags(self::getUsageTargetTag())->flush();
            }
        });

        static::deleted(function ($model) {
            Cache::tags(self::getUsageTargetTag())->flush();
        });
    }
}
