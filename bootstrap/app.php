<?php


use App\Providers\RepositoryServiceProvider;
use App\Providers\ValidationProvider;
use Benfin\Api\BenfinMicroserviceProvider;
use Benfin\Requests\BenfinMacroProvider;
use Benfin\Requests\BenfinRequestProvider;

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('api_sk');
$app->configure('queue');
$app->configure('api');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->routeMiddleware([
    'auth' => Benfin\Auth\Http\Middleware\Authenticate::class,
    'restriction.policy' => Benfin\Restrictions\Http\Middleware\RestrictionPolicy::class,
    'restriction.money' => Benfin\Restrictions\Http\Middleware\RestrictionMoney::class,
    'admin' => Benfin\Auth\Http\Middleware\Admin::class,
    ]);
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(ValidationProvider::class);
$app->register(App\Providers\CompanyServiceProvider::class);
$app->register(App\Providers\RepositoryServiceProvider::class);
$app->register(BenfinMicroserviceProvider::class);
$app->register(BenfinMacroProvider::class);
$app->register(BenfinRequestProvider::class);
$app->register(App\Providers\MinIOStorageServiceProvider::class);
$app->register(RepositoryServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Benfin\Log\LogServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'prefix' => 'api',
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

app('translator')->setLocale('ru');

return $app;
