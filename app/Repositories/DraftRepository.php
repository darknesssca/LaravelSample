<?php


namespace App\Repositories;

use App\Cache\DraftCacheTags;
use App\Contracts\Repositories\DraftRepositoryContract;
use App\Models\Draft;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DraftRepository implements DraftRepositoryContract
{
    use CacheKeysTrait, DraftCacheTags;

    private $_DAY_TTL = 24 * 60 * 60;

    public function getById(int $id, int $agentId)
    {
        $tag = self::getDraftAgentTag($agentId);
        $key = self::getCacheKey("ById", $id, $agentId);

        return Cache::tags($tag)->remember($key, $this->_DAY_TTL, function () use ($id, $agentId) {
            return Draft::with([
                'mark',
                'category',
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
                'drivers'
            ])
                ->where('id', $id)
                ->where('agent_id', $agentId)
                ->first();
        });
    }


    public function getDraftsByAgentId($agentId)
    {
        $cacheKey = self::getCacheKey("Agent", $agentId);
        $cacheTag = self::getDraftAgentTag($agentId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->_DAY_TTL, function () use ($agentId) {
            return Draft::with([
                'mark',
                'category',
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
                'drivers'
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

    /**для заданного агента ищет черновики по фильтру. (поля: фио, модель, марка)
     * @param int $agentId
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function getByFilter(int $agentId, array $filter)
    {
        $cacheTag = self::getDraftAgentTag($agentId);
        $cacheKey = self::getCacheKey("filter", $agentId, $filter);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->_DAY_TTL, function () use ($agentId, $filter) {
            $query = Draft::with(['owner', 'insurer', 'mark', 'category']);

            $query->where('agent_id', $agentId); //только для заданного агента
            if (!empty($filter['query'])) {
                $query->where(function (Builder $query) use ($filter) {
                    //Поиск по ФИО
                    $query->orWhereHas('insurer', function (Builder $sub_query) use ($filter) {
                        $words = explode(' ', $filter['query'], 3);
                        if (count($words) == 1) {
                            $sub_query->where('last_name', 'ilike', '%' . $filter['query'] . '%')
                                ->orWhere('first_name', 'ilike', '%' . $filter['query'] . '%')
                                ->orWhere('patronymic', 'ilike', '%' . $filter['query'] . '%');
                        } else if (count($words) == 2) {
                            $sub_query->where('last_name', 'ilike', '%' . $words[0] . '%')
                                ->where('first_name', 'ilike', '%' . $words[1] . '%');
                        } else if (count($words) > 2) {
                            $sub_query->where('last_name', 'ilike', '%' . $words[0] . '%')
                                ->where('first_name', 'ilike', '%' . $words[1] . '%')
                                ->where('patronymic', 'ilike', '%' . $words[2] . '%');
                        }
                    });
                    //поиск по модели
                    $query->orWhere('vehicle_model', 'ilike', '%' . $filter['query'] . '%');
                    //поиск по модели
                    $query->orWhereHas('mark', function (Builder $sub_query) use ($filter) {
                        $sub_query->where('name', 'ilike', '%' . $filter['query'] . '%');
                    });
                });
            }

            //Сортировка
            if (!empty($filter['orderColumn']) && !empty($filter['orderDirection'])) {
                $query->orderBy($filter['orderColumn'], $filter['orderDirection']);
            } else {
                $query->orderBy('id', 'asc');
            }

            return $query->paginate(
                $filter['count'],
                ['*'],
                'page',
                $filter['page']
            );
        });

    }
}
