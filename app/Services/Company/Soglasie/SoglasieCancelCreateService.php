<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Http\Controllers\RestController;

class SoglasieCancelCreateService extends SoglasieService implements SoglasieCancelCreateServiceContract
{

    public function __construct()
    {
        $this->apiRestUrl = config('api_sk.soglasie.cancelCreateUrl');
        if (!($this->apiRestUrl)) {
            throw new \Exception('soglasie api is not configured');
        }
        parent::__construct();
    }

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendCancelCreate($company, $attributes);
    }

    private function sendCancelCreate($company, $data): array
    {
        $url = $this->getUrl([
            'policyId' => $data->data['policyId'],
        ]);
        $headers = $this->getHeaders();
        $response = RestController::getRequest($url, [], $headers);
        return $response;
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
        ];
    }


}
