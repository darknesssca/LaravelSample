<?php


use App\Models\DocType;
use Illuminate\Database\Seeder;

class DocTypeSeeder extends Seeder
{
    protected static $docTypes = [
        [
            'id' => 1,
            'code' => 'pts',
            'name' => 'ПТС',
        ],
        [
            'id' => 2,
            'code' => 'sts',
            'name' => 'СТС',
        ],
        [
            'id' => 3,
            'code' => 'RussianPassport',
            'name' => 'Паспорт',
        ],
        [
            'id' => 4,
            'code' => 'ForeignPassport',
            'name' => 'Иностранный паспорт',
        ],
        [
            'id' => 5,
            'code' => 'DriverLicense',
            'name' => 'ВУ',
        ],
        [
            'id' => 6,
            'code' => 'ForeignDriverLicense',
            'name' => 'ВУ иностранного образца',
        ],
        [
            'id' => 7,
            'code' => 'Inspection',
            'name' => 'Талон ТО',
        ],
        [
            'id' => 8,
            'code' => 'ForeignInspection',
            'name' => 'Талон ТО иностранного образца',
        ],
    ];

    public function run()
    {
        foreach (self::$docTypes as $docType) {
            DocType::updateOrCreate(
                [
                    'id' => $docType['id'],
                    'code' => $docType['code']
                ],
                $docType
            );
        }

    }
}
