<?php


namespace App\Providers;


use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Company\Renessans\RenessansMasterServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffMasterServiceContract;
use App\Services\Company\Ingosstrah\IngosstrahBillLinkService;
use App\Services\Company\Ingosstrah\IngosstrahBillService;
use App\Services\Company\Ingosstrah\IngosstrahBillStatusService;
use App\Services\Company\Ingosstrah\IngosstrahCalculateService;
use App\Services\Company\Ingosstrah\IngosstrahCheckCreateService;
use App\Services\Company\Ingosstrah\IngosstrahCreateService;
use App\Services\Company\Ingosstrah\IngosstrahEosagoService;
use App\Services\Company\Ingosstrah\IngosstrahLoginService;
use App\Services\Company\Renessans\RenessansBillLinkService;
use App\Services\Company\Renessans\RenessansCalculateService;
use App\Services\Company\Renessans\RenessansCheckCalculateService;
use App\Services\Company\Renessans\RenessansCheckCreateService;
use App\Services\Company\Renessans\RenessansCreateService;
use App\Services\Company\Renessans\RenessansGetStatusService;
use App\Services\Company\Renessans\RenessansMasterService;
use App\Services\Company\Soglasie\SoglasieCalculateService;
use App\Services\Company\Soglasie\SoglasieCancelCreateService;
use App\Services\Company\Soglasie\SoglasieCheckCreateService;
use App\Services\Company\Soglasie\SoglasieCreateService;
use App\Services\Company\Soglasie\SoglasieKbmService;
use App\Services\Company\Soglasie\SoglasieScoringService;
use App\Services\Company\Tinkoff\TinkoffBillLinkService;
use App\Services\Company\Tinkoff\TinkoffCalculateService;
use App\Services\Company\Tinkoff\TinkoffCreateService;
use App\Services\Company\Tinkoff\TinkoffMasterService;

class CompanyServiceProvider
{
    public function register()
    {
        $this->registerCompanyServices();
    }

    public function registerCompanyServices()
    {
        $this->registerRenessansServices();
        $this->registerTinkoffServices();

        //ingosstrah
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
        $this->app->singleton(IngosstrahBillStatusServiceContract::class, function($app) {
            return new IngosstrahBillStatusService();
        });
        //soglasie
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
    }

    protected function registerRenessansServices()
    {
        // master
        $this->app->singleton(RenessansMasterServiceContract::class, RenessansMasterService::class);
        // calculate
        $this->app->singleton(RenessansCalculateServiceContract::class, RenessansCalculateService::class);
        $this->app->singleton(RenessansCheckCalculateServiceContract::class, RenessansCheckCalculateService::class);
        // create
        $this->app->singleton(RenessansCreateServiceContract::class, RenessansCreateService::class);
        $this->app->singleton(RenessansCheckCreateServiceContract::class, RenessansCheckCreateService::class);
        $this->app->singleton(RenessansBillLinkServiceContract::class, RenessansBillLinkService::class);
        // payment
        $this->app->singleton(RenessansGetStatusServiceContract::class, RenessansGetStatusService::class);
    }

    protected function registerTinkoffServices()
    {
        // master
        $this->app->singleton(TinkoffMasterServiceContract::class, TinkoffMasterService::class);
        // calculate
        $this->app->singleton(TinkoffCalculateServiceContract::class, TinkoffCalculateService::class);
        // create
        $this->app->singleton(TinkoffCreateServiceContract::class, TinkoffCreateService::class);
        $this->app->singleton(TinkoffBillLinkServiceContract::class, TinkoffBillLinkService::class);
    }
}
