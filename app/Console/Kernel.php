<?php

namespace App\Console;


use App\Console\Commands\CheckFreezeReportsCommand;
use App\Console\Commands\DispatchProcessing;
use App\Console\Commands\RefreshInsuranceGuidesCommand;
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
        DispatchProcessing::class,
        RefreshInsuranceGuidesCommand::class,
        CheckFreezeReportsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command("benfin:guides")->weekly()->mondays();
        $schedule->command("benfin:check_freeze")->daily();
    }
}
