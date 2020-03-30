<?php


namespace App\Providers;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Repositories\InsuranceCompanyRepository;
use App\Repositories\IntermediateDataRepository;
use App\Repositories\PolicyRepository;
use App\Repositories\RequestProcessRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerRepositoryProviders();
    }

    protected function registerRepositoryProviders()
    {
        $this->app->singleton(InsuranceCompanyRepositoryContract::class, InsuranceCompanyRepository::class);
        $this->app->singleton(IntermediateDataRepositoryContract::class, IntermediateDataRepository::class);
        $this->app->singleton(RequestProcessRepositoryContract::class, RequestProcessRepository::class);
        $this->app->singleton(PolicyRepositoryContract::class, PolicyRepository::class);
    }
}
