<?php


namespace App\Repositories;


use App\Contracts\Repositories\CarModelRepositoryContract;
use App\Models\CarModel;

class CarModelRepository implements CarModelRepositoryContract
{
    public function getModelListByMarkId($mark_id)
    {
        return CarModel::select(["id", "code", "name", "category_id", "mark_id"])->where("mark_id", $mark_id)->get();
    }

    public function getModelList()
    {
        return CarModel::select(["id", "code", "name", "category_id", "mark_id"])->get();
    }

    public function getCompanyModel($mark_id, $id, $companyId)
    {
        return CarModel::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            },
        ])
            ->with([
                'category'
            ])
            ->where('id', $id)
            ->where('mark_id', $mark_id)
            ->first();
    }

    public function getCompanyModelByName($mark_id, $name, $companyId)
    {
        return CarModel::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            },
        ])
            ->with([
                'category'
            ])
            ->where('mark_id', $mark_id)
            ->where('name', $name)
            ->first();
    }
}
