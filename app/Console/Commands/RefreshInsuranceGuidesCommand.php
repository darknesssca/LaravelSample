<?php

namespace App\Console\Commands;

use App\Services\Company\InsuranceGuides;
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
        //модели, страны и марки машин
        InsuranceGuides::refreshGuides();
        echo "----Конец обновления справочников----\n";
    }
}
