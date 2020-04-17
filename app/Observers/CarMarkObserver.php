<?php

namespace App\Observers;

use App\Cache\Car\CarMarkCacheTags;
use Illuminate\Support\Facades\Cache;

trait CarMarkObserver
{
    use CarMarkCacheTags;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($carMark) {
            $tag = self::getCarMarkTag();
            Cache::tags($tag)->flush();
        });

        static::updated(function ($carMark) {
            if ($carMark->isDirty()) {
                $tag = self::getCarMarkTag();
                Cache::tags($tag)->flush();
            }
        });

        static::deleted(function ($carMark) {
            $tag = self::getCarMarkTag();
            Cache::tags($tag)->flush();
        });
    }
}
