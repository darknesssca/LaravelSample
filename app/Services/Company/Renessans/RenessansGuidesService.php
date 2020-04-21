<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansGuidesSourceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Models\InsuranceCompany;
use App\Services\Company\GuidesSourceTrait;

class RenessansGuidesService extends RenessansService implements RenessansGuidesSourceContract
{
    use GuidesSourceTrait;
    private $baseUrl;

    public function __construct(IntermediateDataServiceContract $intermediateDataService,
                                RequestProcessServiceContract $requestProcessService,
                                PolicyServiceContract $policyService)
    {
        parent::__construct($intermediateDataService,$requestProcessService,$policyService);
        $this->baseUrl = env("RENESSANS_API_CARS");;
        $this->companyId = InsuranceCompany::where('code',self::companyCode)->first()['id'];
    }


    public function updateCarModelsGuides(): bool
    {
        try {
            $params = [];
            $this->setAuth($params);
            $response = $this->getRequest($this->baseUrl, $params,[],false);
            if (!$response['result']) {
                return false;
            }
            foreach ($response['data'] as $mark) {
                $val = $this->prepareMark($mark);
                $cnt = $this->updateMark($val);
            }
            return true;
        } catch (\Exception $ex) {
            dump($ex);
            return false;
        }
    }

    /**запрос моделей и добавление марки и моделей в таблицы
     * @param $mark
     * @return array
     */
    private function prepareMark($mark): array
    {
        $res = [
            "NAME" => $mark['make'],
            "REF_CODE" => $mark['make'],
            "MODELS" => [],
        ];
        //МОДЕЛИ
        foreach ($mark["models"] as $model) {
            $model = [
                "NAME" => $model['model'],
                "CATEGORY_CODE" => $model["category"],
                "REF_CODE" => $model['model'],
            ];
            $res["MODELS"][] = $model;
        }
        return $res;
    }
}
