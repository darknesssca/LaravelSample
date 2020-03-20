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
use App\Services\Company\GuidesSourceTrait;

class SoglasieGuidesService extends SoglasieService implements GuidesSourceInterface
{
    use GuidesSourceTrait;

    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env("SOGLASIE_API_CARS");;
    }


    public function updateGuides(): bool
    {
        try {
            $headers = $this->generateHeaders();
            $response = RestController::getRequest($this->baseUrl, [], $headers);

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
        $response = RestController::getRequest($this->baseUrl . "/" . $mark['id'], [], $headers);

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
