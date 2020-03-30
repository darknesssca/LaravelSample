<?php


namespace App\Traits;


trait LocalStorageTrait
{

    protected $storage = [];

    public function save($id, $model)
    {
        $this->storage[$id] = $model;
    }

    public function isStored($id)
    {
        return isset($this->storage[$id]) && $this->storage[$id];
    }

    public function load($id)
    {
        return $this->isStored($id) ? $this->storage[$id] : null;
    }

    public function drop($id)
    {
        if ($this->isStored($id)) {
            unset($this->storage[$id]);
        }
    }

}
