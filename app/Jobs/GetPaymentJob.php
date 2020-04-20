<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

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
        $delay = Carbon::now()->addMinutes(config('api_sk.processingGetPaymentDelay'));
        Queue::later(
            $delay,
            new GetPaymentJob(),
            '',
            'getPayment'
        );
    }
}
