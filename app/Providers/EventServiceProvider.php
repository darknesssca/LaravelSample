<?php

namespace App\Providers;


use App\Models\Draft;
use App\Observers\testObserv;
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
        Draft::observe(testObserv::class);
    }

}
