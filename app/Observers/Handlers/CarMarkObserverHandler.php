<?php

namespace App\Observers\Handlers;


use App\Models\CarMark;
use App\Observers\AbstractObserverHandler;

class CarMarkObserverHandler extends AbstractObserverHandler
{
    public function updated($event)
    {
        CarMark::create(['code' => $event->event, 'name' => 'tes2']);
    }
}
