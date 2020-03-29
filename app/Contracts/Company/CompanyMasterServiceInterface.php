<?php

namespace App\Contracts\Company;


use App\Models\InsuranceCompany;

interface CompanyMasterServiceInterface
{
    /**
     * Метод рассчитывает премию за страховку, либо отправляет запрос в шину СК (в зависимости от того, как работает СК)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     */
    public function calculate(InsuranceCompany $company, $attributes):array;

    /**
     * Метод создает полис в СК либо отправляет создание полиса в шину СК (в зависимости от того, как работает СК)
     *
     * @param InsuranceCompany $company
     * @param $attributes
     * @return array
     */
    public function create(InsuranceCompany $company, $attributes):array;
}
