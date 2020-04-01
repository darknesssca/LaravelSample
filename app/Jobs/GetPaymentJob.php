<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;
use Illuminate\Support\Carbon;

class GetPaymentJob extends Job
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->getPayment();
        dispatch((new GetPaymentJob)
            ->onQueue('getPayment'))
            ->delay(Carbon::now()->addMinutes(config('api_sk.processingGetPaymentDelay')));
    }
}
