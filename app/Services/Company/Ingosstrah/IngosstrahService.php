<?php


namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahServiceContract;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Services\Company\CompanyService;
use Illuminate\Support\Carbon;

class IngosstrahService extends CompanyService implements IngosstrahServiceContract
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;

    public function __construct()
    {
        $this->companyCode = "ingosstrah";
        $this->companyId = InsuranceCompany::where('code',$this->companyCode)->take(1)->get()[0]['id'];
        $this->apiWsdlUrl = config('api_sk.ingosstrah.wsdlUrl');
        $this->apiUser = config('api_sk.ingosstrah.user');
        $this->apiPassword = config('api_sk.ingosstrah.password');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new \Exception('ingosstrah api is not configured');
        }
    }

    public function calculate($company, $attributes, $additionalData = [])
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, $attributes, $additionalData);
        return $loginData;
        $attributes['sessionToken'] = $loginData['sessionToken'];
        $serviceCalculate = app(IngosstrahCalculateServiceContract::class);
        $data = $serviceCalculate->run($company, $attributes, $additionalData);
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'calculated' => true,
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        return [
            'premium' => $data['premium'],
        ];
    }

    protected function transformBoolean($boolean)
    {
        return $boolean ? 'Y' : 'N';
    }

}
