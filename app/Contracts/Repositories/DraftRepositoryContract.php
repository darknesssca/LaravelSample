<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;

interface DraftRepositoryContract
{
    public function getById(int $id) : Model;
}
