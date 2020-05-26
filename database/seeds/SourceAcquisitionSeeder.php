<?php


use App\Models\SourceAcquisition;
use Illuminate\Database\Seeder;

class SourceAcquisitionSeeder extends Seeder
{
    protected static $sourceAcquisition = [
        [
            'id' => 1,
            'code' => 'PurchasedFromPerson',
            'name' => 'Куплено у физ./ юр. лица',
        ],
        [
            'id' => 2,
            'code' => 'PurchasedInSalon',
            'name' => 'Куплено в салоне',
        ],
        [
            'id' => 3,
            'code' => 'InSalon',
            'name' => 'Находится в салоне у дилера',
        ],
        [
            'id' => 4,
            'code' => 'Pickup',
            'name' => 'Самоввоз',
        ],
        [
            'id' => 5,
            'code' => 'other',
            'name' => 'Другое',
        ],
    ];

    public function run()
    {
        foreach (self::$sourceAcquisition as $item) {
            SourceAcquisition::updateOrCreate(
                [
                    'id' => $item['id'],
                    'code' => $item['code']
                ],
                $item
            );
        }
    }
}
