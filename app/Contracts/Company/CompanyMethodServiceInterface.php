<?php

namespace App\Contracts\Company;

use App\Models\InsuranceCompany;

interface CompanyMethodServiceInterface
{
    /**
     * Метод подготавливает данные и отправляет их в СК
     * Каждый метод выполняет один конкретный запрос
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     */
    public function run(InsuranceCompany $company, $attributes):array;
}
