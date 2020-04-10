<?php


namespace App\Repositories;


use App\Contracts\Repositories\DraftRepositoryContract;
use App\Models\Draft;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DraftRepository implements DraftRepositoryContract
{

    public function getById(int $id, int $agentId)
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

    /**для заданного агента ищет черновики по фильтру. (поля: фио, модель, марка)
     * @param int $agentId
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function getByFilter(int $agentId, array $filter)
    {
        $query = Draft::with(['owner', 'model','mark']);

        $query->where('agent_id', $agentId); //только для заданного агента
        if (!empty($filter['query'])) {
            $query->where(function (Builder $query) use ($filter) {
                //Поиск по ФИО
                $query->orWhereHas('owner', function (Builder $sub_query) use ($filter) {
                    $sub_query->where('first_name', 'like', '%' . $filter['query'] . '%')
                        ->orWhere('last_name', 'like', '%' . $filter['query'] . '%')
                        ->orWhere('patronymic', 'like', '%' . $filter['query'] . '%');
                });
                //поиск по модели
                $query->orWhereHas('model', function (Builder $sub_query) use ($filter) {
                    $sub_query->where('name', 'like', '%' . $filter['query'] . '%');
                });
                //поиск по модели
                $query->orWhereHas('mark', function (Builder $sub_query) use ($filter) {
                    $sub_query->where('name', 'like', '%' . $filter['query'] . '%');
                });
            });
        }

        //Сортировка
        if (!empty($filter['orderColumn']) && !empty($filter['orderDirrection'])) {
            $query->orderBy($filter['orderColumn'], $filter['orderDirrection']);
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query->paginate(
            $filter['count'],
            ['*'],
            'page',
            $filter['page']
        );

    }
}
