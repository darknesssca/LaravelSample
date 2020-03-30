<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;

class SoglasieCheckCreateService extends SoglasieService implements SoglasieCheckCreateServiceContract
{

    public function __construct(
        IntermediateDataRepositoryContract $intermediateDataRepository,
        RequestProcessRepositoryContract $requestProcessRepository,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.checkCreateUrl');
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
        return $this->getRequest($url, [], $headers);
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
        ];
    }


}
