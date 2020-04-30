<?php


namespace App\Contracts\Repositories;


interface InsuranceCompanyRepositoryContract
{
    /**
     * Выполняет поиск компании по ее символьному коду
     * Ищет только среди активных компаний
     *
     * @param $code - код компании
     * @return mixed
     */
    public function getCompany($code);

    public function getCompanyById($id);

    public function getInsuranceCompanyList($checkActive);

    public function getById(int $id);
}
