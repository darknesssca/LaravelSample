<?php

namespace App\Observers\Handlers;


use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;
use Benfin\Cache\Observers\AbstractObserverHandler;

class CarCategoryObserverHandler extends AbstractObserverHandler
{
    use CacheTrait;

    public function created($event)
    {
        $tag = $this->getGuidesCategoriesTag();
        Cache::tags($tag)->flush();
    }

    public function updated($event)
    {
        $tag = $this->getGuidesCategoriesTag();
        Cache::tags($tag)->flush();
    }

    public function deleted($event)
    {
        $tag = $this->getGuidesCategoriesTag();
        Cache::tags($tag)->flush();
    }
}
