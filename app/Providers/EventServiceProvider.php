<?php

namespace App\Providers;


use App\Observers\Observer;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [];

    public function boot()
    {
        $observer = app(Observer::class);
        //create observers
        $this->listen = $observer->getListeners();
        // boot observers
        parent::boot();
        // handle events for observer
        $observer->setEvents();
    }

}
