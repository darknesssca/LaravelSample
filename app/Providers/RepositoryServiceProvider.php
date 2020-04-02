<?php

namespace App\Providers;


use App\Contracts\Repositories\BillPolicyRepositoryContract;
use App\Contracts\Repositories\DraftClientRepositoryContract;
use App\Contracts\Repositories\DriverRepositoryContract;
use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\PolicyTypeRepositoryContract;
use App\Contracts\Repositories\ReportRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Contracts\Repositories\Services\DraftServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Contracts\Services\ReportServiceContract;
use App\Repositories\BillPolicyRepository;
use App\Repositories\DraftClientRepository;
use App\Repositories\DriverRepository;
use App\Repositories\InsuranceCompanyRepository;
use App\Repositories\IntermediateDataRepository;
use App\Repositories\PolicyRepository;
use App\Repositories\PolicyTypeRepository;
use App\Repositories\ReportRepository;
use App\Repositories\RequestProcessRepository;
use App\Services\Drafts\DraftService;
use App\Services\PolicyService;
use App\Services\Qiwi\ReportService;
use App\Services\Repositories\InsuranceCompanyService;
use App\Services\Repositories\IntermediateDataService;
use App\Services\Repositories\RequestProcessService;
use App\Contracts\Repositories\DraftRepositoryContract;
use App\Repositories\DraftRepository;
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
        $this->app->bind(ReportRepositoryContract::class, ReportRepository::class);
    }

    protected function registerRepositoryServiceProviders()
    {
        $this->app->singleton(IntermediateDataServiceContract::class, IntermediateDataService::class);
        $this->app->singleton(InsuranceCompanyServiceContract::class, InsuranceCompanyService::class);
        $this->app->singleton(RequestProcessServiceContract::class, RequestProcessService::class);
        $this->app->singleton(PolicyServiceContract::class, PolicyService::class);
        $this->app->bind(DraftServiceContract::class, DraftService::class);
        $this->app->bind(ReportServiceContract::class, ReportService::class);
    }
}
