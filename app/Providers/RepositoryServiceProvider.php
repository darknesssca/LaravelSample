<?php

namespace App\Providers;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Repositories\DraftRepository;
use App\Repositories\PolicyRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(PolicyRepositoryContract::class, PolicyRepository::class);
        $this->app->bind(DraftRepositoryContract::class, DraftRepository::class);
    }
}
