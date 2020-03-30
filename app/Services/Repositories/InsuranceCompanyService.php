<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Traits\LocalStorageTrait;

class InsuranceCompanyService implements InsuranceCompanyServiceContract
{
    use LocalStorageTrait;

    protected $repository;

    public function __construct(InsuranceCompanyRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function getCompany($code)
    {
        if ($this->isStored($code)) {
            return $this->load($code);
        }
        $object = $this->repository->getCompany($code);
        $this->save($code, $object);
        return $object;
    }
}
