<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Exceptions\ApiRequestsException;

class RenessansCheckCalculateService extends RenessansService implements RenessansCheckCalculateServiceContract
{
    protected $apiPath = '/calculate/{{calcId}}/';

    public function run($company, $attributes): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes['data']);
        $response = $this->getRequest($url, $data);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!$response['result'] || !isset($response['data']['response']['Premium'])) {
            throw new ApiRequestsException(
                'API страховой компании не вернуло ответ',
                isset($response['message']) ? $response['message'] : '',
            );
        }
        return [
            'premium' => $response['data']['response']['Premium'],
        ];
    }

}
