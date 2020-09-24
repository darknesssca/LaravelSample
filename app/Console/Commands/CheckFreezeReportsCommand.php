<?php


namespace App\Console\Commands;


use App\Contracts\Services\ReportServiceContract;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Console\Command;

class CheckFreezeReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benfin:check_freeze';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check sum freeze reports';


    /** @var ReportServiceContract $reportService */
    private $reportService;

    /** @var NotifyMicroserviceContract $notifyMks */
    private $notifyMks;

    /** @var CommissionCalculationMicroserviceContract $commissionMks */
    private $commissionMks;

    /** @var AuthMicroserviceContract  $authMks */
    private $authMks;

    /**
     * Create a new command instance.
     *
     * @param ReportServiceContract $reportService
     * @param NotifyMicroserviceContract $notifyMks
     * @param CommissionCalculationMicroserviceContract $commissionMks
     * @param AuthMicroserviceContract $authMks
     */
    public function __construct(
        ReportServiceContract $reportService,
        NotifyMicroserviceContract $notifyMks,
        CommissionCalculationMicroserviceContract $commissionMks,
        AuthMicroserviceContract $authMks
    ) {
        parent::__construct();
        $this->reportService = $reportService;
        $this->notifyMks = $notifyMks;
        $this->commissionMks = $commissionMks;
        $this->authMks = $authMks;
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle()
    {
        $this->login();
        $option = $this->commissionMks->getOptions('allow_pay_requests');

        if ((int)$option['content']['value'] === 0) {
            $status = $this->reportService->getProcessingStatus();

            if ((float)$status['sum'] > 0) {
                $data = [
                    'sender' => config('api.notify_sender'),
                    'sum' => $status['sum']
                ];
                $this->notifyMks->sendMail(config('api.qiwi.balanceNotifyEmail'), $data, 'qiwi_balance');
            }
        }
    }

    /**
     * @throws Exception
     */
    private function login()
    {
        $token = $this->authMks->login([
            'email' => config('api.service_auth.login'),
            'password' => config('api.service_auth.password'),
            'g-recaptcha-response' => config('api.service_auth.token'),
        ]);

        if (!$token || (isset($token['error']) && $token['error'])) {
            throw new Exception('E-mail и пароль указаны не верно');
        }

        GlobalStorage::setUserToken($token['content']['access_token']);
    }
}
