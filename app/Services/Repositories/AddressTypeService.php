<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\AddressTypeRepositoryContract;
use App\Contracts\Repositories\Services\AddressTypeServiceContract;
use App\Exceptions\GuidesNotFoundException;

class AddressTypeService implements AddressTypeServiceContract
{

    protected $addressTypeRepository;

    public function __construct(
        AddressTypeRepositoryContract $addressTypeRepository
    )
    {
        $this->addressTypeRepository = $addressTypeRepository;
    }

    public function getCompanyAddressType($type, $companyCode)
    {
        $code = $this->addressTypeRepository->getCompanyAddressType($type, $companyCode);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $code;
    }
}
