<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;

interface PolicyRepositoryContract
{
    public function getList(array $filter);
    public function create(array $data);
}
