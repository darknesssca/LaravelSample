<?php


use App\Models\Gender;
use Illuminate\Database\Seeder;

class GenderSeeder extends Seeder
{
    protected static $genders = [
        [
            'id' => 1,
            'code' => 'male',
            'name' => 'Мужской',
        ],
        [
            'id' => 2,
            'code' => 'female',
            'name' => 'Женский',
        ],
    ];

    public function run()
    {
        foreach (self::$genders as $gender) {
            Gender::updateOrCreate(
                [
                    'id' => $gender['id'],
                    'code' => $gender['code']
                ],
                $gender
            );
        }
    }
}
