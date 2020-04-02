<?php


namespace App\Repositories;


use App\Contracts\Repositories\DraftRepositoryContract;
use App\Models\Draft;
use Illuminate\Database\Eloquent\Model;

class DraftRepository implements DraftRepositoryContract
{

    public function getById(int $id): Model
    {
        return Draft::findOrFail($id);
    }
}
