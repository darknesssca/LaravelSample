<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Http\Controllers\RestController;
use App\Http\Controllers\SoapController;
use App\Services\Company\GuidesSourceInterface;
use App\Services\Company\GuidesSourceTrait;
use App\Services\Company\Soglasie\SoglasieService;
use Illuminate\Support\Facades\File;

class IngosstrahGuidesService extends IngosstrahService implements GuidesSourceInterface
{
    use GuidesSourceTrait;
    private $baseUrl;
    private $category_codes =[
        8246=>"D",//АВТОБУСЫ
        8245=>"C",//ГРУЗОВЫЕ
        8244=>"B",//ЛЕГКОВЫЕ
        8247=>"A",//МОТОЦИКЛЫ
        2242017703=>"E",//ПРИЦЕП К МОТОЦИКЛУ
        762039900=>"E",//ПРИЦЕП К ТРАКТОРАМ
        3024616=>"E",//ПРИЦЕПЫ К ГРУЗОВЫМ
        3024716=>"E",//ПРИЦЕПЫ К ЛЕГКОВЫМ
        28995016=>"трактор",//ТРАКТОРЫ
        762039800=>"трамвай",//ТРАМВАИ
        762039700=>"троллейбус",//ТРОЛЛЕЙБУСЫ
    ];

    public function __construct()
    {
        parent::__construct();
    }


    public function updateGuides(): bool
    {
        $token = $this->getToken();
        $data = [
            'SessionToken' => $token,
            "Product" => '753518300', //todo из справочника, вероятно статика
        ];
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'GetDicti', $data, [], [], []);
        $arr_raw = $this->parseXML($response['response']->ResponseData->any);

        $cars_arr = $this->filter_dict($arr_raw);
        foreach ($cars_arr as $mark) {
            $val = $this->prepareMark($mark);
            if (count($val) == 0) {
                continue;
            }
            $cnt = $this->updateMark($val);
        }
        return true;
    }

    /**получение токена
     * @return string
     */
    private function getToken(): string
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run(null, null);
        return $loginData['sessionToken'];
    }

    /**парсер xml в массив
     * @param string $xml
     * @return array
     */
    private function parseXML(string $xml): array
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        return $vals;
    }

    /**Фильтрафия словаря, выбор только модеей и марок машин
     * @param array $arr_raw
     * @return array
     */
    private function filter_dict(array $arr_raw): array
    {
        $res = [];
        $cur_mark_isn = "";
        $cur_category="";
        foreach ($arr_raw as $item) {
            $level = $item['level'];
            $open = $item['type'] == 'open';
            $close = $item['type'] == 'close';
            $model = $item['type'] == 'complete' && $level == 6;
            $mark = $open && $level == 5;
            $category = $open && $level == 4;


            if ($item['tag'] != "MODEL") {
                continue;
            }
            if (!$this->checkName($item, $model)) {
                continue;
            }

            if($category) //если это категория
            {
                $cur_category = $this->category_codes[$item['attributes']["ISN"]];
            }

            if ($mark) //если это марка машины
            {
                $mark_arr = [
                    "NAME" => $item['attributes']["NAME"],
                    "ISN" => $item['attributes']['ISN'],
                    "MODELS" => [],
                ];
                $cur_mark_isn = $mark_arr['NAME'];
                $res[$cur_mark_isn] = $mark_arr;
            }

            if ($model && $cur_mark_isn != "" && $cur_category!="") { //если модель машины
                $model_arr = [
                    "NAME" => $item['attributes']["NAME"],
                    "ISN" => $item['attributes']['ISN'],
                    "CATEGORY" => $cur_category,
                ];
                $res[$cur_mark_isn]["MODELS"][$model_arr['NAME']] = $model_arr;

            }

            if ($close)//если закончилась марка
                unset($cur_mark_isn);
        }
        return $res;
    }

    /**проверка допустимости имени
     * @param array $item
     * @param bool $model
     * @return bool
     */
    private function checkName(array $item, bool $model): bool
    {
        if (!array_key_exists('attributes', $item) || !array_key_exists('NAME', $item["attributes"])) {
            return false;
        }
        $name = $item['attributes']['NAME'];
        if ($name == '.' || $name == "") {
            return false;
        }
        if (!$model && preg_match('/[\d-]+/', $name) == 1) {
            return false;
        }
        return true;
    }

    /**
     * @param $mark
     * @return array
     */
    private function prepareMark($mark): array
    {
        if (count($mark["MODELS"]) == 0) {
            return [];
        }
        $res = [
            "NAME" => $mark["NAME"],
            "REF_CODE" => $mark['ISN'],
            "MODELS" => [],
        ];

        foreach ($mark["MODELS"] as $model) {
            $model = [
                "NAME" => $model['NAME'],
                "CATEGORY_CODE" => $model['CATEGORY'],
                "REF_CODE" => $model['ISN'],
            ];
            $res["MODELS"][] = $model;
        }
        return $res;
    }
}
