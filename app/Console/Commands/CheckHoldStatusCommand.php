<?php

namespace App\Console\Commands;

use App\Http\Controllers\InsuranceController;
use Illuminate\Console\Command;

class CheckHoldStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:hold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'обработка полисов, по которым в связи с задержками СК был проставлен статус hold, т.е. владелец уведомлен о том, что полис создан но недоработан';

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
        $controller->getHold();
    }
}
