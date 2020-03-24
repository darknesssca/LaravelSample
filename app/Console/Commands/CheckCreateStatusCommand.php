<?php

namespace App\Console\Commands;

use App\Http\Controllers\InsuranceController;
use Illuminate\Console\Command;

class CheckCreateStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:processing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'обработка созданных но не обсчитанных СК полисов (статус полисов processing)';

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
        $controller = new InsuranceController();
        $controller->getCreateStatus();
    }
}
