<?php


namespace App\Services\Company;


use App\Models\CarCategory;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\InsuranceMark;
use App\Models\InsuranceModel;
use Illuminate\Support\Str;

trait GuidesSourceTrait
{
    /**обновление или добавление марки машины и её моделей
     *$mark = [NAME, REF_CODE, MODELS=[NAME,REF_CODE,CATEGORY_CODE]]
     * @param array $mark
     * @return int
     */
    protected function updateMark(array $mark): int
    {
        if (!array_key_exists('NAME', $mark) ||
            !array_key_exists('REF_CODE', $mark) ||
            !array_key_exists('MODELS', $mark) ||
            count($mark['MODELS']) == 0) {
            return 0;
        }
        //МАРКИ
        //добавление в общие таблицы
        $mark_com = CarMark::updateOrCreate([
            'code' => $this->getCode($mark['NAME']),
        ], [
            'name' => $mark['NAME'],
        ]);
        //добавление в таблицы СК
        $mark_sk = InsuranceMark::updateOrCreate([
            'mark_id' => $mark_com->id,
            'insurance_company_id' => $this->companyId,
        ],
            ['reference_mark_code' => $mark['REF_CODE'],]);

        //МОДЕЛИ
        foreach ($mark["MODELS"] as $model) {
            //общие таблицы
            $cat_code = $this->getCatCode($model["CATEGORY_CODE"]);
            if (!$cat_code) {
                continue;
            }

            $cat = CarCategory::updateOrCreate([
                'code' => $this->getCode($cat_code),
            ], [
                'name' => $cat_code,
            ]);
            $model_com = CarModel::updateOrCreate([
                'code' => $this->getCode($model['NAME']),
                'mark_id' => $mark_com->id,
            ], [
                'name' => $model['NAME'],
                'category_id' => $cat->id,
            ]);

            //таблицы СК
            $model_sk = InsuranceModel::updateOrCreate(
                [
                    "model_id" => $model_com->id,
                    'insurance_company_id' => $this->companyId,
                ],
                ['reference_model_code' => $model['REF_CODE']]
            );
        }
        return count($mark["MODELS"]);
    }

    /**генерация кода по названию
     * @param string $name
     * @return string
     */
    private function getCode(string $name): string
    {
        return Str::slug($name);
    }

    /**подбор категории из списка
     * @param string $cat_raw
     * @return string
     */
    private function getCatCode(string $cat_raw)
    {
        $cat_list = ['a', 'b', 'c', 'd', 'e', 'f', 'трактор', 'трамвай', 'троллейбус', 'вездеход', 'погрузчик', 'автокран', 'коммунальная', 'кран', 'трейлер'];
        $acc = [
            'трамвай' => 'tm',
            'троллейбус' => 'tb',
            'прицеп' => 'e',
            'тракторспецтехника' => 'трактор',
            'экскаватор-погрузчик' => 'погрузчик',
            'автопогрузчик' => 'погрузчик',
        ];

        $ncat = mb_strtolower($cat_raw);
        if (in_array($ncat, $cat_list)) {
            return $cat_raw;
        }
        if (array_key_exists($ncat, $acc)) {
            return $acc[$ncat];
        }
        return false;
    }
}
