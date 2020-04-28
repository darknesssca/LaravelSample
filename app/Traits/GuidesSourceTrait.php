<?php


namespace App\Traits;


use App\Models\CarCategory;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\InsuranceCompany;
use App\Models\InsuranceMark;
use App\Models\InsuranceModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait GuidesSourceTrait
{
    /**массив соответствия разных названий марок и нормальных
     * @var array
     */
    private $mark_replace = [
        "ваз/lada" => "ВАЗ",
        "lada" => "ВАЗ",
        "лада" => "ВАЗ",
        "хундай" => "Hyundai",
        "хенде" => "Hyundai",
        "хендэ" => "Hyundai",
        "хендай" => "Hyundai",
        "хендэе" => "Hyundai",
        "мицубиси" => "Mitsubishi",
        "шкода" => "Skoda",
        "ситроен" => "Citroen",
        "фольксваген" => "Volkswagen",
        "форд" => "Ford",
        "субару" => "Subaru",
        "бмв" => "BMW",
        "mercedes" => "Mercedes-Benz",
        "мерседес" => "Mercedes-Benz",
        "minsk(минск)" => "Минск",
        "minsk" => "Минск",
        "general motors" => "GMC",
        "ман" => "MAN",
        "dongfeng" => "DongFeng",
        "ютонг" => "Yutong",
        "uaz"=>'УАЗ',
        "gaz"=>"ГАЗ",
        "тойота"=>"Toyota",
        "ниссан"=>"Nissan",
        "исузу"=>"Isuzu",
        "tagaz"=>"ТагАЗ",
        "вольво"=>"Volvo",
        "maz"=>"МАЗ",
        "сузуки"=>"Suzuki",
    ];

    /**массив названий марок в нижнем регистре, которые не добавляем вообще
     * @var array
     */
    private $mark_dismiss = [
        ".(отеч.)",
        "погрузчик",
        "грейдер",
        "автогрейдер",
        "электромобиль",
        "тушинский авиазавод",
        "вездеход",
    ];

    /**массив названий моделей в нижнем регистре, которые не добавляем вообще
     * @var array
     */
    private $model_dismiss = [
        ".(отеч.)",
    ];

    /**обновление или добавление марки машины и её моделей
     *$mark = [NAME, REF_CODE, MODELS=[NAME,REF_CODE,CATEGORY_CODE]]
     * @param array $mark
     * @return int
     * @throws \Exception
     */
    protected function updateMark(array $mark): int
    {
        if (!array_key_exists('NAME', $mark) ||
            !array_key_exists('REF_CODE', $mark) ||
            !array_key_exists('MODELS', $mark) ||
            count($mark['MODELS']) == 0) {
            return 0;
        }

        //проверка имени марки
        if (!$mark["NAME"] = $this->getMarkName($mark["NAME"])) {
            return 0;
        }

        $cnt = count($mark["MODELS"]);
        echo "Добавляется марка: " . $mark['NAME'] . " ($cnt моделей)\n";

        DB::beginTransaction(); //начало транзакции

        try {
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
                //проверка кода категории
                $cat_code = $this->getCatCode($model["CATEGORY_CODE"]);
                if (!$cat_code) {
                    continue;
                }

                //проверка имени модели
                if (!$model["NAME"] = $this->getModelName($model["NAME"])) {
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
        } catch (\Exception $ex) {
            dump($ex);
            DB::rollBack(); //откат транзакции, если что-то случилось
            throw $ex;
        }
        // Если всё хорошо - фиксируем
        DB::commit();

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
        $cat_list = ['a', 'b', 'c', 'd', 'e', 'f', 'трактор', 'Tm', 'Tb', 'вездеход', 'погрузчик', 'автокран', 'коммунальная', 'кран', 'трейлер'];
        $acc = [
            'трамвай' => 'Tm',
            'троллейбус' => 'Tb',
            'прицеп' => 'E',
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

    /**проверяет марку по словарям и возвращает правильное имя или false, если эту марку не добавляем
     * @param  $name
     * @return mixed
     */
    private function getMarkName($name)
    {
        if (empty($name))
            return false;

        //проверка надо ли добавлять марку
        foreach ($this->mark_dismiss as $item) {
            if (strpos(mb_strtolower($name), $item) === -1) {
                return false;
            }
        }

        //выбор имени марки по словарю
        if (array_key_exists(mb_strtolower($name), $this->mark_replace)) {
            return $this->mark_replace[mb_strtolower($name)];
        }

        //если марку надо добавить и не надо менять имя, то так и оставляем
        return $name;
    }

    /**проверяет модель по словарям и возвращает правильное имя или false, если эту модель не добавляем
     * @param  $name
     * @return mixed
     */
    private function getModelName($name)
    {
        if (empty($name))
            return false;
        //проверка надо ли добавлять модель
        foreach ($this->model_dismiss as $item) {
            if (strpos(mb_strtolower($name), $item) === -1) {
                return false;
            }
        }
        return $name; //для моделей преобразование имени не делаем
    }

    /**
     * удаляет из БД марки машин, для которых нет кодов во всех СК
     */
    public static function cleanDB(): void
    {
        DB::transaction(function () {
            $companies_count = InsuranceCompany::where('active', true)->count();

            //МАРКИ
            //выбор всех марок машин, к которым привязано меньше $companies_count компаний
            $select = CarMark::selectRaw('id, COUNT(*) AS CarCount')
                ->join('insurance_marks', 'insurance_marks.mark_id', '=', 'car_marks.id')
                ->groupBy('id')
                ->havingRaw("COUNT(*) < $companies_count")
                ->get();
            $ids_marks = [];
            foreach ($select as $item) {
                $ids_marks[] = $item->id;
            }
            if (count($ids_marks) == 0) {
                return;
            }

            //id models to delete
            $select = CarModel::select('id')->whereIn('mark_id', $ids_marks)->get();
            $ids_models = [];
            foreach ($select as $item) {
                $ids_models[] = $item->id;
            }

            if (count($ids_models) == 0) return;
            InsuranceModel::whereIn('model_id', $ids_models)->delete();
            InsuranceMark::whereIn('mark_id', $ids_marks)->delete();
            CarModel::whereIn('mark_id', $ids_marks)->delete();
            $marks_cnt = CarMark::whereIn('id', $ids_marks)->delete();
            echo "Удалено $marks_cnt марок с моделями\n";
        });
    }
}
