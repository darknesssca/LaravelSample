<?php

namespace App\Observers;


use App\Cache\Car\CarModelCacheTags;
use Illuminate\Support\Facades\Cache;

trait CarModelObserver
{
    use CarModelCacheTags;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($carModel) {
            Cache::tags(self::getCarModelTag())->flush();
        });

        static::updated(function ($carModel) {
            if ($carModel->isDirty()) {
                Cache::tags(self::getCarModelTag())->flush();
            }
        });

        static::deleted(function ($carModel) {
            Cache::tags(self::getCarModelTag())->flush();
        });
    }
}
