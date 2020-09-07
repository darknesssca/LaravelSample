<?php


use App\Models\File;
use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
{
    protected static $files = [
        [
            'id' => 1,
            'name' => 'renessans.svg',
            'dir' => '/insurance-companies_logo/renessans.svg',
            'content_type' => 'image/svg+xml',
            'size' => 1619,
        ],
        [
            'id' => 2,
            'name' => 'ingosstrah.svg',
            'dir' => '/insurance-companies_logo/ingosstrah.svg',
            'content_type' => 'image/svg+xml',
            'size' => 358,
        ],
        [
            'id' => 3,
            'name' => 'soglasie.svg',
            'dir' => '/insurance-companies_logo/soglasie.svg',
            'content_type' => 'image/svg+xml',
            'size' => 1348,
        ],
        [
            'id' => 4,
            'name' => 'tinkoff.svg',
            'dir' => '/insurance-companies_logo/tinkoff.svg',
            'content_type' => 'image/svg+xml',
            'size' => 3728,
        ],
        [
            'id' => 5,
            'name' => 'vsk.svg',
            'dir' => '/insurance-companies_logo/vsk.svg',
            'content_type' => 'image/svg+xml',
            'size' => 3728,
        ],
    ];

    protected static $insuranceCompanies = [
        [
            'id' => 1,
            'active' => true,
            'logo_id' => 1,
            'code' => 'renessans',
            'name' => 'Ренессанс',
        ],
        [
            'id' => 2,
            'active' => true,
            'logo_id' => 2,
            'code' => 'ingosstrah',
            'name' => 'Ингосстрах',
        ],
        [
            'id' => 3,
            'active' => true,
            'logo_id' => 3,
            'code' => 'soglasie',
            'name' => 'Согласие',
        ],
        [
            'id' => 4,
            'active' => true,
            'logo_id' => 4,
            'code' => 'tinkoff',
            'name' => 'Тинькофф',
        ],
        [
            'id' => 5,
            'active' => true,
            'logo_id' => 5,
            'code' => 'vsk',
            'name' => 'ВСК',
        ],
    ];

    public function run()
    {
        foreach (self::$files as &$file) {
            $file['dir'] = config('filesystems.disks.minio.bucket') . $file['dir'];
            File::updateOrCreate(
                [
                    'id' => $file['id']
                ],
                $file
            );
        }
        foreach (self::$insuranceCompanies as $insuranceCompany) {
            InsuranceCompany::updateOrCreate(
                [
                    'id' => $insuranceCompany['id'],
                    'code' => $insuranceCompany['code']
                ],
                $insuranceCompany
            );
        }
    }
}
