<?php


use App\Models\SourceAcquisitionInsurance;
use Illuminate\Database\Seeder;

class InsuranceAcquisitionSeeder extends Seeder
{
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

    public function run()
    {
        foreach (self::$insuranceAcquisition as $acquisition) {
            SourceAcquisitionInsurance::updateOrCreate(
                [
                    'acquisition_id' => $acquisition['acquisition_id'],
                    'insurance_company_id' => $acquisition['insurance_company_id']
                ],
                $acquisition
            );
        }
    }
}
