<?php


namespace App\Contracts\Repositories;


interface CarMarkRepositoryContract
{
    public function getMarkList();
    public function getCompanyMark($id, $companyId);
    public function getCarMarkById($id);
}
