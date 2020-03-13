<?php

namespace App\Console\Commands;

use App\Http\Controllers\InsuranceController;
use Illuminate\Console\Command;

class CheckRenessancCalculateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renessans:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get calculate response';

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
