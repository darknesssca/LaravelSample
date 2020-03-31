<?php

namespace App\Providers;

use App\Contracts\Company\CompanyServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahServiceContract;
use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Company\Renessans\RenessansServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Company\Soglasie\SoglasieServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Models\Policy;
use App\Services\Company\CompanyService;
use App\Services\Company\Ingosstrah\IngosstrahBillLinkService;
use App\Services\Company\Ingosstrah\IngosstrahBillService;
use App\Services\Company\Ingosstrah\IngosstrahBillStatusService;
use App\Services\Company\Ingosstrah\IngosstrahCalculateService;
use App\Services\Company\Ingosstrah\IngosstrahCheckCreateService;
use App\Services\Company\Ingosstrah\IngosstrahCreateService;
use App\Services\Company\Ingosstrah\IngosstrahEosagoService;
use App\Services\Company\Ingosstrah\IngosstrahLoginService;
use App\Services\Company\Ingosstrah\IngosstrahService;
use App\Services\Company\Renessans\RenessansBillLinkService;
use App\Services\Company\Renessans\RenessansCalculateService;
use App\Services\Company\Renessans\RenessansCheckCalculateService;
use App\Services\Company\Renessans\RenessansCheckCreateService;
use App\Services\Company\Renessans\RenessansCreateService;
use App\Services\Company\Renessans\RenessansGetStatusService;
use App\Services\Company\Renessans\RenessansService;
use App\Services\Company\Soglasie\SoglasieCalculateService;
use App\Services\Company\Soglasie\SoglasieCancelCreateService;
use App\Services\Company\Soglasie\SoglasieCheckCreateService;
use App\Services\Company\Soglasie\SoglasieCreateService;
use App\Services\Company\Soglasie\SoglasieKbmService;
use App\Services\Company\Soglasie\SoglasieScoringService;
use App\Services\Company\Soglasie\SoglasieService;
use App\Services\Company\Tinkoff\TinkoffBillLinkService;
use App\Services\Company\Tinkoff\TinkoffCalculateService;
use App\Services\Company\Tinkoff\TinkoffCreateService;
use App\Services\Company\Tinkoff\TinkoffService;
use App\Services\PolicyService;
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
        $this->app->singleton(CompanyServiceContract::class, function() {
            return new CompanyService();
        });
        //renessans
        $this->app->singleton(RenessansServiceContract::class, function() {
            return new RenessansService();
        });
        $this->app->singleton(RenessansCalculateServiceContract::class, function() {
            return new RenessansCalculateService();
        });
        $this->app->singleton(RenessansCheckCalculateServiceContract::class, function() {
            return new RenessansCheckCalculateService();
        });
        $this->app->singleton(RenessansCreateServiceContract::class, function() {
            return new RenessansCreateService();
        });
        $this->app->singleton(RenessansCheckCreateServiceContract::class, function() {
            return new RenessansCheckCreateService();
        });
        $this->app->singleton(RenessansGetStatusServiceContract::class, function() {
            return new RenessansGetStatusService();
        });
        $this->app->singleton(RenessansBillLinkServiceContract::class, function() {
            return new RenessansBillLinkService();
        });
        //tinkoff
        $this->app->singleton(TinkoffServiceContract::class, function() {
            return new TinkoffService();
        });
        $this->app->singleton(TinkoffCalculateServiceContract::class, function() {
            return new TinkoffCalculateService();
        });
        $this->app->singleton(TinkoffCreateServiceContract::class, function() {
            return new TinkoffCreateService();
        });
        $this->app->singleton(TinkoffBillLinkServiceContract::class, function() {
            return new TinkoffBillLinkService();
        });
        //ingosstrah
        $this->app->singleton(IngosstrahServiceContract::class, function() {
            return new IngosstrahService();
        });
        $this->app->singleton(IngosstrahLoginServiceContract::class, function() {
            return new IngosstrahLoginService();
        });
        $this->app->singleton(IngosstrahCalculateServiceContract::class, function() {
            return new IngosstrahCalculateService();
        });
        $this->app->singleton(IngosstrahCreateServiceContract::class, function() {
            return new IngosstrahCreateService();
        });
        $this->app->singleton(IngosstrahCheckCreateServiceContract::class, function() {
            return new IngosstrahCheckCreateService();
        });
        $this->app->singleton(IngosstrahEosagoServiceContract::class, function() {
            return new IngosstrahEosagoService();
        });
        $this->app->singleton(IngosstrahBillServiceContract::class, function() {
            return new IngosstrahBillService();
        });
        $this->app->singleton(IngosstrahBillLinkServiceContract::class, function() {
            return new IngosstrahBillLinkService();
        });
        $this->app->singleton(IngosstrahBillStatusServiceContract::class, function($app) {
            return new IngosstrahBillStatusService();
        });
        //soglasie
        $this->app->singleton(SoglasieServiceContract::class, function() {
            return new SoglasieService();
        });
        $this->app->singleton(SoglasieKbmServiceContract::class, function() {
            return new SoglasieKbmService();
        });
        $this->app->singleton(SoglasieScoringServiceContract::class, function() {
            return new SoglasieScoringService();
        });
        $this->app->singleton(SoglasieCalculateServiceContract::class, function() {
            return new SoglasieCalculateService();
        });
        $this->app->singleton(SoglasieCreateServiceContract::class, function() {
            return new SoglasieCreateService();
        });
        $this->app->singleton(SoglasieCheckCreateServiceContract::class, function() {
            return new SoglasieCheckCreateService();
        });
        $this->app->singleton(SoglasieCancelCreateServiceContract::class, function() {
            return new SoglasieCancelCreateService();
        });
        $this->app->singleton(PolicyServiceContract::class, function ($app) {
            return new PolicyService($app->make(PolicyRepositoryContract::class));
        });
    }
}
