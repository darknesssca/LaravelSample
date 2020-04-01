<?php


namespace App\Services\CarInfo;


use App\Http\Controllers\RestController;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\Services\LogMicroservice;
use Benfin\Api\Traits\HttpRequest;
use GuzzleHttp\Client;

class CarInfoService
{
    use HttpRequest;

    /** @var LogMicroservice  $logger */
    protected $logger;

    protected function __construct()
    {
        $this->init();
        /** @var LogMicroservice logger */
        $this->logger = app(LogMicroserviceContract::class);
    }

    /**отправка асинхронного POST запроса
     * @param string $url
     * адрес
     * @param array $data
     * данные
     * @param bool $async
     * если истина, то запрос выполняется асинхронно и без результата
     * @return bool
     * если запрос прошел успешно, то true
     */
    protected function sendPostAsync(string $url, array $data, bool $async = false)
    {
        $client = new Client([
            'base_uri' => $url,
            'timeout' => 1.0,
        ]);
        $client->requestAsync("POST", $url, ["form_params" => $data, "headers" => ["Content-Type" => "application/json"]]);
        return true;
    }
}
