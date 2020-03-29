<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieBillLinkServiceContract;

class SoglasieBillLinkService extends SoglasieService implements SoglasieBillLinkServiceContract
{

    public function __construct()
    {
        $this->apiRestUrl = config('api_sk.soglasie.billLinkUrl');
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
        $response = $this->getRequest($url, [], $headers);
        return $response;
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }


}
