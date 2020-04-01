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
}
