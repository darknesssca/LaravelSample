<?php


namespace App\Repositories;


use App\Contracts\Repositories\SourceAcquisitionRepositoryContract;
use App\Models\SourceAcquisition;

class SourceAcquisitionRepository implements SourceAcquisitionRepositoryContract
{
    public function getSourceAcquisitionsList()
    {
        return SourceAcquisition::select(["id", "code", "name"])->get();
    }
}
