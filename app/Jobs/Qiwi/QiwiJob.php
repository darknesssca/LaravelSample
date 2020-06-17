<?php


namespace App\Jobs\Qiwi;


use App\Jobs\Job;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;

class QiwiJob extends Job
{
    /**
     * @throws Exception
     */
    protected function login()
    {
        /** @var AuthMicroserviceContract  $auth_mks */
        $auth_mks = app(AuthMicroserviceContract::class);
        $token = $auth_mks->login([
            'email' => env('AUTH_LOGIN'),
            'password' => env('AUTH_PASSWORD'),
            'g-recaptcha-response' => env('AUTH_TOKEN'),
        ]);

        if (!$token || (isset($token['error']) && $token['error'])) {
            throw new Exception('E-mail и пароль указаны не верно');
        }

        GlobalStorage::setUserToken($token['content']['access_token']);
    }
}
