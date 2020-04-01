<?php

namespace App\Contracts\Repositories;


interface DriverRepositoryContract
{
    public function update($id, $attributes);
    public function delete($id);
}
