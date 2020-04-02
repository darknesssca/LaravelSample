<?php


namespace App\Contracts\Repositories\Services;


interface AddressTypeServiceContract
{
    public function getCompanyAddressType($type, $companyCode);
}
