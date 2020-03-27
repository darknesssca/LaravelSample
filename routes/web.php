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
        "middleware"=>"auth"
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

                //autocod
                $router->get('autocod/check-taxi', 'AutocodController@checkTaxi'); //проверка на такси
                $router->get('autocod/{report_id}', 'AutocodController@readReport'); //если отчет уже готов
                $router->post('autocod', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации

                //reports
                $router->post('/reports', 'ReportController@create');
                $router->get('/reports', 'ReportController@index');
                $router->get('/reports/{id}', 'ReportController@show');

                //guides
                $router->get('/guides/marks', 'GuidesController@marks');
                $router->get('/guides/models/{mark_id:\d+}', 'GuidesController@models');
                $router->get('/guides/models/', 'GuidesController@modelsAll');
                $router->get('/guides/categories/', 'GuidesController@categories');
                $router->get('/guides/countries/', 'GuidesController@countriesAll');
                $router->get('/guides/countries/{country_id:\d+}', 'GuidesController@countries');
                $router->get('/guides/genders', 'GuidesController@genders');
                $router->get('/guides/doc-types', 'GuidesController@docTypes');
                $router->get('/guides/usage-targets', 'GuidesController@usageTargets');
                $router->get('/guides/insurance-companies', 'GuidesController@insuranceCompanies');
                $router->get('/guides/source-acquisitions', 'GuidesController@sourceAcquisitions');
            }
        );
    }
);
