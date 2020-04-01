<?php


namespace App\Contracts\Repositories;


interface DocTypeRepositoryContract
{
    public function getDocTypesList();
    public function getCompanyDocTypeByCode($code, $companyId);
}
