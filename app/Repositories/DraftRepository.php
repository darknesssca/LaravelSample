<?php


namespace App\Repositories;


use App\Contracts\Repositories\DraftRepositoryContract;
use App\Models\Draft;
use Illuminate\Database\Eloquent\Model;

class DraftRepository implements DraftRepositoryContract
{

    public function getById(int $id, int $agentId): Model
    {
        return Draft::with([
            'model',
            'model.mark',
            'model.category',
            'doctype',
            'type',
            'owner',
            'owner.gender',
            'owner.citizenship',
            'insurer',
            'insurer.gender',
            'insurer.citizenship',
            'regcountry',
            'acquisition',
            'usagetarget',
            'drivers',
        ])
            ->where('id', $id)
            ->where('agent_id', $agentId)
            ->first();
    }


    public function getDraftsByAgentId($agentId)
    {
        return Draft::with([
            'model',
            'model.mark',
            'model.category',
            'doctype',
            'type',
            'owner',
            'owner.gender',
            'owner.citizenship',
            'insurer',
            'insurer.gender',
            'insurer.citizenship',
            'regcountry',
            'acquisition',
            'usagetarget',
            'drivers',
        ])
            ->where('agent_id', $agentId)
            ->get();
    }

    public function create($attributes)
    {
        return Draft::create($attributes);
    }

    public function update($id, $attributes)
    {
        return Draft::where('id', $id)->update($attributes);
    }

    public function delete($id)
    {
        return Draft::where('id', $id)->delete();
    }
}
