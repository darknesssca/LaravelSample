<?php


namespace App\Services\Company\Renessans;


use App\Http\Controllers\RestController;
use App\Models\CarCategory;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\InsuranceMark;
use App\Models\InsuranceModel;
use App\Services\Company\GuidesSourceInterface;
use App\Services\Company\Soglasie\SoglasieService;

class RenessansGuidesService extends RenessansService implements GuidesSourceInterface
{
    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env("RENESSANS_API_CARS");;
    }


    public function updateGuides(): bool
    {
        try {
            $params = [];
            $this->setAuth($params);
            $response = RestController::getRequest($this->baseUrl, $params);
            if (!$response['result']) {
                return false;
            }
            foreach ($response['data'] as $mark) {
                $cnt = $this->updateMark($mark);
                echo "Добавлена марка: " . $mark['make'] . " ($cnt моделей)\n";
            }
            return true;
        } catch (\Exception $ex) {
            dd($ex);
            return false;
        }
    }

    /**запрос моделей и добавление марки и моделей в таблицы
     * @param $mark
     * @return int
     */
    private function updateMark($mark): int
    {
        //МАРКИ
        //добавление в общие таблицы
        $mark_com = CarMark::firstOrCreate([
            'name' => $mark['make'],
            'code' => strtolower($mark['make']),
        ]);
        //добавление в таблицы СК
        $mark_sk = InsuranceMark::updateOrCreate([
            'mark_id' => $mark_com->id,
            'insurance_company_id' => $this->companyId,
        ],
            ['reference_mark_code' => $mark['make'],]);

        //МОДЕЛИ
        foreach ($mark["models"] as $model) {
            //общие таблицы
            $cat_code = $model["category"];
            $cat = CarCategory::firstOrCreate([
                'code' => $cat_code,
                'name' => $cat_code,
            ]);
            $model_com = CarModel::firstOrCreate([
                'name' => $model['model'],
                'code' => strtolower($model['model']),
                'mark_id' => $mark_com->id,
                'category_id' => $cat->id,
            ]);

            //таблицы СК
            $model_sk = InsuranceModel::updateOrCreate(
                [
                    "model_id" => $model_com->id,
                    'insurance_company_id' => $this->companyId,
                ],
                ['reference_model_code' => $model['model']]
            );
        }
        return count($mark["models"]);
    }
}
