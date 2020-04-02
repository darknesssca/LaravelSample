<?php


namespace App\Contracts\Repositories\Services;


interface DocTypeServiceContract
{
    public function getDocTypesList();
    public function getCompanyDocTypeByCode($code, $companyId);
    public function getCompanyPassportDocType($isRussian, $companyId);
    public function getCompanyLicenseDocType($isRussian, $companyId);
    public function getCompanyCarDocType($type, $companyId);
    public function getCompanyInspectionDocType($companyId);
    public function getCompanyDocTypeByRelation($relationCode, $type, $companyId);
}
