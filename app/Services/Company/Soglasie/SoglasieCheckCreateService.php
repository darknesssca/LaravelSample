<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use Illuminate\Support\Carbon;

class SoglasieCheckCreateService extends SoglasieService implements SoglasieCheckCreateServiceContract
{

    public function __construct()
    {
        $this->apiRestUrl = config('api_sk.soglasie.checkCreateUrl');
        if (!($this->apiRestUrl)) {
            throw new \Exception('soglasie api is not configured');
        }
        parent::__construct();
    }

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendCheckCreate($company, $attributes);
    }

    private function sendCheckCreate($company, $data): array
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
