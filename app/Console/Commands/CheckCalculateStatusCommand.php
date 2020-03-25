<?php

namespace App\Console\Commands;

use App\Http\Controllers\InsuranceController;
use Illuminate\Console\Command;

class CheckCalculateStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'получение результатов вторичного рассчета цены (ренессанс)';

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
        $controller->getCalculate();
    }
}
