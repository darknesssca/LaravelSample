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
                // запросы в страховые компании
                $router->post('/registration/send', 'InsuranceController@store');
                $router->post('/registration/{code}/payment', 'InsuranceController@payment');
                $router->post('/registration/{code}/{method}', 'InsuranceController@index');

                //policies
                $router->group(['prefix' => 'policies'] , function () use ($router) {
                    $router->get('/', 'PoliciesController@list');
                    $router->get('/{id}', 'PoliciesController@getById');
                    $router->post('/', 'PoliciesController@create');
                    $router->get('statistic', 'PoliciesController@statistic');
                });


                //autocod
                $router->get('autocod/check-taxi', 'AutocodController@checkTaxi'); //проверка на такси
                $router->get('autocod/{report_id}', 'AutocodController@readReport'); //если отчет уже готов
                $router->post('autocod', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации

                //reports
                $router->post('/reports', 'ReportController@create');
                $router->get('/reports', 'ReportController@index');
                $router->get('/reports/{id}', 'ReportController@show');

                //guides
                $router->group([
                    'prefix' => 'guides',
                ],
                    function () use ($router) {

                        $router->get('/marks', 'GuidesController@marks');
                        $router->get('/models/{mark_id:\d+}', 'GuidesController@models');
                        $router->get('/models', 'GuidesController@modelsAll');
                        $router->get('/categories', 'GuidesController@categories');
                        $router->get('/countries', 'GuidesController@countriesAll');
                        $router->get('/countries/{country_id:\d+}', 'GuidesController@countries');
                        $router->get('/genders', 'GuidesController@genders');
                        $router->get('/doc-types', 'GuidesController@docTypes');
                        $router->get('/usage-targets', 'GuidesController@usageTargets');
                        $router->get('/insurance-companies', 'GuidesController@insuranceCompanies');
                        $router->get('/source-acquisitions', 'GuidesController@sourceAcquisitions');
                    }
                );

                //options
                $router->group([
                    'prefix' => 'options',
                ],
                    function () use ($router) {
                        $router->post('/', 'OptionController@create');
                        $router->get('/', 'OptionController@index');
                        $router->get('/{id}', 'OptionController@show');
                        $router->patch('/{id}', 'OptionController@update');
                        $router->delete('/{id}', 'OptionController@delete');
                    }
                );
            }
        );
    }
);
