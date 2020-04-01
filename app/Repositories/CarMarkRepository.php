<?php


namespace App\Repositories;


use App\Contracts\Repositories\CarMarkRepositoryContract;
use App\Models\CarMark;

class CarMarkRepository implements CarMarkRepositoryContract
{
    public function getMarkList()
    {
        return CarMark::select(["id", "code", "name"])->get();
    }

    public function getCompanyMark($id, $companyId)
    {
        return CarMark::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            }
        ])
            ->where('id', $id)->first();
    }
}
