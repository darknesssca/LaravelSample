<?php

namespace App\Console\Commands;

use App\Http\Controllers\InsuranceController;
use Illuminate\Console\Command;

class RefreshInsuranceGuidesCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benfin:guides';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refresh car models guides';

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
        echo "----Начало обновления справочников----\n";
        //модели и марки машин
        $controller = new InsuranceController();
        $controller->refreshGuides();

        echo "----Конец обновления справочников----\n";
    }
}
