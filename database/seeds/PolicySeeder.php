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
        [
            'code' => 'Inspection',
            'name' => 'Талон ТО',
        ],
        [
            'code' => 'ForeignInspection',
            'name' => 'Талон ТО иностранного образца',
        ],
    ];

    //реф коды СК для документов
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
            'reference_doctype_code' => "driver_license",
            'reference_doctype_code2' => "driver_license",
            'reference_doctype_code3' => "driver_license",
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
            'reference_doctype_code' => 51,
            'reference_doctype_code2' => 51,
            'reference_doctype_code3' => 51,
        ],
        [
            'doctype_id' => 7,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "TO",
            'reference_doctype_code2' => "TO",
            'reference_doctype_code3' => "TO",
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
            'reference_doctype_code' => 51,
            'reference_doctype_code2' => 51,
            'reference_doctype_code3' => 51,
        ],
        [
            'doctype_id' => 8,
            'insurance_company_id' => 4,
            'reference_doctype_code' => "TO",
            'reference_doctype_code2' => "TO",
            'reference_doctype_code3' => "TO",
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
            'code' => 'personal',
            'name' => 'Личная',
        ],
        [
            'code' => 'taxi',
            'name' => 'Такси',
        ],
        [
            'code' => 'rent',
            'name' => 'Сдача в аренду',
        ],
        [
            'code' => 'training',
            'name' => 'Учебная езда',
        ],
        [
            'code' => 'dangerous',
            'name' => 'Перевозка опасных и легковоспламеняющихся грузов',
        ],
        [
            'code' => 'passenger',
            'name' => 'Пассажирские перевозки',
        ],
        [
            'code' => 'emergency',
            'name' => 'Экстренные и коммунальные службы',
        ],
        [
            'code' => 'road',
            'name' => 'Дорожные и специальные ТС',
        ],
        [
            'code' => 'collection',
            'name' => 'Инкассация',
        ],
        [
            'code' => 'ambulance',
            'name' => 'Скорая помощь',
        ],
        [
            'code' => 'other',
            'name' => 'Прочее',
        ],
    ];

    protected static $insuranceUsageTarget = [
        // Личная
        [
            'target_id' => 1,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'Личная',
            'reference_usage_target_code2' => 'Личная',
        ],
        [
            'target_id' => 1,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Personal',
            'reference_usage_target_code2' => 'Personal',
        ],
        [
            'target_id' => 1,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '1',
            'reference_usage_target_code2' => 'Personal',
        ],
        [
            'target_id' => 1,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'personal',
            'reference_usage_target_code2' => 'personal',
        ],
        // Такси
        [
            'target_id' => 2,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'Такси',
            'reference_usage_target_code2' => 'Такси',
        ],
        [
            'target_id' => 2,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Taxi',
            'reference_usage_target_code2' => 'Taxi',
        ],
        [
            'target_id' => 2,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '5',
            'reference_usage_target_code2' => 'Taxi',
        ],
        [
            'target_id' => 2,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'taxi',
            'reference_usage_target_code2' => 'taxi',
        ],
        // Сдача в аренду
        [
            'target_id' => 3,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'СдачаВАренду',
            'reference_usage_target_code2' => 'СдачаВАренду',
        ],
        [
            'target_id' => 3,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Rent',
            'reference_usage_target_code2' => 'Rent',
        ],
        [
            'target_id' => 3,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '11',
            'reference_usage_target_code2' => 'Rent',
        ],
        [
            'target_id' => 3,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'rental',
            'reference_usage_target_code2' => 'rental',
        ],
        // Учебная езда
        [
            'target_id' => 4,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'УчебнаяЕзда',
            'reference_usage_target_code2' => 'УчебнаяЕзда',
        ],
        [
            'target_id' => 4,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'RidingTraining',
            'reference_usage_target_code2' => 'RidingTraining',
        ],
        [
            'target_id' => 4,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '2',
            'reference_usage_target_code2' => 'RidingTraining',
        ],
        [
            'target_id' => 4,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'training_driving',
            'reference_usage_target_code2' => 'training_driving',
        ],
        // Перевозка опасных и легковоспламеняющихся грузов
        [
            'target_id' => 5,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'ОпасныйГруз',
            'reference_usage_target_code2' => 'ОпасныйГруз',
        ],
        [
            'target_id' => 5,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'DangerousCargo',
            'reference_usage_target_code2' => 'DangerousCargo',
        ],
        [
            'target_id' => 5,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '10',
            'reference_usage_target_code2' => 'DangerousAndFlammable',
        ],
        [
            'target_id' => 5,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'dangerous_goods',
            'reference_usage_target_code2' => 'dangerous_goods',
        ],
        // Пассажирские перевозки
        [
            'target_id' => 6,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'ПассажирскиеПеревозки',
            'reference_usage_target_code2' => 'ПассажирскиеПеревозки',
        ],
        [
            'target_id' => 6,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'PassengerService',
            'reference_usage_target_code2' => 'PassengerService',
        ],
        [
            'target_id' => 6,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '12',
            'reference_usage_target_code2' => 'RegularPassengers',
        ],
        [
            'target_id' => 6,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'passenger_transportation',
            'reference_usage_target_code2' => 'passenger_transportation',
        ],
        // Экстренные и коммунальные службы
        [
            'target_id' => 7,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'ЭкстренныеИКоммСлужбы',
            'reference_usage_target_code2' => 'ЭкстренныеИКоммСлужбы',
        ],
        [
            'target_id' => 7,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Emergency',
            'reference_usage_target_code2' => 'Emergency',
        ],
        [
            'target_id' => 7,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '13',
            'reference_usage_target_code2' => 'EmergencyAndMunicipal',
        ],
        [
            'target_id' => 7,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'emergency_services',
            'reference_usage_target_code2' => 'emergency_services',
        ],
        // Дорожные и специальные ТС
        [
            'target_id' => 8,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'ДорожныеИСпециальныеТС',
            'reference_usage_target_code2' => 'ДорожныеИСпециальныеТС',
        ],
        [
            'target_id' => 8,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'RoadVehicles',
            'reference_usage_target_code2' => 'RoadVehicles',
        ],
        [
            'target_id' => 8,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '6',
            'reference_usage_target_code2' => 'TrafficAndSpecial',
        ],
        [
            'target_id' => 8,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'road_and_special_vehicles',
            'reference_usage_target_code2' => 'road_and_special_vehicles',
        ],
        // Инкассация
        [
            'target_id' => 9,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'Прочее',
            'reference_usage_target_code2' => 'Прочее',
        ],
        [
            'target_id' => 9,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Others',
            'reference_usage_target_code2' => 'Others',
        ],
        [
            'target_id' => 9,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '3',
            'reference_usage_target_code2' => 'Collection',
        ],
        [
            'target_id' => 9,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'other',
            'reference_usage_target_code2' => 'other',
        ],
        // Скорая помощь
        [
            'target_id' => 10,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'ЭкстренныеИКоммСлужбы',
            'reference_usage_target_code2' => 'ЭкстренныеИКоммСлужбы',
        ],
        [
            'target_id' => 10,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Emergency',
            'reference_usage_target_code2' => 'Emergency',
        ],
        [
            'target_id' => 10,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '4',
            'reference_usage_target_code2' => 'Ambulance',
        ],
        [
            'target_id' => 10,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'emergency_services',
            'reference_usage_target_code2' => 'emergency_services',
        ],
        // Прочее
        [
            'target_id' => 11,
            'insurance_company_id' => 1,
            'reference_usage_target_code' => 'Прочее',
            'reference_usage_target_code2' => 'Прочее',
        ],
        [
            'target_id' => 11,
            'insurance_company_id' => 2,
            'reference_usage_target_code' => 'Others',
            'reference_usage_target_code2' => 'Others',
        ],
        [
            'target_id' => 11,
            'insurance_company_id' => 3,
            'reference_usage_target_code' => '9',
            'reference_usage_target_code2' => 'Other',
        ],
        [
            'target_id' => 11,
            'insurance_company_id' => 4,
            'reference_usage_target_code' => 'other',
            'reference_usage_target_code2' => 'other',
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
