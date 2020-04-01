<?php


namespace App\Contracts\Repositories\Services;


interface GenderServiceContract
{
    public function getGendersList();
    public function getCompanyGender($id, $companyId);
}
