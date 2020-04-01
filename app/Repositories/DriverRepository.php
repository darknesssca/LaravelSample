<?php


namespace App\Repositories;


use App\Contracts\Repositories\DriverRepositoryContract;
use App\Models\Driver;

class DriverRepository implements DriverRepositoryContract
{
    public function update($id, $attributes)
    {
        return Driver::where('id', $id)->update($attributes);
    }
}
