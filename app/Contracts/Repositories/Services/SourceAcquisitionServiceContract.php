<?php


namespace App\Contracts\Repositories\Services;


interface SourceAcquisitionServiceContract
{
    public function getSourceAcquisitionsList();
    public function getCompanySourceAcquisitions($id, $companyId);
}
