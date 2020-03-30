<?php

//

namespace App\Services\Qiwi;


use App\Contracts\Repositories\ReportsRepositoryContract;
use App\Models\Report;
use App\Repositories\ReportRepository;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Http\Response;


class ReportService
{
    private $reportRepository;
    private $qiwi;

    /** @var AuthMicroserviceContract $auth_mks  */
    private $auth_mks;
    /** @var NotifyMicroserviceContract $notify_mks  */
    private $notify_mks;
    /** @var LogMicroserviceContract $log_mks  */
    private $log_mks;


    public function __construct()
    {
        $this->reportRepository = new ReportRepository();
        $this->qiwi = new Qiwi();

        $this->auth_mks = app(AuthMicroserviceContract::class);
        $this->notify_mks = app(NotifyMicroserviceContract::class);
        $this->log_mks = app(LogMicroserviceContract::class);
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
        $report = new Report();
        $report->fill($fields)->save();
        $report->policies()->sync($fields['policies']);

        $message = "Создан отчет на выплату {$report->id}";
        $this->log_mks->sendLog($message, config('api_sk.logMicroserviceCode'), GlobalStorage::getUserId());

        return Response::success('Отчет успешно создан');
    }

    public function test(){
        $response = $this->qiwi->createPayment(1, 1111111111111111);
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

    private function getReward()
    {

    }
}
