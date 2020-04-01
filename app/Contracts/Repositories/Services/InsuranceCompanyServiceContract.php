<?php


namespace App\Contracts\Repositories\Services;


interface InsuranceCompanyServiceContract
{
    public function getCompany($token);
    public function getInsuranceCompanyList();
}
