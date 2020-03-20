<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieBillLinkServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Company\Soglasie\SoglasieServiceContract;
use App\Http\Controllers\RestController;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use App\Services\Company\CompanyService;

class SoglasieService extends CompanyService implements SoglasieServiceContract
{
    protected $apiWsdlUrl;
    protected $apiRestUrl;
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

    public function create($company, $attributes, $additionalData)
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        $attributes['serviceData'] = [
            'kbmId' => $additionalData['tokenData']['kbmId'],
            'scoringId' => $additionalData['tokenData']['scoringId'],
        ];
        $serviceCreate = app(SoglasieCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes, $additionalData);
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'policyId' => $dataCreate['policyId'],
            'packageId' => $dataCreate['packageId'],
            'status' => 'processing',
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        RequestProcess::create([
            'token' => $attributes['token'],
            'state' => 99,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'packageId' => $dataCreate['packageId'],
                'status' => 'processing',
                'company' => $company->code,
            ]),
        ]);
        return [
            'status' => 'processing',
        ];
    }

    public function processing($company, $data, $additionalData)
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        if (!(isset($additionalData['tokenData']['status']) && $additionalData['tokenData']['status'])) {
            throw new \Exception('no status in token data');
        }
        switch ($additionalData['tokenData']['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'done':
                return [
                    'status' => 'done',
                    'billUrl' => $additionalData['tokenData']['billUrl'],
                ];
            default:
                throw new \Exception('not valid status');
        }
    }

    public function checkCreate($company, $data)
    {
        $checkService = app(SoglasieCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $data);
        switch ($checkData['status']) {
            case 'ERROR':
                RequestProcess::where('token', $data->token)->delete();
                $this->dropCreate($company, $data->token, $checkData['lastError']);
                break;
            case 'COMPLETE':
                switch ($checkData['policy']['status']) {
                    case 'RSA_SIGN_FAIL':
                    case 'RSA_CHECK_FAIL':
                    case 'SK_CHECK_FAIL':
                        $this->cancelCreate($company, $data);
                        RequestProcess::where('token', $data->token)->delete();
                        $this->dropCreate($company, $data->token, $checkData['policy']['statusName']);
                        break;
                    case 'SK_CHECK_OK':
                        RequestProcess::where('token', $data->token)->delete();
                        $billLinkService = app(SoglasieBillLinkServiceContract::class);
                        $billLinkData = $billLinkService->run($company, $data);
                        //$tokenData = IntermediateData::getData($data->token); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
                        $tokenFullData = IntermediateData::where('token', $data->token)->first();
                        $tokenData = json_decode($tokenFullData['data'], true);
                        $form = json_decode($tokenFullData['form']);
                        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
                        RestController::sendBillUrl($insurer['email'], $billLinkData['PayURL']);
                        $tokenData[$company->code] = [
                            'status' => 'done',
                            'billUrl' => $billLinkData['PayLink'],
                        ];
                        IntermediateData::where('token', $data->token)->update([
                            'data' => $tokenData,
                        ]);
                        break;
                    default:
                        $result = RequestProcess::updateCheckCount($data->token);
                        if ($result === false) {
                            $this->cancelCreate($company, $data);
                            $this->dropCreate($company, $data->token, 'no result by max check count');
                        }
                        break;
                }
                break;
            default: // все остальные статусы рассматриваем как WORKING
                $result = RequestProcess::updateCheckCount($data->token);
                if ($result === false) {
                    $this->cancelCreate($company, $data);
                    $this->dropCreate($company, $data->token, 'no result by max check count');
                }
                break;
        }
    }

    public function cancelCreate($company, $data)
    {
        $cancelService = app(SoglasieCancelCreateServiceContract::class);
        $cancelData = $cancelService->run($company, $data);
        return $cancelData;
    }

    protected function dropCreate($company, $token, $error)
    {
        $tokenData = IntermediateData::getData($token); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'status' => 'error',
            'error' => $error,
        ];
        IntermediateData::where('token', $token)->update([
            'data' => $tokenData,
        ]);
    }

    protected function getUrl($data = [])
    {
        $url = $this->apiRestUrl;
        if ($data) {
            foreach ($data as $key => $value) {
                $url = str_replace('{{'.$key.'}}', $value, $url);
            }
        }
        return $url;
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
