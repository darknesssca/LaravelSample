<?php


use App\Models\UsageTarget;
use Illuminate\Database\Seeder;

class UsageTargetSeeder extends Seeder
{

    protected static $usageTargets = [
        [
            'id' => 1,
            'code' => 'personal',
            'name' => 'Личная',
        ],
        [
            'id' => 2,
            'code' => 'taxi',
            'name' => 'Такси',
        ],
        [
            'id' => 3,
            'code' => 'rent',
            'name' => 'Сдача в аренду',
        ],
        [
            'id' => 4,
            'code' => 'training',
            'name' => 'Учебная езда',
        ],
        [
            'id' => 5,
            'code' => 'dangerous',
            'name' => 'Перевозка опасных и легковоспламеняющихся грузов',
        ],
        [
            'id' => 6,
            'code' => 'passenger',
            'name' => 'Пассажирские перевозки',
        ],
        [
            'id' => 7,
            'code' => 'emergency',
            'name' => 'Экстренные и коммунальные службы',
        ],
        [
            'id' => 8,
            'code' => 'road',
            'name' => 'Дорожные и специальные ТС',
        ],
        [
            'id' => 9,
            'code' => 'collection',
            'name' => 'Инкассация',
        ],
        [
            'id' => 10,
            'code' => 'ambulance',
            'name' => 'Скорая помощь',
        ],
        [
            'id' => 11,
            'code' => 'other',
            'name' => 'Прочее',
        ],
    ];

    public function run()
    {
        foreach (self::$usageTargets as $usageTarget) {
            UsageTarget::updateOrCreate(
                [
                    'id' => $usageTarget['id'],
                    'code' => $usageTarget['code']
                ],
                $usageTarget
            );
        }

    }
}
