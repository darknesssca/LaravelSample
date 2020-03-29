<?php


namespace App\Contracts\Company\Renessans;


use App\Contracts\Company\CompanyMasterServiceInterface;

interface RenessansMasterServiceContract extends CompanyMasterServiceInterface
{
    public function calculating($company, $attributes):array;
    public function processing($company, $attributes):array;
}
