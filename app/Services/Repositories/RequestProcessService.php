<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Traits\LocalStorageTrait;

class RequestProcessService implements RequestProcessServiceContract
{
    use LocalStorageTrait;

    protected $repository;

    public function __construct(RequestProcessRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function getPool($state, $limit)
    {
        $pool = $this->repository->getPool($state, $limit);
        foreach ($pool as $item) {
            $this->save($item->token, $item);
        }
        return $pool;
    }

    public function updateCheckCount($token)
    {
        if ($this->isStored($token)) {
            $object = $this->load($token);
        } else {
            $object = $this->find($token);
        }
        $checkCount = ++$object->checkCount;
        if ($checkCount >= config('api_sk.maxCheckCount')) {
            $object->delete();
            return false;
        }
        $object->update(['checkCount' => $checkCount]);
        return true;

    }

    public function getByToken($token)
    {
        if ($this->isStored($token)) {
            return $this->load($token);
        } else {
           return$this->find($token);
        }
    }

    public function find($token)
    {
        $object = $this->repository->find($token);
        $this->save($token, $object);
        return $object;
    }

    public function delete($token)
    {
        if ($this->isStored($token)) {
            $object = $this->load($token);
            $this->drop($token);
            return $object->delete();
        }
        return $this->repository->delete($token);
    }

    public function update($token, $data)
    {
        $object = $this->repository->update($token, $data);
        $this->save($token, $object);
        return $object;
    }

    public function create($data)
    {
        $object = $this->repository->create($data);
        $this->save($object->token, $object);
        return $object;
    }
}
