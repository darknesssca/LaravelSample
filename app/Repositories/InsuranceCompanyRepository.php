<?php


namespace App\Repositories;


use App\Contracts\Repositories\InsuranceCompanyContract;
use App\Models\InsuranceCompany;

abstract class InsuranceCompanyRepository extends AbstractDataRepository implements InsuranceCompanyContract
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
