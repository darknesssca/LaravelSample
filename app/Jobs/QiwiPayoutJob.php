<?php


namespace App\Jobs;


use App\Contracts\Services\ReportServiceContract;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;

class QiwiPayoutJob extends Job
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        /** @var ReportServiceContract $reportService  */
        $reportService = app(ReportServiceContract::class);
        GlobalStorage::setUser($this->params['user']);
        $this->login();
        $report = $reportService->reportRepository()->getById($this->params['report_id']);

        try {
            $reportService->createPayout($report);
        } catch (Exception $exception) {
            $report->policies()->detach();
            $report->forceDelete();
            throw $exception;
        }

        $reportService->createXls($report);
    }


    /**
     * @throws Exception
     */
    private function login()
    {
        /** @var AuthMicroserviceContract $auth_mks */
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
}
