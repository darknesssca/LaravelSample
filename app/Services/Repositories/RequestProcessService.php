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

    private function getLocalStorageKey($token, $companyCode)
    {
        return $companyCode . '_' . $token;
    }

    public function getPool($state, $limit)
    {
        $pool = $this->repository->getPool($state, $limit);
        foreach ($pool as $item) {
            $this->save($this->getLocalStorageKey($item->token, $item->company), $item);
        }
        return $pool;
    }

    public function updateCheckCount($token, $companyCode)
    {
        if ($this->isStored($this->getLocalStorageKey($token, $companyCode))) {
            $object = $this->load($this->getLocalStorageKey($token, $companyCode));
        } else {
            $object = $this->find($token, $companyCode);
        }
        if (!$object) {
            return false;
        }
        $checkCount = ++$object->checkCount;
        if ($checkCount >= config('api_sk.maxCheckCount')) {
            $object->delete();
            $this->drop($this->getLocalStorageKey($token, $companyCode));
            return false;
        }
        $object->update(['checkCount' => $checkCount]);
        $this->drop($this->getLocalStorageKey($token, $companyCode));
        return true;

    }

    public function find($token, $companyCode)
    {
        $object = $this->repository->find($token, $companyCode);
        $this->save($this->getLocalStorageKey($token, $companyCode), $object);
        return $object;
    }

    public function delete($token, $companyCode)
    {
        if ($this->isStored($this->getLocalStorageKey($token, $companyCode))) {
            $object = $this->load($this->getLocalStorageKey($token, $companyCode));
            $this->drop($this->getLocalStorageKey($token, $companyCode));
            return $object->delete();
        }
        return $this->repository->delete($token, $companyCode);
    }

    public function update($token, $companyCode, $data)
    {
        $object = $this->repository->update($token, $companyCode, $data);
        $this->drop($this->getLocalStorageKey($token, $companyCode));
        return $object;
    }

    public function create($data)
    {
        $object = $this->repository->create($data);
        $this->save($this->getLocalStorageKey($object->token, $object->company), $object);
        return $object;
    }
}
