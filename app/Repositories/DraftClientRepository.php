<?php


namespace App\Repositories;


use App\Contracts\Repositories\DraftClientRepositoryContract;
use App\Models\DraftClient;

class DraftClientRepository implements DraftClientRepositoryContract
{

    public function create($attributes)
    {
        return DraftClient::create($attributes);
    }

    public function update($id, $attributes)
    {
        return DraftClient::where('id', $id)->update($attributes);
    }

    public function delete($id)
    {
        return DraftClient::where('id', $id)->delete();
    }
}
