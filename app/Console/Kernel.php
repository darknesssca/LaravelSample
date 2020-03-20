<?php

namespace App\Console;

use App\Console\Commands\CheckCalculateStatusCommand;
use App\Console\Commands\CheckCreateStatusCommand;
use App\Console\Commands\CheckHoldStatusCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CheckCreateStatusCommand::class,
        CheckHoldStatusCommand::class,
        CheckCalculateStatusCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
