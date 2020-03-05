<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Company\RenessansService;
use App\Contracts\Company\RenessansServiceContract;

class AppServiceProvider extends ServiceProvider implements RenessansServiceContractAlias
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerServices();
    }

    public function registerServices()
    {
        $this->app->singleton(RenessansServiceContract::class, function($app) {
            return new RenessansService();
        });
    }
}
