<?php


namespace App\Contracts\Repositories;


interface GenderRepositoryContract
{
    public function getGendersList();
    public function getCompanyGender($id, $companyId);
}
