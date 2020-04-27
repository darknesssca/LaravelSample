<?php


namespace App\Services\Company;


use App\Contracts\Company\Ingosstrah\IngosstrahGuidesSourceContract;
use App\Contracts\Company\Renessans\RenessansGuidesSourceContract;
use App\Contracts\Company\Soglasie\SoglasieGuidesSourceContract;
use App\Contracts\Company\Tinkoff\TinkoffGuidesSourceContract;
use App\Models\Country;
use App\Traits\GuidesSourceTrait;
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
            app(RenessansGuidesSourceContract::class),
            //app(IngosstrahGuidesSourceContract::class),
           // app(SoglasieGuidesSourceContract::class),
            //app(TinkoffGuidesSourceContract::class),
        ];

       // self::loadCountries();

        foreach ($companies as $company) {
            /** @var CompanyService $company */
            echo "Импорт марок и моделей: " . $company::companyCode . "\n";

            if (!$company->updateCarModelsGuides()) {
                echo "!!!!!!!!!!!!!!!!!!!!!!!!ОШИБКА!!!!!!!!!!!!!!!!!!!!!!!!";
            }
        }

        echo "Удаление лишних марок...\n";
        //GuidesSourceTrait::cleanDB();
    }

    /**
     * обновление общей таблицы стран
     */
    private static function loadCountries()
    {
        DB::transaction(function () {
            echo "Обновление списка стран\n";
            $filename = Application::getInstance()->basePath() . "/storage/import/countries.json"; //todo: сделать импорт из minio
            $arr = json_decode(file_get_contents($filename), true);
            $i = 0;
            $count = count($arr);
            foreach ($arr as $item) {
                $i++;
                $name = array_key_exists("FULLNAME", $item) ? $item["FULLNAME"] : $item["SHORTNAME"];
                $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
                echo "Добавление страны $name, $i из $count\n";
                Country::updateOrCreate([
                    'code' => $item["CODE"],
                ], [
                    "name" => $name,
                    "short_name" => mb_convert_case($item["SHORTNAME"], MB_CASE_TITLE, "UTF-8"),
                    "alpha2" => $item["ALFA2"],
                    "alpha3" => $item["ALFA3"],
                ]);
            }
            echo "Страны обновлены\n";
        });
    }
}
