<?php


namespace App\Console\Commands;


use App\Models\IntermediateData;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearIntermediateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benfin:clear-intermediate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаляет устаревшие записи из таблицы intermediate_data';

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
     * @return void
     */
    public function handle()
    {
        IntermediateData::query()
            ->where('created_at', '<', Carbon::now()->subDay()->format('Y-m-d H:i:s'))
            ->delete();
    }
}
