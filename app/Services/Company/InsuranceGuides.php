<?php


namespace App\Services\Company;


use App\Contracts\Company\Ingosstrah\IngosstrahGuidesSourceContract;
use App\Contracts\Company\Renessans\RenessansGuidesSourceContract;
use App\Contracts\Company\Soglasie\SoglasieGuidesSourceContract;
use App\Contracts\Company\Tinkoff\TinkoffGuidesSourceContract;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Application;

abstract class InsuranceGuides
{

    /**
     * artisan команда обновления справочников
     */
    public static function refreshGuides()
    {
        //список объектов, реализующих интерфейс GuidesSourceContract
        $companies = [
           // app(RenessansGuidesSourceContract::class),
            //app(IngosstrahGuidesSourceContract::class),
           // app(SoglasieGuidesSourceContract::class),
           // app(TinkoffGuidesSourceContract::class),
        ];

        self::loadCountries();

        foreach ($companies as $company) {
            /** @var CompanyService $company */
            echo "Импорт марок и моделей: " . $company::companyCode. "\n";

            if (!$company->updateCarModelsGuides()) {
                echo "!!!!!!!!!!!!!!!!!!!!!!!!ОШИБКА!!!!!!!!!!!!!!!!!!!!!!!!";
            }
        }

        echo "Удаление лишних марок...\n";
        GuidesSourceTrait::cleanDB();
    }

    /**
     * обновление общей таблицы стран
     */
    private static function loadCountries()
    {
        echo "Обновление списка стран\n";
        DB::transaction(function () {
            $filename = Application::getInstance()->basePath() . "/storage/import/countries.json"; //todo: сделать импорт из minio
            $arr = (array)json_decode(file_get_contents($filename));
            $models = [];
            Country::truncate();
            foreach ($arr as $item) {
                $item = (array)$item;
                $models[] = [
                    "code" => $item["CODE"],
                    "name" => array_key_exists("FULLNAME", $item) ? $item["FULLNAME"] : $item["SHORTNAME"],
                    "short_name" => $item["SHORTNAME"],
                    "alpha2" => $item["ALFA2"],
                    "alpha3" => $item["ALFA3"],
                ];
            }
            Country::insert($models);
        });
        echo "Страны обновлены\n";
    }
}
