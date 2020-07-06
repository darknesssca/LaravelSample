<?php

namespace App\Jobs;

use App\Contracts\Company\ProcessingServiceContract;

class SegmentCalculatingJob extends Job
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->segmentCalculating();
        dispatch((new SegmentCalculatingJob)->onQueue('segmentCalculating'));
    }

    public function failed()
    {
        dispatch((new SegmentCalculatingJob)->onQueue('segmentCalculating'));
    }
}
