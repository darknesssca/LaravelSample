<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffMasterServiceContract;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class TinkoffMasterService extends TinkoffService implements TinkoffMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);
        $calculateService = app(TinkoffCalculateServiceContract::class);
        $dataCalculate = $calculateService->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'setNumber' => $dataCalculate['setNumber'],
            'premium' => $dataCalculate['premium'],
        ];
        $this->intermediateDataRepository->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'premium' => $dataCalculate['premium'],
        ];
    }

    public function create($company, $attributes):array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $attributes['setNumber'] = $tokenData['setNumber'];
        $createService = app(TinkoffCreateServiceContract::class);
        $createData = $createService->run($company, $attributes);
        $billLinkService = app(TinkoffBillLinkServiceContract::class);
        $billLinkData = $billLinkService->run($company, $attributes);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->sendBillUrl($insurer['email'], $billLinkData['billUrl']);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['status'] = $createData['status'];
        $tokenData[$company->code]['billUrl'] = $billLinkData['billUrl'];
        $this->intermediateDataRepository->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $logger = app(LogMicroserviceContract::class);
        $logger->sendLog(
            'пользователь отправил запрос на создание заявки в компанию ' . $company->name,
            config('api_sk.logMicroserviceCode'),
            GlobalStorage::getUserId()
        );
        return [
            'status' => 'done',
            'billUrl' => $billLinkData['billUrl'],
        ];
    }
}
