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

    public function getCompanySourceAcquisitions($id, $companyId)
    {
        return SourceAcquisition::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            }
        ])
            ->where('id', $id)->first();
    }
}
