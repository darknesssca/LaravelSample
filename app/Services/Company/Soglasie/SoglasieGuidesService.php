<?php


namespace App\Services\Company\Soglasie;


use App\Http\Controllers\RestController;
use App\Http\Controllers\SoapController;
use App\Models\CarCategory;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\InsuranceMark;
use App\Models\InsuranceModel;
use App\Services\Company\GuidesSourceInterface;

class SoglasieGuidesService extends SoglasieService implements GuidesSourceInterface
{
    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env("SOGLASIE_API_CARS");;
    }


    public function updateGuides(): void
    {
        $headers = $this->generateHeaders();
        $response = RestController::getRequest($this->baseUrl, [], $headers);

        foreach ($response as $mark) {
            $cnt = $this->updateMark($mark);
            echo "Добавлена марка: " . $mark['name'] . " ($cnt шт)\n";
        }
    }

    /**запрос моделей и добавление марки и моделей в таблицы
     * @param array $mark
     * @return int
     * @throws \Exception
     */
    private function updateMark(array $mark): int
    {
        //МАРКИ
        //добавление в общие таблицы
        $mark_com = CarMark::firstOrCreate([
            'name' => $mark['name'],
            'code' => strtolower($mark['name']),
        ]);
        //добавление в таблицы СК
        $mark_sk = InsuranceMark::updateOrCreate([
            'mark_id' => $mark_com->id,
            'insurance_company_id' => $this->companyId,
        ],
            ['reference_mark_code' => $mark['id'],]);


        //МОДЕЛИ
        $headers = $this->generateHeaders();
        $response = RestController::getRequest($this->baseUrl . "/" . $mark['id'], [], $headers);

        foreach ($response as $model) {
            //общие таблицы
            $cat_code = !empty($model['cat']) ? $model['cat'] : "D";
            $cat = CarCategory::firstOrCreate([
                'code' => $cat_code,
                'name' => $cat_code,
            ]);
            $model_com = CarModel::firstOrCreate([
                'name' => $model['name'],
                'code' => strtolower($model['name']),
                'mark_id' => $mark_com->id,
                'category_id' => $cat->id,
            ]);

            //таблицы СК
            $model_sk = InsuranceModel::updateOrCreate(
                [
                    "model_id" => $model_com->id,
                    'insurance_company_id' => $this->companyId,
                ],
                ['reference_model_code' => $model['id']]
            );
        }
        return count($response);
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
}
