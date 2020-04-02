<?php


namespace App\Observers;


use App\Events\Event;

class AbstractObserverEvent extends Event
{
    public $event;

    public function __construct($event)
    {
        $this->event = $event;
    }
}
