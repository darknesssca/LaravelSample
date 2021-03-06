<?php


namespace App\Providers;


use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahGuidesSourceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahMasterServiceContract;
use App\Contracts\Company\ProcessingServiceContract;
use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetPdfServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Company\Renessans\RenessansGuidesSourceContract;
use App\Contracts\Company\Renessans\RenessansMasterServiceContract;
use App\Contracts\Company\Soglasie\SoglasieBillLinkServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieGuidesSourceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieMasterServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffGuidesSourceContract;
use App\Contracts\Company\Tinkoff\TinkoffMasterServiceContract;
use App\Contracts\Company\Vsk\VskBuyPolicyServiceContract as VskOsagoBuyPolicyServiceContract;
use App\Contracts\Company\Vsk\VskCalculatePolicyServiceContract as VskOsagoCalculatePolicyServiceContract;
use App\Contracts\Company\Vsk\VskCallbackServiceContract as VskOsagoCallbackServiceContract;
use App\Contracts\Company\Vsk\VskErrorHandlerServiceContract as VskOsagoErrorHandlerServiceContract;
use App\Contracts\Company\Vsk\VskLoginServiceContract as VskOsagoLoginServiceContract;
use App\Contracts\Company\Vsk\VskMasterServiceContract as VskOsagoMasterServiceContract;
use App\Contracts\Company\Vsk\VskSavePolicyServiceContract as VskOsagoSavePolicyServiceContract;
use App\Contracts\Company\Vsk\VskSignPolicyServiceContract as VskOsagoSignPolicyServiceContract;
use App\Services\Company\Ingosstrah\IngosstrahBillLinkService;
use App\Services\Company\Ingosstrah\IngosstrahBillService;
use App\Services\Company\Ingosstrah\IngosstrahBillStatusService;
use App\Services\Company\Ingosstrah\IngosstrahCalculateService;
use App\Services\Company\Ingosstrah\IngosstrahCheckCreateService;
use App\Services\Company\Ingosstrah\IngosstrahCreateService;
use App\Services\Company\Ingosstrah\IngosstrahEosagoService;
use App\Services\Company\Ingosstrah\IngosstrahGuidesService;
use App\Services\Company\Ingosstrah\IngosstrahLoginService;
use App\Services\Company\Ingosstrah\IngosstrahMasterService;
use App\Services\Company\ProcessingService;
use App\Services\Company\Renessans\RenessansBillLinkService;
use App\Services\Company\Renessans\RenessansCalculateService;
use App\Services\Company\Renessans\RenessansCheckCalculateService;
use App\Services\Company\Renessans\RenessansCheckCreateService;
use App\Services\Company\Renessans\RenessansCreateService;
use App\Services\Company\Renessans\RenessansGetPdfService;
use App\Services\Company\Renessans\RenessansGetStatusService;
use App\Services\Company\Renessans\RenessansGuidesService;
use App\Services\Company\Renessans\RenessansMasterService;
use App\Services\Company\Soglasie\SoglasieBillLinkService;
use App\Services\Company\Soglasie\SoglasieCalculateService;
use App\Services\Company\Soglasie\SoglasieCancelCreateService;
use App\Services\Company\Soglasie\SoglasieCheckCreateService;
use App\Services\Company\Soglasie\SoglasieCreateService;
use App\Services\Company\Soglasie\SoglasieGuidesService;
use App\Services\Company\Soglasie\SoglasieKbmService;
use App\Services\Company\Soglasie\SoglasieMasterService;
use App\Services\Company\Soglasie\SoglasieScoringService;
use App\Services\Company\Tinkoff\TinkoffBillLinkService;
use App\Services\Company\Tinkoff\TinkoffCalculateService;
use App\Services\Company\Tinkoff\TinkoffCreateService;
use App\Services\Company\Tinkoff\TinkoffGuidesService;
use App\Services\Company\Tinkoff\TinkoffMasterService;
use App\Services\Company\Vsk\VskBuyPolicyService as VskOsagoBuyPolicyService;
use App\Services\Company\Vsk\VskCalculatePolicyService as VskOsagoCalculatePolicyService;
use App\Services\Company\Vsk\VskCallbackService as VskOsagoCallbackService;
use App\Services\Company\Vsk\VskErrorHandlerService as VskOsagoErrorHandlerService;
use App\Services\Company\Vsk\VskLoginService as VskOsagoLoginService;
use App\Services\Company\Vsk\VskMasterService as VskOsagoMasterService;
use App\Services\Company\Vsk\VskSavePolicyService as VskOsagoSavePolicyService;
use App\Services\Company\Vsk\VskSignPolicyService as VskOsagoSignPolicyService;
use Illuminate\Support\ServiceProvider;

class CompanyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerCompanyServices();
    }

    public function registerCompanyServices()
    {
        $this->registerRenessansServices();
        $this->registerTinkoffServices();
        $this->registerIngosstrahServices();
        $this->registerSoglasieServices();
        $this->registerVskOsagoServices();

        // сервис процессингов
        $this->app->bind(ProcessingServiceContract::class, ProcessingService::class);
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
        //guides
        $this->app->singleton(RenessansGuidesSourceContract::class, RenessansGuidesService::class);
        //pdf
        $this->app->singleton(RenessansGetPdfServiceContract::class, RenessansGetPdfService::class);
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
        //guides
        $this->app->singleton(TinkoffGuidesSourceContract::class, TinkoffGuidesService::class);
    }

    protected function registerIngosstrahServices()
    {
        // master
        $this->app->singleton(IngosstrahMasterServiceContract::class, IngosstrahMasterService::class);
        // login
        $this->app->singleton(IngosstrahLoginServiceContract::class, IngosstrahLoginService::class);
        // calculate
        $this->app->singleton(IngosstrahCalculateServiceContract::class, IngosstrahCalculateService::class);
        // create
        $this->app->singleton(IngosstrahCreateServiceContract::class, IngosstrahCreateService::class);
        $this->app->singleton(IngosstrahCheckCreateServiceContract::class, IngosstrahCheckCreateService::class);
        $this->app->singleton(IngosstrahEosagoServiceContract::class, IngosstrahEosagoService::class);
        $this->app->singleton(IngosstrahBillServiceContract::class, IngosstrahBillService::class);
        $this->app->singleton(IngosstrahBillLinkServiceContract::class, IngosstrahBillLinkService::class);
        // payment
        $this->app->singleton(IngosstrahBillStatusServiceContract::class, IngosstrahBillStatusService::class);
        //guides
        $this->app->singleton(IngosstrahGuidesSourceContract::class, IngosstrahGuidesService::class);
    }

    protected function registerSoglasieServices()
    {
        // master
        $this->app->singleton(SoglasieMasterServiceContract::class, SoglasieMasterService::class);
        // calculate
        $this->app->singleton(SoglasieKbmServiceContract::class, SoglasieKbmService::class);
        $this->app->singleton(SoglasieScoringServiceContract::class, SoglasieScoringService::class);
        $this->app->singleton(SoglasieCalculateServiceContract::class, SoglasieCalculateService::class);
        // create
        $this->app->singleton(SoglasieCreateServiceContract::class, SoglasieCreateService::class);
        $this->app->singleton(SoglasieCheckCreateServiceContract::class, SoglasieCheckCreateService::class);
        $this->app->singleton(SoglasieCancelCreateServiceContract::class, SoglasieCancelCreateService::class);
        $this->app->singleton(SoglasieBillLinkServiceContract::class, SoglasieBillLinkService::class);
        //guides
        $this->app->singleton(SoglasieGuidesSourceContract::class, SoglasieGuidesService::class);
    }

    protected function registerVskOsagoServices()
    {
        // master
        $this->app->singleton(VskOsagoMasterServiceContract::class, VskOsagoMasterService::class);
        //callback
        $this->app->singleton(VskOsagoCallbackServiceContract::class, VskOsagoCallbackService::class);
        //login
        $this->app->singleton(VskOsagoLoginServiceContract::class, VskOsagoLoginService::class);
        //calculate
        $this->app->singleton(VskOsagoCalculatePolicyServiceContract::class, VskOsagoCalculatePolicyService::class);
        //create
        $this->app->singleton(VskOsagoSavePolicyServiceContract::class, VskOsagoSavePolicyService::class);
        $this->app->singleton(VskOsagoSignPolicyServiceContract::class, VskOsagoSignPolicyService::class);
        $this->app->singleton(VskOsagoBuyPolicyServiceContract::class, VskOsagoBuyPolicyService::class);
        //error handler
        $this->app->singleton(VskOsagoErrorHandlerServiceContract::class, VskOsagoErrorHandlerService::class);
    }
}
