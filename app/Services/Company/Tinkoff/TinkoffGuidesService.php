<?php


namespace App\Services\Company\Tinkoff;


use App\Contracts\Company\Tinkoff\TinkoffGuidesSourceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Models\InsuranceCompany;
use App\Traits\GuidesSourceTrait;
use Illuminate\Support\Str;
use Laravel\Lumen\Application;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class TinkoffGuidesService extends TinkoffService implements TinkoffGuidesSourceContract
{
    /**массив марок машин, которые состоят более чем из одного слова
     * @var array
     */
    private $two_word_marks = [
        "Great Wall",
        "Alfa Romeo",
        "Aston Martin",
        "Chang Feng",
        "Iran Khodro",
        "Land Rover",
        "Mini (BMW)",
        "Rolls Royce",
    ];

    private $carReferenceApiUrl;

    use GuidesSourceTrait;

    public function __construct(IntermediateDataServiceContract $intermediateDataService,
                                RequestProcessServiceContract $requestProcessService,
                                PolicyServiceContract $policyService
    )
    {
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
        $this->companyId = InsuranceCompany::where('code', self::companyCode)->first()['id'];
        $this->carReferenceApiUrl = config('api_sk.tinkoff.modelsUrl');
    }


    public function updateCarModelsGuides(): bool
    {
        /*
         * старый способ обновления справочников из файла
         */
        //$filename = Application::getInstance()->basePath() . "/storage/import/tinkoff_cars_import.xlsx"; //todo: сделать импорт из minio
        try {
            /*
             * старый способ обновления справочников из файла
             */
            //$arr = $this->readDocument($filename);
            $arr = $this->getCarsArray();

            foreach ($arr as $mark) {
                $val = $this->prepareMark($mark);
                if (count($val) == 0) {
                    continue;
                }
                $cnt = $this->updateMark($val);
            }
        } catch (\Exception $e) {
            dump($e);
            return false;
        }
        return true;
    }

    /**Возвращает таблицу в виде массива
     * @param string $filename
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    private function readDocument(string $filename): array
    {
        $reader = IOFactory::createReaderForFile($filename);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(["2.11"]);
        $spreadsheet = $reader->load($filename);
        $cells = $spreadsheet->getActiveSheet()->getCellCollection();
        $table = [];
        for ($row = 2; $row <= $cells->getHighestRow(); $row++) {
            $table[] = [
                "code" => $cells->get("A$row")->getValue(),
                "name" => $cells->get("B$row")->getValue(),
            ];
        }
        $marks = [];
        foreach ($table as $item) {
            $mark_name = $this->parseMarkName($item['name']);
            $model_name = $this->parseModelName($item['name'], $mark_name);
            $marks[$mark_name]["NAME"] = $mark_name;
            $marks[$mark_name]["MODELS"][] = ['NAME' => $model_name, "REF_CODE" => $item['code']];
        }
        return $marks;
    }

    /**приведение марки машины к общему виду для updateMark($mark)
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
            "REF_CODE" => $mark["REF_CODE"],
            "MODELS" => [],
        ];

        foreach ($mark["MODELS"] as $model) {
            $model = [
                "NAME" => $model['NAME'],
                "CATEGORY_CODE" => $model["CATEGORY_CODE"],
                "REF_CODE" => $model['REF_CODE'],
            ];
            $res["MODELS"][] = $model;
        }
        return $res;
    }

    /**Отделение названия модели от названия марки машины
     * @param string $name
     * @param string $mark_name
     * @return string
     */
    private function parseModelName(string $name, string $mark_name): string
    {
        return trim(str_replace($mark_name, "", $name));
    }

    /**отделение марки машины из полного названия
     * @param string $name
     * @return string
     */
    private function parseMarkName(string $name): string
    {
        //проверка среди многословных марок машин
        foreach ($this->two_word_marks as $two_word_mark) {
            if (strpos($name, $two_word_mark) === 0) {
                return $two_word_mark;
            }
        }

        //берем первое слово
        return explode(' ', trim($name))[0];
    }

    private function doRequest($method, $data = [])
    {
        $data['Header'] = $this->getHeaders();

        $wrappedInMethod = [
            $method . 'Request' => $data
        ];

        $response = $this->postRequest(
            $this->carReferenceApiUrl,
            $wrappedInMethod,
            [],
            false,
            false,
            true
        );

        if (!empty($response[$method . 'Response'][$method])) {
            return [
                'error' => false,
                'content' => $response[$method . 'Response'][$method] ?? null
            ];
        } else {
            return [
                'error' => true,
                'errorMessage' => $response[$method . 'Response']['errorDescr'] ?? null
            ];
        }
    }

    private function getTypesMap()
    {
        return [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
            'tb' => 5,
            'tm' => 6,
            'коммунальная' => 9,
            'погрузчик' => 13
        ];
    }

    /**
     * Получение типов ТС(по факту это категории, ноо ни не обозначены как категории).
     * Метод не используется, т.к. по заверению ск список статичный и не будет меняться
     * @return array
     */
    private function getTypes()
    {
        return $this->doRequest('listTypes');
    }

    /**
     * Получение годов выпуска автомобилей для типа ТС
     * @param $vehTypeId - идентификатор типа ТС
     * @return array
     */
    private function getYears($vehTypeId)
    {
        $data = [
            'vehicleType' => (string)$vehTypeId
        ];
        return $this->doRequest('listYears', $data);
    }

    /**
     * Получение марок автомобилей по выбранному году и типу ТС
     * @param $year - год выпуска
     * @param $vehTypeId - идентификатор типа ТС
     * @return array
     */
    private function getMakers($year, $vehTypeId)
    {
        $data = [
            'year' => (string)$year,
            'vehicleType' => (string)$vehTypeId
        ];
        return $this->doRequest('listMakers', $data);
    }

    /**
     * Получение списка моделей по идентификатору марки, году и типу ТС
     * @param $year - год выпуска
     * @param $vehTypeId - идентификатор типа ТС
     * @param $makerId -  идентификатор марки
     * @return array
     */
    private function getModels($year, $vehTypeId, $makerId)
    {
        $data = [
            'year' => (string)$year,
            'vehicleType' => (string)$vehTypeId,
            'maker' => (string)$makerId
        ];
        return $this->doRequest('listModels', $data);
    }

    /**
     * установка заголовков авторизации
     * @return array
     */
    private function getHeaders()
    {
        return [
            'user' => config('api_sk.tinkoff.user'),
            'password' => config('api_sk.tinkoff.password')
        ];
    }

    public function getCarsArray()
    {
        $marks = [];
        $types = $this->getTypesMap();
        foreach ($types as $code => $type) {
            $years = $this->getYears($type)['content'] ?? null;
            if ($years) {
                foreach ($years as $year) {
                    $year = $year['year'];
                    $yearMakers = $this->getMakers($year, $type)['content'] ?? null;

                    if ($yearMakers) {
                        $yearMakers = !isset($yearMakers['id']) ? $yearMakers : [$yearMakers];
                        foreach ($yearMakers as $yearMaker) {
                            if (!empty($yearMaker['id'])) {
                                if (!isset($marks[$yearMaker['name']])) {
                                    $marks[$yearMaker['name']] = [
                                        'NAME' => $yearMaker['name'],
                                        'REF_CODE' => $yearMaker['id'],
                                        'MODELS' => []
                                    ];
                                }
                                $models = $this->getModels($year, $type, $yearMaker['id'])['content'] ?? null;
                                if ($models) {
                                    $models = !isset($models['id']) ? $models : [$models];
                                    foreach ($models as $model) {
                                        if (!isset($marks[$yearMaker['name']]['MODELS'][$model['id']])) {
                                            $marks[$yearMaker['name']]['MODELS'][$model['id']] = [
                                                'NAME' => $model['name'],
                                                'REF_CODE' => $model['id'],
                                                'CATEGORY_CODE' => $code,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $marks;
    }
}
