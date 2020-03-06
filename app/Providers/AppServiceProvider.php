<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Services\Company\Renessans\RenessansCalculateService;

class AppServiceProvider extends ServiceProvider
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
        $this->app->singleton(RenessansCalculateServiceContract::class, function($app) {
            return new RenessansCalculateService();
        });
    }
}
