<?php


namespace App\Observers;


use App\Cache\ReportCacheTag;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

trait ReportObserver
{
    use ReportCacheTag, CacheKeysTrait;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::tags(self::getReportCacheTagByAttribute('Filter'))->flush();

            $cacheTag = $model->creator_id;
            Cache::tags(self::getReportCacheTagByAttribute("Creator|$cacheTag"))->flush();
        });
    }
}
