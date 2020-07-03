<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
use App\Contracts\Utils\DeferredResultContract;
use App\Exceptions\Qiwi\BillingDeclinedException;
use App\Exceptions\Qiwi\PayoutAlreadyExistException;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use App\Exceptions\Qiwi\ResolutionException;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

class QiwiCreatePayoutJob extends QiwiJob
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
                $reportService->createPayout($report);
                dispatch((new QiwiExecutePayoutJob($this->params))->onQueue('QiwiExecutePayout'));
            } catch (BillingDeclinedException $exception) {
                $this->stopProcessing($this->params['report_id'], 1001);
                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->error($deferredResultId, [
                        'errorCode' => 1001,
                        'errorMessage' => $exception->getMessageData(),
                    ]);
                }
            } catch (ResolutionException $exception) {
                $this->stopProcessing($this->params['report_id'], 1002);
                $deferredResultUtil = app(DeferredResultContract::class);
                $deferredResultId = $deferredResultUtil->getId('report', $report->id);
                if ($deferredResultUtil->exist($deferredResultId)) {
                    $deferredResultUtil->error($deferredResultId, [
                        'errorCode' => 1002,
                        'errorMessage' => $exception->getMessageData(),
                    ]);
                }
            } catch (PayoutAlreadyExistException $exception) {
                Queue::later(
                    Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                    new QiwiCreatePayoutJob($this->params),
                    '',
                    'QiwiCreatePayout'
                );
            } catch (PayoutInsufficientFundsException $exception) {
                $this->disableAllowPayRequests();
                $this->sendNotify();
                Queue::later(
                    Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                    new QiwiCreatePayoutJob($this->params),
                    '',
                    'QiwiCreatePayout'
                );
            }
        } else {
            Queue::later(
                Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                new QiwiCreatePayoutJob($this->params),
                '',
                'QiwiCreatePayout'
            );
        }
    }

    public function failed(Exception $exception)
    {
        Queue::later(
            Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
            new QiwiCreatePayoutJob($this->params),
            '',
            'QiwiCreatePayout'
        );
    }
}
