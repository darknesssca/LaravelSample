<?php


namespace App\Services\Qiwi;


use App\Contracts\Repositories\ReportsRepositoryContract;
use App\Models\Report;
use App\Repositories\ReportRepository;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Exception;


class ReportService
{
    private $reportRepository;
    private $qiwi;

    private $auth_mks;
    private $notify_mks;

    public function __construct()
    {
        $this->reportRepository = new ReportRepository();
        $this->qiwi = new Qiwi();


        $this->auth_mks = app(AuthMicroserviceContract::class);
        $this->notify_mks = app(NotifyMicroserviceContract::class);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function getReportInfo($id)
    {   $report = $this->reportRepository->getById($id);
        $report_info = $report->toArray();
        $report_info['creator'] = $this->getCreator($report->creator_id);

        return $report_info;
    }

    public function createReport($fields)
    {
        $fields = $this->prepareCreateFields($fields);
        $report = new Report();
        $report->fill($fields)->save();
    }

    public function test(){
        $response = $this->qiwi->getProvidersDirectories();
        return $response;
    }

    /**
     * @param $user_id
     * @return array
     * @throws Exception
     */
    private function getCreator($user_id)
    {
        $user_info = $this->auth_mks->userInfo($user_id);

        return [
            'id' => $user_info['id'],
            'full_name' => $user_info['full_name']
        ];
    }

    private function prepareCreateFields($fields)
    {
        return $fields;
    }
}
