<?php


namespace App\Contracts\Repositories;


interface DocTypeRepositoryContract
{
    public function getDocTypesList();
    public function getCompanyDocTypeByCode($code, $companyId);
    public function getPassportCode($isRussian);
    public function getLicenseCode($isRussian);
    public function getCarDocCode($type);
    public function getInspectionCode();
    public function getDocTypeRelations();
}
