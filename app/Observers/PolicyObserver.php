<?php


namespace App\Observers;


use App\Cache\Policy\PolicyCacheTag;
use App\Exceptions\PolicyDuplicateException;
use App\Models\Policy;
use App\Repositories\PolicyRepository;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Illuminate\Support\Facades\Cache;

trait PolicyObserver
{
    use PolicyCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model) {
            $duplicate = Policy::where('number', $model->number)
                ->where('insurance_company_id', $model->insurance_company_id)
                ->where('registration_date', $model->registration_date)
                ->first();

            if ($duplicate) {
                throw new PolicyDuplicateException('Попытка создать дубликат полиса');
            }

            return true;
        });

        static::saved(function ($model) {
            Cache::tags(self::getPolicyListCacheTagByUser($model->agent_id))->flush();
            $referId = app(AuthMicroserviceContract::class)->userInfo($model->agent_id)["content"]["referer_id"] ?? "";
            Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
        });

        static::created(function ($model) {
            Cache::tags(self::getPolicyListCacheTagByUser($model->agent_id))->flush();
            $referId = app(AuthMicroserviceContract::class)->userInfo($model->agent_id)["content"]["referer_id"] ?? "";
            Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                Cache::tags(self::getPolicyListCacheTagByUser($model->agent_id))->flush();
                $referId = app(AuthMicroserviceContract::class)->userInfo($model->agent_id)["content"]["referer_id"] ?? "";
                Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
            }
        });

        static::deleted(function ($model) {
            Cache::tags(self::getPolicyListCacheTagByUser())->flush();
            $referId = app(AuthMicroserviceContract::class)->userInfo($model->agent_id)["content"]["referer_id"] ?? "";
            Cache::tags(self::getPolicyListCacheTagByAttribute("List|$referId"))->flush();
        });
    }
}
