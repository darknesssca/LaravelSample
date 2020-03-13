<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Company\Soglasie\SoglasieServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Models\IntermediateData;
use App\Services\Company\CompanyService;
use Illuminate\Support\Carbon;

class SoglasieService extends CompanyService implements SoglasieServiceContract
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiSubUser;
    protected $apiSubPassword;
    protected $apiIsTest;

    // wsdl url прописывается в дочерних классах

    public function __construct()
    {
        $this->apiUser = config('api_sk.soglasie.user');
        $this->apiPassword = config('api_sk.soglasie.password');
        $this->apiSubUser = config('api_sk.soglasie.subUser');
        $this->apiSubPassword = config('api_sk.soglasie.subPassword');
        $this->apiIsTest = config('api_sk.soglasie.isTest');
        if (!($this->apiUser && $this->apiPassword && $this->apiSubUser && $this->apiSubPassword)) {
            throw new \Exception('soglasie api is not configured');
        }
    }

    public function calculate($company, $attributes, $additionalData = [])
    {
        if (!$attributes['policy']['isMultidrive']) {
            $serviceKbm = app(SoglasieKbmServiceContract::class);
            $dataKbm = $serviceKbm->run($company, $attributes, $additionalData);
        } else {
            $dataKbm = [
                'kbmId' => 1,
            ];
        }
        $serviceScoring = app(SoglasieScoringServiceContract::class);
        $dataScoring = $serviceScoring->run($company, $attributes, $additionalData);
        $attributes['serviceData'] = [
            'kbmId' => $dataKbm['kbmId'],
            'scoringId' => $dataScoring['scoringId'],
        ];
        $serviceKbm = app(SoglasieCalculateServiceContract::class);
        $dataCalculate = $serviceKbm->run($company, $attributes, $additionalData);
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'scoringId' => $dataScoring['scoringId'],
            'kbmId' => $dataKbm['kbmId'],
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        return [
            'premium' => $dataCalculate['premium'],
        ];
    }

    protected function getHeaders()
    {
        return [];
    }

    protected function getAuth()
    {
        return [
            'login' => $this->apiUser,
            'password' => $this->apiPassword,
        ];
    }

    protected function transformBoolean($boolean)
    {
        return (bool)$boolean;
    }

    protected function transformBooleanToInteger($boolean)
    {
        return (int)$boolean;
    }

}
