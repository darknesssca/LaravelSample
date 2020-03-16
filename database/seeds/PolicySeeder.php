<?php

use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    protected static $files = [
        [
            'name' => 1,
            'dir' => 1,
            'content_type' => 1,
            'size' => 1,
        ],
    ];

    protected static $insuranceCompanies = [
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'renessans',
            'name' => 'ренессанс',
        ],
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'ingosstrah',
            'name' => 'ингосстрах',
        ],
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'soglasie',
            'name' => 'согласие',
        ],
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'tinkoff',
            'name' => 'тинькофф',
        ],
    ];

    protected static $policyTypes = [
        [
            'code' => 'osago',
            'name' => 'osago',
        ],
    ];

    protected static $policyStatus = [
        [
            'active' => true,
            'code' => 'draft',
            'name' => 'draft',
        ],
    ];

    protected static $docTypes = [
        [
            'code' => 'passport',
            'name' => 'пасспорт',
        ],
        [
            'code' => 'pts',
            'name' => 'ПТС',
        ],
        [
            'code' => 'sts',
            'name' => 'СТС',
        ],
        [
            'code' => 'inspection',
            'name' => 'талон ТО',
        ],
        [
            'code' => 'license',
            'name' => 'права',
        ],
    ];

    protected static $docTypeInsurance = [
        [
            'doctype_id' => 1,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 'RussianPassport',
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 'PTS',
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 'STS',
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 'inspection',
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 'DriverLicense',
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 'passport',
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 'PTS',
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 'STS',
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 'inspection',
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 'driverLicense',
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 'passport',
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 'PTS',
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 'STS',
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 'inspection',
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 'driverLicense',
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 4,
            'reference_doctype_code' => 'passport_russian',
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 4,
            'reference_doctype_code' => 'PTS',
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 4,
            'reference_doctype_code' => 'STS',
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 4,
            'reference_doctype_code' => 'inspection',
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 4,
            'reference_doctype_code' => 'driver_license_russian_B',
        ],
    ];

    protected static $carMarks = [
        [
            'code' => 'nissan',
            'name' => 'Nissan',
        ],
    ];

    protected static $insuranceMark = [
        [
            'mark_id' => 1,
            'insurance_company_id' => 1,
            'reference_mark_code' => 'Nissan',
        ],
        [
            'mark_id' => 1,
            'insurance_company_id' => 2,
            'reference_mark_code' => 'Nissan',
        ],
        [
            'mark_id' => 1,
            'insurance_company_id' => 3,
            'reference_mark_code' => '555',
        ],
        [
            'mark_id' => 1,
            'insurance_company_id' => 4,
            'reference_mark_code' => '555',
        ],
    ];

    protected static $carCategory = [
        [
            'code' => 'B',
            'name' => 'B',
        ],
    ];

    protected static $carModel = [
        [
            'mark_id' => 1,
            'category_id' => 1,
            'code' => 'pixo',
            'name' => 'Pixo',
        ],
    ];

    protected static $insuranceModel = [
        [
            'model_id' => 1,
            'insurance_company_id' => 1,
            'reference_model_code' => 'Pixo',
        ],
        [
            'model_id' => 1,
            'insurance_company_id' => 2,
            'reference_model_code' => 'Pixo',
        ],
        [
            'model_id' => 1,
            'insurance_company_id' => 3,
            'reference_model_code' => '36892',
        ],
        [
            'model_id' => 1,
            'insurance_company_id' => 4,
            'reference_model_code' => '1360',
        ],
    ];

    protected static $regCountry = [
        [
            'code' => 'ru',
            'name' => 'россия',
        ],
    ];

    protected static $insuranceCountry = [
        [
            'country_id' => 1,
            'insurance_company_id' => 1,
            'reference_country_code' => 'RU',
        ],
        [
            'country_id' => 1,
            'insurance_company_id' => 2,
            'reference_country_code' => 'RU',
        ],
        [
            'country_id' => 1,
            'insurance_company_id' => 3,
            'reference_country_code' => 'RU',
        ],
        [
            'country_id' => 1,
            'insurance_company_id' => 4,
            'reference_country_code' => 'RU',
        ],
    ];

    protected static $sourceAcquisition = [
        [
            'code' => 'person',
            'name' => 'у человека',
        ],
    ];

    protected static $insuranceAcquisition = [
        [
            'acquisition_id' => 1,
            'insurance_company_id' => 1,
            'reference_acquisition_code' => 'PURCHASED_FROM_PERSON',
        ],
        [
            'acquisition_id' => 1,
            'insurance_company_id' => 2,
            'reference_acquisition_code' => 'PURCHASED_FROM_PERSON',
        ],
        [
            'acquisition_id' => 1,
            'insurance_company_id' => 3,
            'reference_acquisition_code' => 'PURCHASED_FROM_PERSON',
        ],
        [
            'acquisition_id' => 1,
            'insurance_company_id' => 4,
            'reference_acquisition_code' => 'PURCHASED_FROM_PERSON',
        ],
    ];

    protected static $usageType = [
        [
            'code' => 'person',
            'name' => 'лично',
        ],
    ];

    protected static $insuranceUsageType = [
        [
            'type_id' => 1,
            'insurance_company_id' => 1,
            'reference_usage_type_code' => 'Личная',
        ],
        [
            'type_id' => 1,
            'insurance_company_id' => 2,
            'reference_usage_type_code' => 'PURCHASED_FROM_PERSON',
        ],
        [
            'type_id' => 1,
            'insurance_company_id' => 3,
            'reference_usage_type_code' => '1',
        ],
        [
            'type_id' => 1,
            'insurance_company_id' => 4,
            'reference_usage_type_code' => 'personal',
        ],
    ];

    protected static $usageTarget = [
        [
            'code' => 'person',
            'name' => 'лично',
        ],
    ];

    protected static $insuranceUsageTarget = [
        [
            'target_id' => 1,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'Личная',
        ],
        [
            'target_id' => 1,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'PURCHASED_FROM_PERSON',
        ],
        [
            'target_id' => 1,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '1',
        ],
        [
            'target_id' => 1,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'personal',
        ],
    ];

    protected static $gender = [
        [
            'code' => 'male',
            'name' => 'мужской',
        ],
    ];

    protected static $insuranceGender = [
        [
            'gender_id' => 1,
            'insurance_company_id' => 1,
            'reference_gender_code' => 'male',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 2,
            'reference_gender_code' => 'male',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 3,
            'reference_gender_code' => 'male',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 4,
            'reference_gender_code' => 'male',
        ],
    ];

    public function run()
    {
        \App\Models\File::insert(self::$files);
        \App\Models\InsuranceCompany::insert(self::$insuranceCompanies);
        \App\Models\PolicyType::insert(self::$policyTypes);
        \App\Models\PolicyStatus::insert(self::$policyStatus);
        \App\Models\DocType::insert(self::$docTypes);
        \App\Models\DocTypeInsurance::insert(self::$docTypeInsurance);
        \App\Models\CarMark::insert(self::$carMarks);
        \App\Models\CarMarkInsurance::insert(self::$insuranceMark);
        \App\Models\CarCategory::insert(self::$carCategory);
        \App\Models\CarModel::insert(self::$carModel);
        \App\Models\CarModelInsurance::insert(self::$insuranceModel);
        \App\Models\RegCountry::insert(self::$regCountry);
        \App\Models\RegCountryInsurance::insert(self::$insuranceCountry);
        \App\Models\SourceAcquisition::insert(self::$sourceAcquisition);
        \App\Models\SourceAcquisitionInsurance::insert(self::$insuranceAcquisition);
        \App\Models\UsageType::insert(self::$usageType);
        \App\Models\UsageTypeInsurance::insert(self::$insuranceUsageType);
        \App\Models\UsageTarget::insert(self::$usageTarget);
        \App\Models\UsageTargetInsurance::insert(self::$insuranceUsageTarget);
        \App\Models\Gender::insert(self::$gender);
        \App\Models\GenderInsurance::insert(self::$insuranceGender);
    }
}
