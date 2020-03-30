<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieBillLinkServiceContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;

class SoglasieBillLinkService extends SoglasieService implements SoglasieBillLinkServiceContract
{

    public function __construct(
        IntermediateDataRepositoryContract $intermediateDataRepository,
        RequestProcessRepositoryContract $requestProcessRepository,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.billLinkUrl');
        if (!($this->apiRestUrl)) {
            throw new \Exception('soglasie api is not configured');
        }
        parent::__construct($intermediateDataRepository, $requestProcessRepository, $policyRepository);
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
