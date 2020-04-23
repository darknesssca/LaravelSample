<?php


namespace App\Cache;


trait InsuranceCompanyCacheTag
{
    protected static function getInsuranceCompanyTag(): string
    {
        return "InsuranceCompany";
    }

    protected static function getInsuranceCompanyListKey(): string
    {
        return "InsuranceCompany|List";
    }
}
