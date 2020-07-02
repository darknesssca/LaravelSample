<?php


use App\Models\UsageTargetInsurance;
use Illuminate\Database\Seeder;

class InsuranceUsageTargetSeeder extends Seeder
{
    protected static $insuranceUsageTargets = [
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
//        [
//            'target_id' => 2,
//            'insurance_company_id' => 1,
//            'reference_usage_target_code' => 'Такси',
//            'reference_usage_target_code2' => 'Такси',
//        ],
//        [
//            'target_id' => 2,
//            'insurance_company_id' => 2,
//            'reference_usage_target_code' => 'Taxi',
//            'reference_usage_target_code2' => 'Taxi',
//        ],
//        [
//            'target_id' => 2,
//            'insurance_company_id' => 3,
//            'reference_usage_target_code' => '5',
//            'reference_usage_target_code2' => 'Taxi',
//        ],
//        [
//            'target_id' => 2,
//            'insurance_company_id' => 4,
//            'reference_usage_target_code' => 'taxi',
//            'reference_usage_target_code2' => 'taxi',
//        ],
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

    public function run()
    {
        foreach (self::$insuranceUsageTargets as $insuranceUsageTarget) {
            UsageTargetInsurance::updateOrCreate(
                [
                    'target_id' => $insuranceUsageTarget['target_id'],
                    'insurance_company_id' => $insuranceUsageTarget['insurance_company_id']
                ],
                $insuranceUsageTarget
            );
        }

    }
}
