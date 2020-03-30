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
                $router->post('/registration/send', 'InsuranceController@store');
                $router->post('/registration/{code}/{method}', 'InsuranceController@index');

                //autocod
                $router->get('autocod/check-taxi', 'AutocodController@checkTaxi'); //проверка на такси
                $router->get('autocod/{report_id}', 'AutocodController@readReport'); //если отчет уже готов
                $router->post('autocod', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации

                //reports
                $router->post('/reports', 'ReportController@create');
                $router->get('/reports', 'ReportController@index');
                $router->get('/reports/{id}', 'ReportController@show');

                //options
                $router->post('/options', 'OptionController@create');
                $router->get('/options', 'OptionController@index');
                $router->get('/options/{id}', 'OptionController@show');
                $router->patch('/options/{id}', 'OptionController@update');
                $router->delete('/options/{id}', 'OptionController@delete');
            }
        );
    }
);
