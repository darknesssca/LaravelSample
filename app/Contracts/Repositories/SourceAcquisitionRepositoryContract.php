<?php


namespace App\Contracts\Repositories;


interface SourceAcquisitionRepositoryContract
{
    public function getSourceAcquisitionsList();
    public function getCompanySourceAcquisitions($id, $companyId);
}
