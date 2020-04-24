<?php


namespace App\Observers;


use App\Cache\Policy\PolicyCacheTag;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Illuminate\Support\Facades\Cache;

trait PolicyObserver
{
    use PolicyCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::tags(self::getPolicyListCacheTagByUser())->flush();
            $referId = app(AuthMicroserviceContract::class)->userInfo(GlobalStorage::getUserId())["referer_id"] ?? "";
            Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                Cache::tags(self::getPolicyListCacheTagByUser())->flush();
                $referId = app(AuthMicroserviceContract::class)->userInfo(GlobalStorage::getUserId())["referer_id"] ?? "";
                Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
            }
        });

        static::deleted(function ($model) {
            Cache::tags(self::getPolicyListCacheTagByUser())->flush();
            $referId = app(AuthMicroserviceContract::class)->userInfo(GlobalStorage::getUserId())["referer_id"] ?? "";
            Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
        });
    }
}
