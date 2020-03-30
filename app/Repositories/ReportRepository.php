<?php


namespace App\Repositories;


use App\Contracts\Repositories\ReportsRepositoryContract;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class ReportRepository implements ReportsRepositoryContract
{
    public function getById($id): Model
    {
        $id = intval($id);

        if ($id <= 0) {

            throw new InvalidArgumentException('Передан некорректный id');
        }

        $report = Report::with('policies')->find($id);

        if (empty($report)) {
            throw new ModelNotFoundException(sprintf('Не найден отчет с id %s', $id));
        }

        return $report;
    }
}
