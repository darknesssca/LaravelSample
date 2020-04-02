<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;

class PreCalculatingJob extends Job
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->preCalculating();
        dispatch((new PreCalculatingJob)->onQueue('preCalculating'));
    }
}
