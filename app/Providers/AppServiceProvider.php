<?php

namespace App\Providers;

use App\Contracts\Company\CompanyServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Services\Company\CompanyService;
use App\Services\Company\Renessans\RenessansCalculateService;
use App\Services\Company\Renessans\RenessansCreateService;
use App\Services\Company\Tinkoff\TinkoffCalculateService;
use Illuminate\Support\ServiceProvider;

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
        $this->app->singleton(RenessansCreateServiceContract::class, function($app) {
            return new RenessansCreateService();
        });
        $this->app->singleton(TinkoffCalculateServiceContract::class, function($app) {
            return new TinkoffCalculateService();
        });
        $this->app->singleton(CompanyServiceContract::class, function($app) {
            return new CompanyService();
        });
    }
}
