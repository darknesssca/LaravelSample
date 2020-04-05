<?php


namespace App\Repositories;


use App\Contracts\Repositories\ReportRepositoryContract;
use App\Exceptions\ReportNotFoundException;
use App\Models\Report;
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
     * @return Report|Builder|Collection
     * @throws ReportNotFoundException
     */
    public function getAll(): Report
    {
        $reports = Report::with('policies file')->get();

        $this->checkReport($reports);

        return $reports;
    }

    /**
     * @param int $creator_id
     * @return Report|Builder|Collection
     * @throws ReportNotFoundException
     */
    public function getByCreatorId(int $creator_id): Report
    {
        $reports = Report::with('policies file')->where('creator_id', '=', $creator_id)->get();

        $this->checkReport($reports);

        return $reports;
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
    private function checkReport($report){
        if (empty($report)) {
            throw new ReportNotFoundException();
        }
    }
}
