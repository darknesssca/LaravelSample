<?php


namespace App\Contracts\Repositories;


interface AddressTypeRepositoryContract
{
    public function getCompanyAddressType($type, $companyCode);
}
