<?php

namespace App\Providers;

use App\Contracts;
use App\Utils;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerCompanyServices();
    }

    public function registerCompanyServices()
    {
        $this->app->bind(Contracts\Utils\DeferredResultContract::class, Utils\DeferredResult::class);
    }
}
