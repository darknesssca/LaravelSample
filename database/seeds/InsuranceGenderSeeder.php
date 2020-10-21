<?php


use App\Models\GenderInsurance;
use Illuminate\Database\Seeder;

class InsuranceGenderSeeder extends Seeder
{
    protected static $insuranceGenders = [
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
        [
            'gender_id' => 1,
            'insurance_company_id' => 5,
            'reference_gender_code' => '1',
        ],

        //Ж
        [
            'gender_id' => 2,
            'insurance_company_id' => 1,
            'reference_gender_code' => 'female',
        ],
        [
            'gender_id' => 2,
            'insurance_company_id' => 2,
            'reference_gender_code' => 'Ж',
        ],
        [
            'gender_id' => 2,
            'insurance_company_id' => 3,
            'reference_gender_code' => 'female',
        ],
        [
            'gender_id' => 2,
            'insurance_company_id' => 4,
            'reference_gender_code' => 'female',
        ],
        [
            'gender_id' => 2,
            'insurance_company_id' => 5,
            'reference_gender_code' => '2',
        ],
    ];

    public function run()
    {
        foreach (self::$insuranceGenders as $insuranceGender) {
            GenderInsurance::updateOrCreate(
                [
                    'gender_id' => $insuranceGender['gender_id'],
                    'insurance_company_id' => $insuranceGender['insurance_company_id']
                ],
                $insuranceGender
            );
        }
    }
}
