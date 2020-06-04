<?php


namespace App\Observers;


use App\Cache\ReportCacheTag;
use App\Models\Report;
use Benfin\Api\GlobalStorage;
use Benfin\Cache\CacheKeysTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait ReportObserver
{
    use ReportCacheTag, CacheKeysTrait;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->create_date = Carbon::now();
            $model->creator_id = GlobalStorage::getUserId();
            $model->is_payed = false;
        });
        static::created(function ($model) {
            Cache::tags(self::getReportCacheTagByAttribute('Filter'))->flush();
            $cacheTag = $model->creator_id;
            Cache::tags(self::getReportCacheTagByAttribute("Creator|$cacheTag"))->flush();
        });
    }
}
