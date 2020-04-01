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
}
