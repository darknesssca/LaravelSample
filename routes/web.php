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

use Illuminate\Support\Facades\Route;

/**@var Route $router */
$router->group(
    [
        'prefix' => 'v1',
        'middleware' => 'auth',
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
                // insurance
                $router->post('/registration/send', 'InsuranceController@store');
                $router->post('/registration/{code}/{method}', 'InsuranceController@index');

                //policies
                $router->group(['prefix' => 'policies'] , function () use ($router) {
                    $router->get('/', 'PoliciesController@list');
                    $router->get('/{id}', 'PoliciesController@getById');
                    $router->post('/', 'PoliciesController@create');
                });

                //autocod
                $router->get('autocod/check-taxi', 'AutocodController@checkTaxi'); //проверка на такси
                $router->get('autocod/{report_id}', 'AutocodController@readReport'); //если отчет уже готов
                $router->post('autocod', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации
            }
        );


        $router->group(
            [
                'prefix' => 'policies'
            ],
            function () use ($router) {
                $router->post('/reports', 'ReportController@create');
                $router->get('/reports', 'ReportController@index');
                $router->get('/reports/{id}', 'ReportController@show');
            }
        );
    }
);
