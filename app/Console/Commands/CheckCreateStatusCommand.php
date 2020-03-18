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
    protected $signature = 'check:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get create response';

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
