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

    public function getInsuranceCompanyList()
    {
        return InsuranceCompany::select(["id", "code", "name"])->where("active", true)->get();
    }

    public function getById(int $id)
    {
        return InsuranceCompany::query()->find($id)->first();
    }
}
