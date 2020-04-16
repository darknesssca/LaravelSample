<?php


namespace App\Repositories;

use App\Cache\DraftCacheTags;
use App\Contracts\Repositories\DraftRepositoryContract;
use App\Models\Draft;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class DraftRepository implements DraftRepositoryContract
{
    use CacheKeysTrait, DraftCacheTags;

    private $_DAY_TTL = 24 * 60 * 60;

    public function getById(int $id, int $agentId)
    {
        $tag = $this->getDraftTag();
        $key = $this->getCacheKey($id, $agentId);

        return Cache::tags($tag)->remember($key, $this->_DAY_TTL, function () use ($id, $agentId) {
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
        });
    }


    public function getDraftsByAgentId($agentId)
    {
        $cacheKey = $this->getCacheKey($agentId);

        return Cache::tags($this->getDraftTag())->remember($cacheKey, $this->_DAY_TTL, function () use ($agentId) {
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
        });
    }

    public function create($attributes)
    {
        return Draft::create($attributes);
    }

    public function update($id, $attributes)
    {
        return Draft::find($id)->update($attributes);
    }

    public function delete($id)
    {
        return Draft::where('id', $id)->delete();
    }
}
