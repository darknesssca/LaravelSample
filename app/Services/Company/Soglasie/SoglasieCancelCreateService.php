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

    public function run($company, $data, $additionalFields = []): array
    {
        $url = $this->getUrl([
            'policyId' => $data->data['policyId'],
        ]);
        $headers = $this->getHeaders();
        return $this->getRequest($url, [], $headers);
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
        ];
    }


}
