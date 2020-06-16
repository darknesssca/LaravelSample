<?php


namespace App\Contracts\Services;


use App\Contracts\Repositories\ReportRepositoryContract;
use App\Models\Report;

interface ReportServiceContract
{
    public function reportRepository(): ReportRepositoryContract;

    public function getReportInfo($id): array;

    public function getReportsInfo(array $fields);

    public function createReport(array $fields);

    public function createPayout($report);

    public function executePayout($report);

    public function createXls(Report $report);
}
