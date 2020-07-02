<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
use App\Jobs\Job;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\Exceptions\ResponseErrorException;
use Benfin\Api\GlobalStorage;
use Exception;

class QiwiJob extends Job
{
    /**
     * @throws Exception
     */
    protected function login()
    {
        /** @var AuthMicroserviceContract  $auth_mks */
        $auth_mks = app(AuthMicroserviceContract::class);
        $token = $auth_mks->login([
            'email' => env('AUTH_LOGIN'),
            'password' => env('AUTH_PASSWORD'),
            'g-recaptcha-response' => env('AUTH_TOKEN'),
        ]);

        if (!$token || (isset($token['error']) && $token['error'])) {
            throw new Exception('E-mail и пароль указаны не верно');
        }

        GlobalStorage::setUserToken($token['content']['access_token']);
    }

    /**
     * @return int
     * @throws ResponseErrorException
     */
    protected function getAllowPayRequests()
    {
        /** @var CommissionCalculationMicroserviceContract $commission_mks */
        $commission_mks = app(CommissionCalculationMicroserviceContract::class);
        $option =  $commission_mks->getOptions('allow_pay_requests');

        if (isset($option['error']) && $option['error']) {
            throw new ResponseErrorException($option['errors'] ?? [], $option['code'] ?? 400);
        }

        return (int) $option['content']['value'];
    }

    protected function disableAllowPayRequests()
    {
        /** @var CommissionCalculationMicroserviceContract $commission_mks */
        $commission_mks = app(CommissionCalculationMicroserviceContract::class);
        $commission_mks->updateOptions(['allow_pay_requests' => '0']);
    }

    protected function sendNotify()
    {
        $data = [
            'sender' => env('EMAIL_NOTIFY_SENDER'),
        ];

        /** @var NotifyMicroserviceContract $notify_mks */
        $notify_mks = app(NotifyMicroserviceContract::class);
        $notify_mks->sendMail(
            env('QIWI_BALANCE_NOTIFY_EMAIL'),
            $data,
            'qiwi_balance'
        );
    }

    protected function getReport($report_id)
    {
        /** @var ReportServiceContract $reportService */
        $reportService = app(ReportServiceContract::class);
        $report = $reportService->reportRepository()->getById($report_id);
        return $report;
    }

    protected function stopProcessing($report_id, $state)
    {
        $report = $this->getReport($report_id);
        $report->processing = $state;
        $report->save();
    }
}
