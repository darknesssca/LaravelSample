<?php

namespace App\Providers;


use App\Contracts\Repositories\AddressTypeRepositoryContract;
use App\Contracts\Repositories\BillPolicyRepositoryContract;
use App\Contracts\Repositories\CarCategoryRepositoryContract;
use App\Contracts\Repositories\CarMarkRepositoryContract;
use App\Contracts\Repositories\CarModelRepositoryContract;
use App\Contracts\Repositories\CountryRepositoryContract;
use App\Contracts\Repositories\DocTypeRepositoryContract;
use App\Contracts\Repositories\DraftClientRepositoryContract;
use App\Contracts\Repositories\DriverRepositoryContract;
use App\Contracts\Repositories\GenderRepositoryContract;
use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\PolicyTypeRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Contracts\Repositories\Services\AddressTypeServiceContract;
use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\DraftServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\PolicyTypeServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\SourceAcquisitionServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Repositories\SourceAcquisitionRepositoryContract;
use App\Contracts\Repositories\UsageTargetRepositoryContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Repositories\AddressTypeRepository;
use App\Repositories\BillPolicyRepository;
use App\Repositories\CarCategoryRepository;
use App\Repositories\CarMarkRepository;
use App\Repositories\CarModelRepository;
use App\Repositories\CountryRepository;
use App\Repositories\DocTypeRepository;
use App\Repositories\DraftClientRepository;
use App\Repositories\DriverRepository;
use App\Repositories\GenderRepository;
use App\Repositories\InsuranceCompanyRepository;
use App\Repositories\IntermediateDataRepository;
use App\Repositories\PolicyRepository;
use App\Repositories\PolicyTypeRepository;
use App\Repositories\RequestProcessRepository;
use App\Repositories\SourceAcquisitionRepository;
use App\Repositories\UsageTargetRepository;
use App\Services\Drafts\DraftService;
use App\Services\PolicyService;
use App\Services\Repositories\AddressTypeService;
use App\Services\Repositories\CarCategoryService;
use App\Services\Repositories\CarMarkService;
use App\Services\Repositories\CarModelService;
use App\Services\Repositories\CountryService;
use App\Services\Repositories\DocTypeService;
use App\Services\Repositories\GenderService;
use App\Services\Repositories\InsuranceCompanyService;
use App\Services\Repositories\IntermediateDataService;
use App\Services\Repositories\PolicyTypeService;
use App\Services\Repositories\RequestProcessService;
use App\Contracts\Repositories\DraftRepositoryContract;
use App\Repositories\DraftRepository;
use App\Services\Repositories\SourceAcquisitionService;
use App\Services\Repositories\UsageTargetService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerRepositoryProviders();
        $this->registerRepositoryServiceProviders();
    }

    protected function registerRepositoryProviders()
    {
        $this->app->bind(InsuranceCompanyRepositoryContract::class, InsuranceCompanyRepository::class);
        $this->app->bind(IntermediateDataRepositoryContract::class, IntermediateDataRepository::class);
        $this->app->bind(RequestProcessRepositoryContract::class, RequestProcessRepository::class);
        $this->app->bind(PolicyRepositoryContract::class, PolicyRepository::class);
        $this->app->bind(BillPolicyRepositoryContract::class, BillPolicyRepository::class);
        $this->app->bind(DraftRepositoryContract::class, DraftRepository::class);
        $this->app->bind(PolicyTypeRepositoryContract::class, PolicyTypeRepository::class);
        $this->app->bind(DriverRepositoryContract::class, DriverRepository::class);
        $this->app->bind(DraftClientRepositoryContract::class, DraftClientRepository::class);
        $this->app->bind(CarMarkRepositoryContract::class, CarMarkRepository::class);
        $this->app->bind(CarModelRepositoryContract::class, CarModelRepository::class);
        $this->app->bind(CarCategoryRepositoryContract::class, CarCategoryRepository::class);
        $this->app->bind(CountryRepositoryContract::class, CountryRepository::class);
        $this->app->bind(GenderRepositoryContract::class, GenderRepository::class);
        $this->app->bind(DocTypeRepositoryContract::class, DocTypeRepository::class);
        $this->app->bind(UsageTargetRepositoryContract::class, UsageTargetRepository::class);
        $this->app->bind(SourceAcquisitionRepositoryContract::class, SourceAcquisitionRepository::class);
        $this->app->bind(AddressTypeRepositoryContract::class, AddressTypeRepository::class);
    }

    protected function registerRepositoryServiceProviders()
    {
        $this->app->singleton(IntermediateDataServiceContract::class, IntermediateDataService::class);
        $this->app->singleton(InsuranceCompanyServiceContract::class, InsuranceCompanyService::class);
        $this->app->singleton(RequestProcessServiceContract::class, RequestProcessService::class);
        $this->app->singleton(PolicyServiceContract::class, PolicyService::class);
        $this->app->bind(DraftServiceContract::class, DraftService::class);
        $this->app->bind(CarMarkServiceContract::class, CarMarkService::class);
        $this->app->bind(CarModelServiceContract::class, CarModelService::class);
        $this->app->bind(CarCategoryServiceContract::class, CarCategoryService::class);
        $this->app->bind(CountryServiceContract::class, CountryService::class);
        $this->app->bind(GenderServiceContract::class, GenderService::class);
        $this->app->bind(DocTypeServiceContract::class, DocTypeService::class);
        $this->app->bind(UsageTargetServiceContract::class, UsageTargetService::class);
        $this->app->bind(SourceAcquisitionServiceContract::class, SourceAcquisitionService::class);
        $this->app->bind(AddressTypeServiceContract::class, AddressTypeService::class);
        $this->app->bind(PolicyTypeServiceContract::class, PolicyTypeService::class);
    }
}
