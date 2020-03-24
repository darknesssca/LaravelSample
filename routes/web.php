<?php
/**
 * @var \Laravel\Lumen\Routing\Router $router
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
                $router->post('/registration/send', 'InsuranceController@store');
                $router->post('/registration/{code}/{method}', 'InsuranceController@index');

                //autocod
                $router->get('autocod/check-taxi', 'AutocodController@checkTaxi'); //проверка на такси
                $router->get('autocod/{report_id}', 'AutocodController@readReport'); //если отчет уже готов
                $router->post('autocod', 'AutocodController@requestReport'); //заказать отчет и сразу дождаться генерации
            }
        );
    }
);
