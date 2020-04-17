<?php


namespace App\Contracts\Repositories\Services;


interface DocTypeServiceContract
{
    public function getDocTypesList();
    public function getDocTypeByCode($code);
    public function getCompanyDocTypeByCode($code, $companyId);
    public function getCompanyDocTypeByCode2($code, $companyId);
    public function getCompanyDocTypeByCode3($code, $companyId);
    public function getCompanyPassportDocType($isRussian, $companyId);
    public function getCompanyPassportDocType2($isRussian, $companyId);
    public function getCompanyPassportDocType3($isRussian, $companyId);
    public function getCompanyLicenseDocType($isRussian, $companyId);
    public function getCompanyLicenseDocType2($isRussian, $companyId);
    public function getCompanyLicenseDocType3($isRussian, $companyId);
    public function getCompanyCarDocType($type, $companyId);
    public function getCompanyCarDocType2($type, $companyId);
    public function getCompanyCarDocType3($type, $companyId);
    public function getCompanyInspectionDocType($isRussian, $companyId);
    public function getCompanyInspectionDocType2($isRussian, $companyId);
    public function getCompanyInspectionDocType3($isRussian, $companyId);
    public function getCompanyDocTypeByRelation($relationCode, $type, $companyId);
    public function getCompanyDocTypeByRelation2($relationCode, $type, $companyId);
    public function getCompanyDocTypeByRelation3($relationCode, $type, $companyId);
}
