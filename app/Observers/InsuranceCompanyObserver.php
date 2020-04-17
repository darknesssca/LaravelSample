<?php

namespace App\Observers;


use App\Cache\InsuranceCompanyCacheTag;
use Illuminate\Support\Facades\Cache;

trait InsuranceCompanyObserver
{
    use InsuranceCompanyCacheTag;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($insuranceCompany) {
            Cache::tags(self::getInsuranceCompanyTag())->flush();
        });

        static::updated(function ($insuranceCompany) {
            if ($insuranceCompany->isDirty()) {
                Cache::tags(self::getInsuranceCompanyTag())->flush();
            }
        });

        static::deleted(function ($insuranceCompany) {
            Cache::tags(self::getInsuranceCompanyTag())->flush();
        });
    }
}
