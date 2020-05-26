<?php


use App\Models\PolicyType;
use Illuminate\Database\Seeder;

class PolicyTypeSeeder extends Seeder
{
    protected static $policyTypes = [
        [
            'id' => 1,
            'code' => 'osago',
            'name' => 'ОСАГО',
        ],
    ];

    public function run()
    {
        foreach (self::$policyTypes as $policyType) {
            PolicyType::updateOrCreate(
                [
                    'id' => $policyType['id'],
                    'code' => $policyType['code']
                ],
                $policyType
            );
        }
    }
}
