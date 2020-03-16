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
        'prefix' => 'api',
    ],
    function () use ($router) {
        $router->group(
            [
                'prefix' => 'v1',
            ],
            function () use ($router) {
                $router->group(
                    [
                        'prefix' => 'policies',
                    ],
                    function () use ($router) {
                        $router->post('/insurance/send', 'InsuranceController@store');
                        $router->post('/insurance/{code}/{method}', 'InsuranceController@index');
                        $router->post('/reports', 'ReportController@createReport');
                        // тут будут остальные контроллеры
                        $router->group(
                            [
                                'prefix' => 'drafts',
                            ],
                            function () use ($router) {
                                $router->get('/', 'DraftController@index');
                                $router->post('/', 'DraftController@store');
                                $router->get('/{policeId}', 'DraftController@show');
                                $router->patch('/{policeId}', 'DraftController@update');
                                $router->delete('/{policeId}', 'DraftController@delete');
                            }
                        );
                    }
                );
            }
        );


    }
);
