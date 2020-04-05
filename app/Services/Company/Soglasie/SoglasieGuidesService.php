<?php


namespace App\Services\Company\Soglasie;


use App\Contracts\Company\Soglasie\SoglasieGuidesSourceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Models\InsuranceCompany;
use App\Services\Company\GuidesSourceTrait;

class SoglasieGuidesService extends SoglasieService implements SoglasieGuidesSourceContract
{
    use GuidesSourceTrait;

    private $baseUrl;

    public function __construct(IntermediateDataServiceContract $intermediateDataService,
                                RequestProcessServiceContract $requestProcessService,
                                PolicyServiceContract $policyService
    )
    {
        parent::__construct($intermediateDataService,$requestProcessService,$policyService);
        $this->baseUrl = env("SOGLASIE_API_CARS");
        $this->companyId = InsuranceCompany::where('code',self::companyCode)->first()['id'];
    }


    public function updateCarModelsGuides(): bool
    {
        try {
            $headers = $this->generateHeaders();
            $response = $this->getRequest($this->baseUrl, [], $headers,false);

            foreach ($response as $mark) {
                $val = $this->prepareMark($mark);
                $cnt = $this->updateMark($val);
            }
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }


    /**генерация тегов авторизации
     * @return array
     */
    private function generateHeaders(): array
    {
        $auth = $this->getAuth();
        $auth_key = base64_encode($auth['login'] . ":" . $auth["password"]);
        return [
            'Authorization' => "Basic $auth_key",
        ];
    }

    /**подготовка марки машины и моделей
     * @param $mark
     * @return array
     * @throws \Exception
     */
    private function prepareMark($mark): array
    {
        $res = [
            "NAME" => $mark["name"],
            "REF_CODE" => $mark['id'],
            "MODELS" => [],
        ];
        //МОДЕЛИ
        $headers = $this->generateHeaders();
        $response = $this->getRequest($this->baseUrl . "/" . $mark['id'], [], $headers,false);

        foreach ($response as $model) {
            $model = [
                "NAME" => $model['name'],
                "CATEGORY_CODE" => !empty($model['cat']) ? $model['cat'] : "D",
                "REF_CODE" => $model['id'],
            ];
            $res["MODELS"][] = $model;
        }
        return $res;
    }
}
