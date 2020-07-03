<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;

class CreatingJob extends Job
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->creating();
        dispatch((new CreatingJob)->onQueue('creating'));
    }

    public function failed()
    {
        dispatch((new CreatingJob)->onQueue('creating'));
    }
}
