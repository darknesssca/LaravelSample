<?php


namespace App\Services\Company\Renessans;


use App\Http\Controllers\RestController;
use App\Models\CarCategory;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\InsuranceMark;
use App\Models\InsuranceModel;
use App\Services\Company\GuidesSourceInterface;
use App\Services\Company\GuidesSourceTrait;
use App\Services\Company\Soglasie\SoglasieService;

class RenessansGuidesService extends RenessansService implements GuidesSourceInterface
{
    use GuidesSourceTrait;
    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env("RENESSANS_API_CARS");;
    }


    public function updateCarModelsGuides(): bool
    {
        try {
            $params = [];
            $this->setAuth($params);
            $response = $this->getRequest($this->baseUrl, $params);
            if (!$response['result']) {
                return false;
            }
            foreach ($response['data'] as $mark) {
                $val = $this->prepareMark($mark);
                $cnt = $this->updateMark($val);
            }
            return true;
        } catch (\Exception $ex) {
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
