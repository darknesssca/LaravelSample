<?php


namespace App\Repositories;


use App\Cache\ReportCacheTag;
use App\Contracts\Repositories\ReportRepositoryContract;
use App\Exceptions\ReportNotFoundException;
use App\Models\Report;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ReportRepository implements ReportRepositoryContract
{
    use ReportCacheTag, CacheKeysTrait;

    private $_DAY_TTL = 24 * 60 * 60;

    /**
     * @param int $id
     * @return Report|Builder
     * @throws ReportNotFoundException
     */
    public function getById($id): Report
    {
        $cacheTag = self::getReportCacheTag();
        $cacheKey = self::getCacheKey('id', $id);

        $report = Cache::tags($cacheTag)->remember($cacheKey, $this->_DAY_TTL, function () use ($id) {
            return Report::with(['policies', 'file'])->find($id);
        });

        $this->checkReport($report);

        return $report;
    }

    /**
     * @param array $filter
     * @return Report|LengthAwarePaginator|Builder|Collection
     */
    public function getAll(array $filter)
    {
        $cacheTag = self::getReportCacheTagByAttribute("Filter");
        $cacheKey = self::getCacheKey($filter);

        $remember = Cache::tags($cacheTag)->remember($cacheKey, $this->_DAY_TTL, function () use ($filter) {
            $query = Report::with(['policies', 'file']);

            if (!empty($filter['search'])) {
                $query->where('name', 'like', '%' . $filter['search'] . '%');
            }

            if (!empty($filter['orderBy']) && !empty($filter['orderDirection'])) {
                $query->orderBy($filter['orderBy'], $filter['orderDirection']);
            }


            $count = !empty($filter['count']) ? $filter['count'] : 10;
            $page = !empty($filter['page']) ? $filter['page'] : 1;
            return $query->paginate(
                $count,
                ['*'],
                'page',
                $page
            );
        });
        return $remember;
    }

    /**
     * @param int $creator_id
     * @param array $filter
     * @return Report|LengthAwarePaginator|Builder|Collection
     */
    public function getByCreatorId(int $creator_id, array $filter)
    {
        $cacheTag = self::getReportCacheTagByAttribute("Creator|$creator_id");
        $cacheKey = self::getCacheKey('Filter', $filter);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->_DAY_TTL, function () use ($creator_id, $filter) {
            $query = Report::with(['policies', 'file'])
                ->where('creator_id', '=', $creator_id);

            if (!empty($filter['search'])) {
                $query->where('name', 'like', '%' . $filter['search'] . '%');
            }

            if (!empty($filter['orderBy']) && !empty($filter['orderDirection'])) {
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
        });
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
