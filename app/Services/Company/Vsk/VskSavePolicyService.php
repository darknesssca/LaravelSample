<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskSavePolicyServiceContract;
use App\Models\InsuranceCompany;

class VskSavePolicyService extends VskService implements VskSavePolicyServiceContract
{

    /**
     * Метод подготавливает данные и отправляет их в СК
     * Каждый метод выполняет один конкретный запрос
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     */
    public function run(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement run() method.
    }
}
