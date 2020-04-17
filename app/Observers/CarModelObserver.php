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
            $cacheTag = self::getCarModelTag();
            $cacheKey = self::getCarModelListKey();

            Cache::tags($cacheTag)->forget($cacheKey);
        });
    }
    public function created($event)
    {
        $tag = $this->getGuidesModelsTag();
        Cache::tags($tag)->flush();
    }

    public function updated($event)
    {
        $tag = $this->getGuidesModelsTag();
        Cache::tags($tag)->flush();
    }

    public function deleted($event)
    {
        $tag = $this->getGuidesModelsTag();
        Cache::tags($tag)->flush();
    }
}
