<?php


namespace App\Jobs\Qiwi;



use App\Contracts\Services\ReportServiceContract;
use App\Contracts\Utils\DeferredResultContract;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use Benfin\Api\GlobalStorage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

class QiwiGetPayoutStatusJob extends QiwiJob
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function handle() {
        /** @var ReportServiceContract $reportService */

        GlobalStorage::setUser($this->params['user']);
        $this->login();

        if ($this->getAllowPayRequests() == 1) {
            $reportService = app(ReportServiceContract::class);
            $report = $this->getReport($this->params['report_id']);

            try {
                $result = $reportService->getPayoutStatus($report);
                if (!$result) {
                    return;
                }
                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->done($deferredResultId);
                }
                dispatch((new QiwiCreateXlsJob($this->params))->onQueue('QiwiCreateXls'));
            } catch (PayoutInsufficientFundsException $exception) {
                $this->disableAllowPayRequests();
                $status = $reportService->getProcessingStatus();
                $this->sendNotify($status['sum']);
                Queue::later(
                    Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                    new QiwiGetPayoutStatusJob($this->params),
                    '',
                    'QiwiGetPayoutStatus'
                );
            }
        }
    }
}