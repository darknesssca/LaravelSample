<?php


namespace App\Services\Company\Tinkoff;


use App\Services\Company\GuidesSourceInterface;
use App\Services\Company\GuidesSourceTrait;
use Laravel\Lumen\Application;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class TinkoffGuidesService extends TinkoffService implements GuidesSourceInterface
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

    use GuidesSourceTrait;

    public function __construct()
    {
        parent::__construct();
    }


    public function updateCarModelsGuides(): bool
    {
        $filename = Application::getInstance()->basePath() . "/storage/import/tinkoff_cars_import.xlsx"; //todo: сделать импорт из minio
        try {
            $arr = $this->readDocument($filename);


            foreach ($arr as $mark) {
                $val = $this->prepareMark($mark);
                if (count($val) == 0) {
                    continue;
                }
                $cnt = $this->updateMark($val);
            }
        } catch (Exception $e) {
            return false;
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
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
            "REF_CODE" => $mark["NAME"],
            "MODELS" => [],
        ];

        foreach ($mark["MODELS"] as $model) {
            $model = [
                "NAME" => $model['NAME'],
                "CATEGORY_CODE" => "B", //у тинькова все машины категории В
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
}
