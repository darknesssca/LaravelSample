<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Traits\LocalStorageTrait;

class IntermediateDataService implements IntermediateDataServiceContract
{
    use LocalStorageTrait;

    protected $repository;

    public function __construct(IntermediateDataRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function find($token)
    {
        $object = $this->repository->find($token);
        $this->save($token, $object);
        return $object;
    }

    public function getToken($token, $force = false)
    {
        if (!$force && $this->isStored($token)) {
            return $this->load($token);
        }
        return $this->repository->find($token);
    }

    public function update($token, $data)
    {
        $object = $this->repository->update($token, $data);
        $this->drop($token);
        return $object;
    }

    public function create($data)
    {
        $object = $this->repository->create($data);
        $this->save($object->token, $object);
        return $object;
    }

    public function delete($token)
    {
        $this->drop($token);
        $this->repository->delete($token);
    }
}
