<?php


use App\Models\DocTypeInsurance;
use Illuminate\Database\Seeder;

class DocTypeInsuranceSeeder extends Seeder
{
    protected static $docTypeInsurance = [
        //ПТС
        [
            'doctype_id' => 1,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 30,
            'reference_doctype_code2' => 30,
            'reference_doctype_code3' => 30,
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 34709216,
            'reference_doctype_code2' => 34709216,
            'reference_doctype_code3' => 34709216,
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 30,
            'reference_doctype_code2' => 30,
            'reference_doctype_code3' => 30,
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "PTS",
            'reference_doctype_code2' => "PTS",
            'reference_doctype_code3' => "PTS",
        ],
        //СТС
        [
            'doctype_id' => 2,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 31,
            'reference_doctype_code2' => 31,
            'reference_doctype_code3' => 31,
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 34709216,
            'reference_doctype_code2' => 34709216,
            'reference_doctype_code3' => 34709216,
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 31,
            'reference_doctype_code2' => 31,
            'reference_doctype_code3' => 31,
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "STS",
            'reference_doctype_code2' => "STS",
            'reference_doctype_code3' => "STS",
        ],
        //Паспорт
        [
            'doctype_id' => 3,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "RussianPassport",
            'reference_doctype_code2' => "RussianPassport",
            'reference_doctype_code3' => "RussianPassport",
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 30363316,
            'reference_doctype_code2' => 30363316,
            'reference_doctype_code3' => 30363316,
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 12,
            'reference_doctype_code2' => 6,
            'reference_doctype_code3' => 12,
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "passport_russian",
            'reference_doctype_code2' => "passport_russian",
            'reference_doctype_code3' => "passport_russian",
        ],
        //ВУ
        [
            'doctype_id' => 5,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "DriverLicense",
            'reference_doctype_code2' => "DriverLicense",
            'reference_doctype_code3' => "DriverLicense",
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 765912000,
            'reference_doctype_code2' => 765912000,
            'reference_doctype_code3' => 765912000,
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 20,
            'reference_doctype_code2' => 15,
            'reference_doctype_code3' => 20,
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "driver_license_russian_B",
            'reference_doctype_code2' => "driver_license_russian_B",
            'reference_doctype_code3' => "driver_license_russian_B",
        ],
        //Иностранное ВУ
        [
            'doctype_id' => 6,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "DriverLicense",
            'reference_doctype_code2' => "DriverLicense",
            'reference_doctype_code3' => "DriverLicense",
        ],
        [
            'doctype_id' => 6,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 765912000, //в справочнике нет Иностранного ВУ
            'reference_doctype_code2' => 765912000,
            'reference_doctype_code3' => 765912000,
        ],
        [
            'doctype_id' => 6,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 22,
            'reference_doctype_code2' => 35,
            'reference_doctype_code3' => 22,
        ],
        [
            'doctype_id' => 6,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "driver_license_international", //в справочнике: "Международное водительское удостоверение"
            'reference_doctype_code2' => "driver_license_international",
            'reference_doctype_code3' => "driver_license_international",
        ],

        //Иностранный паспорт
        [
            'doctype_id' => 4,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "ForeignPassport",
            'reference_doctype_code2' => "ForeignPassport",
            'reference_doctype_code3' => "ForeignPassport",
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 37713516,
            'reference_doctype_code2' => 37713516,
            'reference_doctype_code3' => 37713516,
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 7,
            'reference_doctype_code2' => 3,
            'reference_doctype_code3' => 7,
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "passport_foreign",
            'reference_doctype_code2' => "passport_foreign",
            'reference_doctype_code3' => "passport_foreign",
        ],

        //талон ТО
        [
            'doctype_id' => 7,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "Inspection",
            'reference_doctype_code2' => "Inspection",
            'reference_doctype_code3' => "Inspection",
        ],
        [
            'doctype_id' => 7,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 3507627803,
            'reference_doctype_code2' => 3507627803,
            'reference_doctype_code3' => 3507627803,
        ],
        [
            'doctype_id' => 7,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 53,
            'reference_doctype_code2' => 53,
            'reference_doctype_code3' => 53,
        ],
        [
            'doctype_id' => 7,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "DIAGNOSTIC_CARD",
            'reference_doctype_code2' => "DIAGNOSTIC_CARD",
            'reference_doctype_code3' => "DIAGNOSTIC_CARD",
        ],

        //Иностранный талон ТО
        [
            'doctype_id' => 8,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "Inspection",
            'reference_doctype_code2' => "Inspection",
            'reference_doctype_code3' => "Inspection",
        ],
        [
            'doctype_id' => 8,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 3507627803,
            'reference_doctype_code2' => 3507627803,
            'reference_doctype_code3' => 3507627803,
        ],
        [
            'doctype_id' => 8,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 53,
            'reference_doctype_code2' => 53,
            'reference_doctype_code3' => 53,
        ],
        [
            'doctype_id' => 8,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "DIAGNOSTIC_CARD",
            'reference_doctype_code2' => "DIAGNOSTIC_CARD",
            'reference_doctype_code3' => "DIAGNOSTIC_CARD",
        ],
    ];

    public function run()
    {
        foreach (self::$docTypeInsurance as $type) {
            DocTypeInsurance::updateOrCreate(
                [
                    'doctype_id' => $type['doctype_id'],
                    'insurance_company_id' => $type['insurance_company_id']
                ],
                $type
            );
        }
    }
}
