<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(
    [
        'prefix' => 'v1',
    ],
    function () use ($router) {
        $router->group(
            [
                'prefix' => 'api',
            ],
            function () use ($router) {
                $router->group(
                    [
                        'prefix' => 'policies',
                    ],
                    function () use ($router) {
                        $router->post('/insurance/send', 'InsuranceController@store');
                        $router->post('/insurance/{code}/{method}', 'InsuranceController@index');
                        // тут будут остальные контроллеры
                    }
                );
                // тут будут остальные контроллеры
            }
        );


    }
);
