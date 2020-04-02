<?php


namespace App\Traits\Observers;


trait ObserverTrait
{
    public function create(...$args)
    {
        parent::create($args);
        dd('create');
    }
}
