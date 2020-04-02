<?php


namespace App\Observers;


abstract class AbstractObserverHandler
{
    const anyEventMethodName = 'any';

    public function handle(AbstractObserverEvent $event)
    {
        if (method_exists($this, $event->event)) {
            $this->{$event->event}($event);
        }

        if (method_exists($this, static::anyEventMethodName)) {
            $this->{static::anyEventMethodName}($event);
        }
    }
}
