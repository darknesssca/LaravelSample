<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
use App\Contracts\Utils\DeferredResultContract;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

class QiwiExecutePayoutJob extends QiwiJob
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
        /** @var ReportServiceContract $reportService */

        GlobalStorage::setUser($this->params['user']);
        $this->login();

        if ($this->getAllowPayRequests() == 1) {
            $reportService = app(ReportServiceContract::class);
            $report = $this->getReport($this->params['report_id']);

            try {
                $reportService->executePayout($report);
                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->done($deferredResultId);
                }
                dispatch((new QiwiCreateXlsJob($this->params))->onQueue('QiwiCreateXls'));
            } catch (PayoutInsufficientFundsException $exception) {
                $this->disableAllowPayRequests();
                $this->sendNotify();
                Queue::later(
                    Carbon::now()->addSeconds(config('api_sk.qiwi.requestInterval')),
                    new QiwiExecutePayoutJob($this->params),
                    '',
                    'QiwiExecutePayout'
                );
            }
        } else {
            Queue::later(
                Carbon::now()->addSeconds(config('api_sk.qiwi.requestInterval')),
                new QiwiExecutePayoutJob($this->params),
                '',
                'QiwiExecutePayout'
            );
        }

    }

    public function failed(Exception $exception)
    {
        Queue::later(
            Carbon::now()->addSeconds(config('api_sk.qiwi.requestInterval')),
            new QiwiExecutePayoutJob($this->params),
            '',
            'QiwiExecutePayout'
        );
    }
}
