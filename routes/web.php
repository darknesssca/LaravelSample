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
                //guides
                $router->group(['prefix' => 'guides'], function () use ($router) {
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
                    $router->get('/insurance-companies/{id}', 'GuidesController@insuranceCompany');
                    $router->get('/source-acquisitions', 'GuidesController@sourceAcquisitions');
                }
                );
            }
        );
    }
);

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
                $router->group(['prefix' => 'drafts'], function () use ($router) {
                        $router->get('/', 'DraftController@index');
                        $router->get('filter', 'DraftController@filter');
                        $router->post('/', 'DraftController@store');
                        $router->get('{draftId}', 'DraftController@show');
                        $router->patch('{draftId}', 'DraftController@update');
                        $router->delete('{draftId}', 'DraftController@delete');
                    }
                );

                // запросы в страховые компании
                $router->group(['middleware' => 'restriction.policy'], function () use ($router) {
                    $router->group(['prefix' => 'registration'], function () use ($router) {
                        $router->post('send', 'InsuranceController@store'); //Запрос с формой, в ответе приходит токен формы
                        $router->post('{code}/payment', 'InsuranceController@payment');
                        $router->post('{code}/{method}', 'InsuranceController@index'); //Запросы с токеном формы, для получения предложений
                    });
                });


                //policies
                $router->group(['prefix' => 'policies'], function () use ($router) {
                    $router->get('/', 'PoliciesController@list');
                    $router->get('statistic', 'PoliciesController@statistic');
                    $router->get('/{id:\d+}', 'PoliciesController@getById');
                    $router->get('/with-rewards', 'PoliciesController@listAbleToPayment');
                    $router->get('/users-with-policies', 'PoliciesController@usersWithPolicies');
                });


                //autocod
                $router->group(['prefix' => 'autocod'], function () use ($router) {
                        $router->get('check-taxi', 'AutocodController@checkTaxi'); //проверка на такси
                        $router->get('{report_id}', 'AutocodController@readReport'); //если отчет уже готов
                        $router->post('/', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации
                    }
                );

                //reports
                $router->group(['prefix' => 'reports'], function () use ($router) {
                        $router->post('/', [
                            'uses' => 'ReportController@create',
                            'middleware' => 'restriction.money'
                        ]);
                        $router->get('/', 'ReportController@index');
                        $router->get('/balance', [
                            'uses' => 'ReportController@getBalance',
                            'middleware' => 'admin'
                        ]);
                        $router->get('status', 'ReportController@status');
                        $router->get('/processing-status', [
                            'uses' => 'ReportController@processingStatus',
                            'middleware' => 'admin'
                        ]);
                        $router->patch('{id}/payout/rerun', [
                            'uses' => 'ReportController@rerunPayout',
                            'middleware' => 'restriction.money'
                        ]);
                        $router->get('{id}', 'ReportController@show');
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
                        $router->patch('/update-companies', 'OptionController@updateCompanies');
                        $router->patch('/{id}', 'OptionController@update');
                        $router->delete('/{id}', 'OptionController@delete');
                    }
                );
            }
        );
    }
);
