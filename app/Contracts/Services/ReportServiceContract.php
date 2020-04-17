<?php


namespace App\Contracts\Services;


use App\Contracts\Repositories\ReportRepositoryContract;

interface ReportServiceContract
{
    public function reportRepository(): ReportRepositoryContract;

    public function getReportInfo($id): array;

    public function getReportsInfo(array $fields);

    public function createReport(array $fields);

    public function initQiwi($user_requisites, $tax_status_code, $description = 'Перевод');
}
