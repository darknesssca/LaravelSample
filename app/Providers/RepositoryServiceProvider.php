<?php


namespace App\Providers;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Repositories\InsuranceCompanyRepository;
use App\Repositories\IntermediateDataRepository;
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
        $this->app->bind(
            InsuranceCompanyRepositoryContract::class,
            InsuranceCompanyRepository::class
        );
        $this->app->bind(
            IntermediateDataRepositoryContract::class,
            IntermediateDataRepository::class
        );
        $this->app->bind(
            RequestProcessRepositoryContract::class,
            RequestProcessRepository::class
        );
    }
}
