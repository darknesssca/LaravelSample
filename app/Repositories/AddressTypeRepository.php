<?php


namespace App\Repositories;


use App\Contracts\Repositories\AddressTypeRepositoryContract;

class AddressTypeRepository implements AddressTypeRepositoryContract
{
    public function getCompanyAddressType($type, $companyCode)
    {
        $relations = $this->getAddressTypeRelations();
        return isset($relations[$type][$companyCode]) ? $relations[$type][$companyCode] : null;
    }

    public function getAddressTypeRelations()
    {
        return [
            'registration' => [
                'renessans' => 'registration',
                'ingosstrah' => 'Registered',
                'tinkoff' => 'registration',
                'soglasie' => 'Registered',
            ],
            'home' => [
                'renessans' => 'home', // not used
                'ingosstrah' => 'home', // not used
                'tinkoff' => 'home',
                'soglasie' => 'home', // not used
            ],
        ];
    }
}
