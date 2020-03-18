<?php


namespace App\Services\CarInfo\Autocod;

use App\Services\CarInfo\CarInfoService;
use Exception;

class AutocodService extends CarInfoService
{
    protected $baseurl;
    private $user;
    private $password;
    private $token_lifetime;
    protected $uid_autocomplete;
    protected $uid_taxi;


    protected function __construct()
    {
        $this->baseurl = env("AC_API_BASE_URI");
        $this->user = env('AC_API_AUTH_USERNAME');
        $this->password = env('AC_API_AUTH_PASSWORD');
        $this->token_lifetime = env('AC_API_TOKEN_LIFETIME');
        $this->uid_autocomplete = env('AC_API_REPORT_TYPE_UID_AUTOCOMPLETE');
        $this->uid_taxi = env('AC_API_REPORT_TYPE_UID_TAXI');
    }

    /**генерирует токен авторизации
     * @return string
     */
    protected function createToken(): string
    {
            $stamp = time();
            $passHash = base64_encode(md5($this->password, true));
            $saltedHash = base64_encode(md5($stamp . ':' . $this->token_lifetime . ':' . $passHash, true));
            $token = base64_encode(implode(':', [$this->user, $stamp, $this->token_lifetime, $saltedHash]));
            return $token;
    }
}
