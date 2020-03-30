<?php


namespace App\Repositories;


use App\Contracts\Repositories\AbstractRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractDataRepository implements AbstractRepositoryInterface
{
    protected $model;
    protected $storage;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function save($id, $model)
    {
        $this->storage[$id] = $model;
    }

    public function load($id)
    {
        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }
        return $this->find($id);
    }

    public function drop($id)
    {
        if (isset($this->storage[$id])) {
            unset($this->storage[$id]);
        }
    }

    public function isStored($id)
    {
        return isset($this->storage[$id]) && $this->storage[$id];
    }

    public function find($id)
    {
        $result = $this->model->find($id);
        $this->save($id, $result);
        return $result;
    }

    public function getById($id)
    {
        $result = $this->model->where('id', $id)->first();
        $this->save($id, $result);
        return $result;
    }

    public function create($data)
    {
        $result = $this->model->create($data);
        $this->save($result->id, $result);
        return $result;
    }

    public function update($id, $data)
    {
        $result = $this->model->where('id', $id)->update($data);
        $this->save($id, $result);
        return $result;
    }

    public function delete($id)
    {
        $result = $this->model->delete($id);
        $this->drop($id);
        return $result;
    }
}
