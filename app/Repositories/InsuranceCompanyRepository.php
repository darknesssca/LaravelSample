<?php


namespace App\Repositories;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Models\InsuranceCompany;

class InsuranceCompanyRepository extends AbstractDataRepository implements InsuranceCompanyRepositoryContract
{
    public function __construct(InsuranceCompany $model)
    {
        parent::__construct($model);
    }

    public function getCompany($code)
    {
        if ($this->isStored($code)) {
            return $this->load($code);
        }
        $object = $this->model
            ->where([
                'code' => $code,
                'active' => true,
            ])
            ->first();
        $this->save($code, $object);
        return $object;
    }

}
