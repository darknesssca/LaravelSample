<?php

namespace App\Console\Commands;

use App\Contracts\Company\ProcessingServiceContract;
use Illuminate\Console\Command;

class DispatchProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benfin:processing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'отправка всех процессингов в очереди';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $service = app(ProcessingServiceContract::class);
        $service->initDispatch();
    }
}
