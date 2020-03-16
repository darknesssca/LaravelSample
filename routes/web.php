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
                        $router->get('/drafts', 'DraftController@index');
                        $router->post('/drafts', 'DraftController@store');
                        $router->get('/drafts/{policeId}', 'DraftController@show');
                        $router->patch('/drafts/{policeId}', 'DraftController@update');
                        $router->delete('/drafts/{policeId}', 'DraftController@delete');
                        $router->post('/insurance/send', 'InsuranceController@store');
                        $router->post('/insurance/{code}/{method}', 'InsuranceController@index');
                    }
                );
            }
        );


    }
);
