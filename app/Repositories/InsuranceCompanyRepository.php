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
        return $this->model
            ->where([
                'code' => $code,
                'active' => true,
            ])
            ->first();
    }

}
