<?php


namespace App\Contracts\Repositories\Services;


interface InsuranceCompanyServiceContract
{
    public function getCompany($code);
    public function getCompanyById($id);
    public function getInsuranceCompanyList($checkActive);
}
