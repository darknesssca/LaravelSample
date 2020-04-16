<?php


namespace App\Repositories;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Models\InsuranceCompany;

class InsuranceCompanyRepository implements InsuranceCompanyRepositoryContract
{
    public function getCompany($code)
    {
        return InsuranceCompany::where([
                'code' => $code,
                'active' => true,
            ])
            ->first();
    }

    public function getCompanyById($id)
    {
        return InsuranceCompany::where([
            'id' => $id,
            'active' => true,
        ])
            ->first();
    }

    public function getInsuranceCompanyList()
    {
        return InsuranceCompany::select(["id", "code", "name"])->where("active", true)->get();
    }
}
