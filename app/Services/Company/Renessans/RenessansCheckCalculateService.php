<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;

class RenessansCheckCalculateService extends RenessansService implements RenessansCheckCalculateServiceContract
{
    protected $apiPath = '/calculate/{{calcId}}/';

    public function run($company, $attributes): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes);
        $response = $this->getRequest($url, $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result'] || !isset($response['data']['response']['Premium'])) {
            return [
                'result' => false,
                'message' => isset($response['message']) ? $response['message'] : '',
            ];
        }
        return [
            'result' => true,
            'premium' => $response['data']['response']['Premium'],
        ];
    }

}
