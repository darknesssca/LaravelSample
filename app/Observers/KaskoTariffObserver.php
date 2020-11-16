<?php


namespace App\Observers;


use App\Cache\KaskoTariffTags;
use Illuminate\Support\Facades\Cache;

trait KaskoTariffObserver
{
    use KaskoTariffTags;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($tariff) {
            Cache::tags(self::getKaskoTariffTag())->flush();
        });

        static::updated(function ($tariff) {
            if ($tariff->isDirty()) {
                Cache::tags(self::getKaskoTariffTag())->flush();
            }
        });

        static::deleted(function ($tariff) {
            Cache::tags(self::getKaskoTariffTag())->flush();
        });
    }
}
