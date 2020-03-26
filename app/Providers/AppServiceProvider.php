<?php

namespace App\Providers;

use App\Contracts\Company\CompanyServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
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
use App\Contracts\Services\PolicyServiceContract;
use App\Models\Policy;
use App\Services\Company\CompanyService;
use App\Services\Company\Ingosstrah\IngosstrahBillLinkService;
use App\Services\Company\Ingosstrah\IngosstrahBillService;
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
        $this->app->singleton(CompanyServiceContract::class, function($app) {
            return new CompanyService();
        });
        //renessans
        $this->app->singleton(RenessansServiceContract::class, function($app) {
            return new RenessansService();
        });
        $this->app->singleton(RenessansCalculateServiceContract::class, function($app) {
            return new RenessansCalculateService();
        });
        $this->app->singleton(RenessansCheckCalculateServiceContract::class, function($app) {
            return new RenessansCheckCalculateService();
        });
        $this->app->singleton(RenessansCreateServiceContract::class, function($app) {
            return new RenessansCreateService();
        });
        $this->app->singleton(RenessansCheckCreateServiceContract::class, function($app) {
            return new RenessansCheckCreateService();
        });
        $this->app->singleton(RenessansGetStatusServiceContract::class, function($app) {
            return new RenessansGetStatusService();
        });
        $this->app->singleton(RenessansBillLinkServiceContract::class, function($app) {
            return new RenessansBillLinkService();
        });
        //tinkoff
        $this->app->singleton(TinkoffServiceContract::class, function($app) {
            return new TinkoffService();
        });
        $this->app->singleton(TinkoffCalculateServiceContract::class, function($app) {
            return new TinkoffCalculateService();
        });
        $this->app->singleton(TinkoffCreateServiceContract::class, function($app) {
            return new TinkoffCreateService();
        });
        $this->app->singleton(TinkoffBillLinkServiceContract::class, function($app) {
            return new TinkoffBillLinkService();
        });
        //ingosstrah
        $this->app->singleton(IngosstrahServiceContract::class, function($app) {
            return new IngosstrahService();
        });
        $this->app->singleton(IngosstrahLoginServiceContract::class, function($app) {
            return new IngosstrahLoginService();
        });
        $this->app->singleton(IngosstrahCalculateServiceContract::class, function($app) {
            return new IngosstrahCalculateService();
        });
        $this->app->singleton(IngosstrahCreateServiceContract::class, function($app) {
            return new IngosstrahCreateService();
        });
        $this->app->singleton(IngosstrahCheckCreateServiceContract::class, function($app) {
            return new IngosstrahCheckCreateService();
        });
        $this->app->singleton(IngosstrahEosagoServiceContract::class, function($app) {
            return new IngosstrahEosagoService();
        });
        $this->app->singleton(IngosstrahBillServiceContract::class, function($app) {
            return new IngosstrahBillService();
        });
        $this->app->singleton(IngosstrahBillLinkServiceContract::class, function($app) {
            return new IngosstrahBillLinkService();
        });
        //soglasie
        $this->app->singleton(SoglasieServiceContract::class, function($app) {
            return new SoglasieService();
        });
        $this->app->singleton(SoglasieKbmServiceContract::class, function($app) {
            return new SoglasieKbmService();
        });
        $this->app->singleton(SoglasieScoringServiceContract::class, function($app) {
            return new SoglasieScoringService();
        });
        $this->app->singleton(SoglasieCalculateServiceContract::class, function($app) {
            return new SoglasieCalculateService();
        });
        $this->app->singleton(SoglasieCreateServiceContract::class, function($app) {
            return new SoglasieCreateService();
        });
        $this->app->singleton(SoglasieCheckCreateServiceContract::class, function($app) {
            return new SoglasieCheckCreateService();
        });
        $this->app->singleton(SoglasieCancelCreateServiceContract::class, function($app) {
            return new SoglasieCancelCreateService();
        });
        $this->app->singleton(PolicyServiceContract::class, function () {
            return new PolicyService();
        });
    }
}
