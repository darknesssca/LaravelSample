<?php


namespace App\Repositories;


use App\Contracts\Repositories\ReportRepositoryContract;
use App\Exceptions\ReportNotFoundException;
use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository implements ReportRepositoryContract
{
    /**
     * @param int $id
     * @return Report|Builder
     * @throws ReportNotFoundException
     */
    public function getById($id): Report
    {
        $report = Report::with('policies')->find($id);

        $this->checkReport($report);

        return $report;
    }

    /**
     * @param array $filter
     * @return Report|LengthAwarePaginator|Builder|Collection
     */
    public function getAll(array $filter)
    {
        $query = Report::with(['policies', 'file']);

        if (!empty($filter['search'])) {
            $query->where('name', 'like', '%' . $filter['search'] . '%');
        }

        if (!empty($filter['orderBy']) && !empty($filter['orderDirection'])){
            $query->orderBy($filter['orderBy'], $filter['orderDirection']);
        }


        $count = !empty($filter['count']) ? $filter['count'] : 10;
        $page = !empty($filter['page']) ? $filter['page'] : 1;
        return $query->paginate(
            $count,
            ['*'],
            'Страницы',
            $page
        );
    }

    /**
     * @param int $creator_id
     * @param array $filter
     * @return Report|LengthAwarePaginator|Builder|Collection
     */
    public function getByCreatorId(int $creator_id, array $filter)
    {
        $query = Report::with(['policies', 'file'])
            ->where('creator_id', '=', $creator_id);

        if (!empty($filter['search'])) {
            $query->where('name', 'like', '%' . $filter['search'] . '%');
        }

        if (!empty($filter['orderBy']) && !empty($filter['orderDirection'])){
            $query->orderBy($filter['orderBy'], $filter['orderDirection']);
        }


        $count = !empty($filter['count']) ? $filter['count'] : 10;
        $page = !empty($filter['page']) ? $filter['page'] : 1;
        return $query->paginate(
            $count,
            ['*'],
            'Страницы',
            $page
        );
    }

    public function create(array $fields): Report
    {
        $report = new Report();
        $report->fill($fields)->save();
        return $report;
    }

    /**
     * @param $report
     * @throws ReportNotFoundException
     */
    private function checkReport($report)
    {
        if (empty($report)) {
            throw new ReportNotFoundException();
        }
    }
}
