<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;

class HoldingJob extends Job
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->holding();
        dispatch((new HoldingJob)->onQueue('holding'));
    }

    public function failed()
    {
        dispatch((new HoldingJob)->onQueue('holding'));
    }
}
