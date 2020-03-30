<?php

namespace App\Providers;


use Benfin\Requests\AbstractRequest;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
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

    }

    public function boot()
    {
        $this->bootRequestValidation();
    }

    public function bootRequestValidation()
    {
        $this->app->afterResolving(ValidatesWhenResolved::class, function ($resolved) {
            $resolved->validateResolved();
        });
        $this->app->resolving(AbstractRequest::class, function ($request, $app) {
            $request = AbstractRequest::createFrom($app['request'], $request);
        });
    }
}
