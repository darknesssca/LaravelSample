<?php

namespace App\Providers;

use App\Contracts\Company\CompanyServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Services\Company\CompanyService;
use App\Services\Company\Ingosstrah\IngosstrahCalculateService;
use App\Services\Company\Ingosstrah\IngosstrahLoginService;
use App\Services\Company\Ingosstrah\IngosstrahService;
use App\Services\Company\Renessans\RenessansCalculateService;
use App\Services\Company\Renessans\RenessansCreateService;
use App\Services\Company\Renessans\RenessansService;
use App\Services\Company\Tinkoff\TinkoffCalculateService;
use App\Services\Company\Tinkoff\TinkoffService;
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
        $this->app->singleton(CompanyServiceContract::class, function($app) {
            return new CompanyService();
        });
        $this->app->singleton(RenessansServiceContract::class, function($app) {
            return new RenessansService();
        });
        $this->app->singleton(TinkoffServiceContract::class, function($app) {
            return new TinkoffService();
        });
        $this->app->singleton(IngosstrahServiceContract::class, function($app) {
            return new IngosstrahService();
        });
        $this->app->singleton(RenessansCalculateServiceContract::class, function($app) {
            return new RenessansCalculateService();
        });
        $this->app->singleton(RenessansCreateServiceContract::class, function($app) {
            return new RenessansCreateService();
        });
        $this->app->singleton(TinkoffCalculateServiceContract::class, function($app) {
            return new TinkoffCalculateService();
        });
        $this->app->singleton(IngosstrahLoginServiceContract::class, function($app) {
            return new IngosstrahLoginService();
        });
        $this->app->singleton(IngosstrahCalculateServiceContract::class, function($app) {
            return new IngosstrahCalculateService();
        });
    }
}
