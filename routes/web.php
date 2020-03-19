<?php
/**
 * @var \Laravel\Lumen\Routing\Router $router
 */

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
                'prefix' => 'car-insurance',
            ],
            function () use ($router) {
                // drafts
                $router->get('/drafts', 'DraftController@index');
                $router->post('/drafts', 'DraftController@store');
                $router->get('/drafts/{policeId}', 'DraftController@show');
                $router->patch('/drafts/{policeId}', 'DraftController@update');
                $router->delete('/drafts/{policeId}', 'DraftController@delete');
                // policies
                $router->post('policies/send', 'InsuranceController@store');
                $router->post('/policies/{code}/{method}', 'InsuranceController@index');

                //autocod
                $router->get('autocod/check-taxi/', 'AutocodController@checkTaxi'); //проверка на такси
                $router->get('autocod/{report_id}/', 'AutocodController@readReport'); //если отчет уже готов
                $router->post('autocod', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации
            }
        );
    }
);
