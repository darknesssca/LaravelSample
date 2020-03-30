<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Models\IntermediateData;
use App\Models\PolicyStatus;
use App\Services\Company\CompanyService;

abstract class RenessansService extends CompanyService
{
    const companyCode = 'renessans';

    protected $apiUrl;
    protected $secretKey;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository)
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
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

    // FIXME требуется рефакторинг

    public function checkPaid($company, $process)
    {
        $dataProcess = $process->toArray();
        $attributes = [
            'policyId' => (int)$dataProcess['number']
        ];
        $serviceStatus = app(RenessansGetStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $process);
        if ($dataStatus['result'] && $dataStatus['payStatus']) {
            $process->update([
                'paid' => true,
                'status_id' => PolicyStatus::where('code', 'paid')->first()->id, // todo справочник
                'number' => $dataStatus['policyNumber'],
            ]);
        }
    }










}
