<?php

namespace App\Repositories;

use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Model;

class PolicyRepository implements PolicyRepositoryContract
{

    public function getById(int $id): Model
    {
        return Policy::findOrFail($id);
    }
}
