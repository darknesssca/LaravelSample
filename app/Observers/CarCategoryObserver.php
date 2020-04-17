<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Cache\Car\CarCategoryCacheTags;

trait CarCategoryObserver
{
    use CarCategoryCacheTags;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($carCategory) {
            $tag = self::getGuidesCategoriesTag();
            Cache::tags($tag)->flush();
        });

        static::updated(function ($carCategory) {
            if ($carCategory->isDirty()) {
                $tag = self::getGuidesCategoriesTag();
                Cache::tags($tag)->flush();
            }
        });

        static::deleted(function ($carCategory) {
            $tag = self::getGuidesCategoriesTag();
            Cache::tags($tag)->flush();
        });
    }
}
