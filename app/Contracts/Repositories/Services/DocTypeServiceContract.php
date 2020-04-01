<?php


namespace App\Contracts\Repositories\Services;


interface DocTypeServiceContract
{
    public function getDocTypesList();
    public function getCompanyDocTypeByCode($code, $companyId);
}
