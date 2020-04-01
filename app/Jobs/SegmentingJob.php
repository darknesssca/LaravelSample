<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;

class SegmentingJob extends Job
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->segmenting();
        dispatch((new SegmentingJob)->onQueue('segmenting'));
    }
}
