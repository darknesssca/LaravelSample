<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
use App\Contracts\Utils\DeferredResultContract;
use App\Exceptions\Qiwi\BillingDeclinedException;
use App\Exceptions\Qiwi\ExecutePayoutException;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use App\Exceptions\QiwiCreatePayoutException;
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
                $execResult = $reportService->executePayout($report);

                if (!$execResult) {
                    return;
                }

                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->done($deferredResultId, $report->id);
                }
                dispatch((new QiwiCreateXlsJob($this->params))->onQueue('QiwiCreateXls'));
            } catch (PayoutInsufficientFundsException $exception) {
                $this->disableAllowPayRequests();
                $status = $reportService->getProcessingStatus();
                $this->sendNotify($status['sum']);
                Queue::later(
                    Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                    new QiwiExecutePayoutJob($this->params),
                    '',
                    'QiwiExecutePayout'
                );
            } catch (BillingDeclinedException $exception) {
                $this->stopProcessing($this->params['report_id'], 1001);
                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->error($deferredResultId, [
                        'errorCode' => 1001,
                        'errorMessage' => $exception->getMessageData(),
                        'fail' => true,
                        'redirect' => true,
                    ]);
                }
            } catch (ExecutePayoutException $exception) {
                $this->stopProcessing($this->params['report_id'], 1001);
                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->error($deferredResultId, [
                        'errorCode' => 1010,
                        'errorMessage' => 'Произошла ошибка',
                        'fail' => true,
                        'redirect' => false,
                    ]);
                }
            }
        } else {
            Queue::later(
                Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                new QiwiExecutePayoutJob($this->params),
                '',
                'QiwiExecutePayout'
            );
        }

    }

    public function failed(Exception $exception)
    {
        Queue::later(
            Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
            new QiwiExecutePayoutJob($this->params),
            '',
            'QiwiExecutePayout'
        );
    }
}
