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
}
