<?php


namespace App\Repositories;


use App\Contracts\Repositories\CarCategoryRepositoryContract;
use App\Models\CarCategory;

class CarCategoryRepository implements CarCategoryRepositoryContract
{
    public function getCategoryList()
    {
        return CarCategory::select(["id", "code", "name"])->get();
    }
}
