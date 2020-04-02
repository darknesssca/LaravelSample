<?php


namespace App\Observers;


abstract class AbstractObserver
{
    protected $observe = [];

    protected $events = [
//        'created',
        'updated',
        'deleted',
    ];

    const observerEventNamespace = 'App\\Observers\\Events\\';
    const observerHandlerNamespace = 'App\\Observers\\Handlers\\';
    const observerEventClassName = 'Observer';
    const observerHandlerClassName = 'ObserverHandler';
    const modelNamespace = 'App\\Models\\';

    public function getListeners()
    {
        $listen = [];
        foreach ($this->observe as $model) {
            $observerClass = static::observerEventNamespace . $model . static::observerEventClassName;
            $observerListenerClass = static::observerHandlerNamespace . $model . static::observerHandlerClassName;
            $listen[$observerClass] = [$observerListenerClass];
        }
        return $listen;
    }

    public function setEvents()
    {
        foreach ($this->observe as $model) {
            foreach ($this->events as $event) {
                $modelClass = static::modelNamespace . $model;
                $observerClass = static::observerEventNamespace . $model . static::observerEventClassName;
                $modelClass::{$event}(function () use ($observerClass, $event){
                    event(new $observerClass($event));
                });
            }
        }
    }
}
