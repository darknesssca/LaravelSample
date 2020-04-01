<?php


namespace App\Contracts\Repositories\Services;


interface CarMarkServiceContract
{
    public function getMarkList();
    public function getCompanyMark($id, $companyId);
}
