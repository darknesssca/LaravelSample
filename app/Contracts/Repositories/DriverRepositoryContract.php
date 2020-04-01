<?php

namespace App\Contracts\Repositories;


interface DriverRepositoryContract
{
    public function update($id, $attributes);
}
