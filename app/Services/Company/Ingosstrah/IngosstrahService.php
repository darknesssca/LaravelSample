<?php

namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Models\PolicyStatus;
use App\Services\Company\CompanyService;

abstract class IngosstrahService extends CompanyService
{
    const companyCode = 'ingosstrah';

    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiWsdlUrl = config('api_sk.ingosstrah.wsdlUrl');
        $this->apiUser = config('api_sk.ingosstrah.user');
        $this->apiPassword = config('api_sk.ingosstrah.password');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    // FIXME рефакторинг



    public function checkPaid($company, $process)
    {
        $dataProcess = $process->toArray();
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, []);
        $attributes = [
            'BillISN' => $dataProcess['bill']['bill_id'],
            'SessionToken' => $loginData['sessionToken'],
        ];
        $serviceStatus = app(IngosstrahBillStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $process);
        if (isset($dataStatus['paid']) && $dataStatus['paid']) {
            $process->update([
                'paid' => true,
                'status_id' => PolicyStatus::where('code', 'paid')->first()->id, // todo справочник
            ]);
            $process->bill()->delete();
        }
    }







}
