<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansServiceContract;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use App\Services\Company\CompanyService;

class RenessansService extends CompanyService implements RenessansServiceContract
{
    private $apiUrl;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new \Exception('renessans api is not configured');
        }
    }

    public function calculate($company, $attributes, $additionalData = [])
    {
        /*
         * $state текущий этап
         * 0 - вычисления не выполнялись
         * 1 - вызван метод calculate
         * 2 - получен результат рассчета
         * 3 - отправлена заявка checkSegment
         * 4 - получен ответ checkSegment
         * 5 - отправлена сегментировання заявка в calculate
         * 6 - получен ответ calculate
         * */

        // calculate
        $state = 0;
        if ($additionalData['tokenData']) {
            $state = $additionalData['tokenData']['state'];
        }
        //$test = false; // fixme test
        $dataCalculate = [];
        if ($state == 0) {
            $serviceCalculate = app(RenessansCalculateServiceContract::class);
            $dataCalculate = $serviceCalculate->run($company, $attributes, $additionalData);
            $state = 1;
            //$test = true;// fixme test
        } else {
            $dataCalculate = $additionalData['tokenData']['calcIds'];
        }
        // get process
        $process = RequestProcess::where('token', $attributes['token'])->where('state', '<>', 99)->first();
        // check calculate
        if ($state == 1) {
            if ($process && $process['state'] == 1) {
                $dataCalculate = $process['data'];
            }
            $isNotChecked = false;
            $dataCheckCalculate = [];
            $serviceCheckCalculate = app(RenessansCheckCalculateServiceContract::class);
            foreach ($dataCalculate as $dataCalculateItem) {
                if (isset($dataCalculateItem['premium']) && ($dataCalculateItem['premium'] !== false)) {
                    $dataCheckCalculate[] = $dataCalculateItem;
                    continue;
                }
//                if ($test) { // fixme test
//                    $responseCheckCalculate = false;// fixme test
//                } else {// fixme test
//                    $responseCheckCalculate = $serviceCheckCalculate->run($company, $dataCalculateItem, $additionalData);// fixme test
//                }// fixme test
                $responseCheckCalculate = $serviceCheckCalculate->run($company, $dataCalculateItem, $additionalData);// fixme test
                if ($responseCheckCalculate === false) {
                    $isNotChecked = true;
                    $dataCheckCalculate[] = [
                        'premium' => false,
                        'state' => 1,
                        'calcId' => $dataCalculateItem['calcId'],
                    ];
                } else {
                    $dataCheckCalculate[] = $responseCheckCalculate;
                }
            }
            if ($isNotChecked) {
                if ($process) {
                    if ($process->checkCount == 5) {
                        RequestProcess::where('token', $attributes['token'])->delete();
                    } else {
                        RequestProcess::where('token', $attributes['token'])->update([
                            'checkCount' => ++$process->checkCount,
                            'data' => \GuzzleHttp\json_encode($dataCheckCalculate),
                        ]);
                    }
                } else {
                    RequestProcess::create([
                        'token' => $attributes['token'],
                        'state' => $state,
                        'data' => \GuzzleHttp\json_encode($dataCheckCalculate),
                    ]);
                }
                return $dataCheckCalculate;
            } else {
                $state = 2;
            }
        }
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'calcIds' => $dataCalculate,
            'state' => $state,
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        //todo тут идет проверка сегментации
        return isset($dataCheckCalculate) ? $dataCheckCalculate : [false]; // todo temp crutch
    }


    protected function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    protected function getUrl($data = [])
    {
        $url = (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath;
        if ($data) {
            foreach ($data as $key => $value) {
                $url = str_replace('{{'.$key.'}}', $value, $url);
            }
        }
        return $url;
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
