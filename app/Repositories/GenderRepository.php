<?php


namespace App\Repositories;


use App\Contracts\Repositories\GenderRepositoryContract;
use App\Models\Gender;

class GenderRepository implements GenderRepositoryContract
{
    public function getGendersList()
    {
        return Gender::select(["id", "code", "name"])->get();
    }

    public function getCompanyGender($id, $companyId)
    {
        return Gender::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            }
        ])
            ->where('id', $id)->first();
    }
}
