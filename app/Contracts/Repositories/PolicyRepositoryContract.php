<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;

interface PolicyRepositoryContract
{
    public function getById(int $id) : Model;
}
