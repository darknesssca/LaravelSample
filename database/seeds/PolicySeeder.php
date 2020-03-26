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

    //страховые компании
    protected static $insuranceCompanies = [
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'renessans',
            'name' => 'Ренессанс',
        ],
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'ingosstrah',
            'name' => 'Ингосстрах',
        ],
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'soglasie',
            'name' => 'Согласие',
        ],
        [
            'active' => true,
            'logo_id' => 1,
            'code' => 'tinkoff',
            'name' => 'Тинькофф',
        ],
    ];

    //типы полисов
    protected static $policyTypes = [
        [
            'code' => 'osago',
            'name' => 'ОСАГО',
        ],
    ];

    //статус полиса
    protected static $policyStatus = [
        [
            'active' => true,
            'code' => 'draft',
            'name' => 'Черновик',
        ],
        [
            'active' => true,
            'code' => 'issued',
            'name' => 'Оформлен',
        ],
        [
            'active' => true,
            'code' => 'paid',
            'name' => 'Оплачен',
        ],
    ];

    //типы документов
    protected static $docTypes = [
        [
            'code' => 'pts',
            'name' => 'ПТС',
        ],
        [
            'code' => 'sts',
            'name' => 'СТС',
        ],
        [
            'code' => 'RussianPassport',
            'name' => 'Паспорт',
        ],
        [
            'code' => 'ForeignPassport',
            'name' => 'Иностранный паспорт',
        ],
        [
            'code' => 'DriverLicense',
            'name' => 'ВУ',
        ],
        [
            'code' => 'ForeignDriverLicense',
            'name' => 'ВУ иностранного образца',
        ],
    ];

    //реф коды СК для документов
    protected static $docTypeInsurance = [
        //ПТС
        [
            'doctype_id' => 1,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 30
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 34709216
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 30
        ],
        [
            'doctype_id' => 1,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "PTS"
        ],
        //СТС
        [
            'doctype_id' => 2,
            'insurance_company_id' => 1,
            'reference_doctype_code' => 31
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 34709216
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 31
        ],
        [
            'doctype_id' => 2,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "STS"
        ],
        //Паспорт
        [
            'doctype_id' => 3,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "RussianPassport"
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 30363316
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 12
        ],
        [
            'doctype_id' => 3,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "passport_russian"
        ],
        //ВУ
        [
            'doctype_id' => 5,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "DriverLicense"
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 765912000
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 20
        ],
        [
            'doctype_id' => 5,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "driver_license"
        ],
        //Иностранное ВУ
        [
            'doctype_id' => 6,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "DriverLicense"
        ],
        [
            'doctype_id' => 6,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 765912000 //в справочнике нет Иностранного ВУ
        ],
        [
            'doctype_id' => 6,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 22
        ],
        [
            'doctype_id' => 6,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "driver_license_international", //в справочнике: "Международное водительское удостоверение"
        ],

        //Иностранный паспорт
        [
            'doctype_id' => 4,
            'insurance_company_id' => 1,
            'reference_doctype_code' => "ForeignPassport"
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 2,
            'reference_doctype_code' => 37713516
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 3,
            'reference_doctype_code' => 7
        ],
        [
            'doctype_id' => 4,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "passport_foreign"
        ],
    ];

    protected static $sourceAcquisition = [
        [
            'code' => 'PurchasedFromPerson',
            'name' => 'Куплено у физ./ юр. лица',
        ],
        [
            'code' => 'PurchasedInSalon',
            'name' => 'Куплено в салоне',
        ],
        [
            'code' => 'InSalon',
            'name' => 'Находится в салоне у дилера',
        ],
        [
            'code' => 'Pickup',
            'name' => 'Самоввоз',
        ],
        [
            'code' => 'other',
            'name' => 'Другое',
        ],
    ];

    protected static $insuranceAcquisition = [
        //остальные СК
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

        //тинькофф
        [
            'acquisition_id' => 1,
            'insurance_company_id' => 4,
            'reference_acquisition_code' => 'PURCHASED_FROM_PERSON',
        ],

        [
            'acquisition_id' => 2,
            'insurance_company_id' => 4,
            'reference_acquisition_code' => 'PURCHASED_IN_SALON',
        ],

        [
            'acquisition_id' => 3,
            'insurance_company_id' => 4,
            'reference_acquisition_code' => 'IN_SALON',
        ],
        [
            'acquisition_id' => 4,
            'insurance_company_id' => 4,
            'reference_acquisition_code' => 'PICKUP',
        ],
        [
            'acquisition_id' => 5,
            'insurance_company_id' => 4,
            'reference_acquisition_code' => 'OTHER',
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
            'name' => 'Мужской',
        ],
        [
            'code' => 'female',
            'name' => 'Женский',
        ],
    ];

    protected static $insuranceGender = [
        //М
        [
            'gender_id' => 1,
            'insurance_company_id' => 1,
            'reference_gender_code' => 'male',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 2,
            'reference_gender_code' => 'М',
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

        //Ж
        [
            'gender_id' => 1,
            'insurance_company_id' => 1,
            'reference_gender_code' => 'female',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 2,
            'reference_gender_code' => 'Ж',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 3,
            'reference_gender_code' => 'female',
        ],
        [
            'gender_id' => 1,
            'insurance_company_id' => 4,
            'reference_gender_code' => 'female',
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
        \App\Models\SourceAcquisition::insert(self::$sourceAcquisition);
        \App\Models\SourceAcquisitionInsurance::insert(self::$insuranceAcquisition);
        \App\Models\UsageTarget::insert(self::$usageTarget);
        \App\Models\UsageTargetInsurance::insert(self::$insuranceUsageTarget);
        \App\Models\Gender::insert(self::$gender);
        \App\Models\GenderInsurance::insert(self::$insuranceGender);
    }
}
