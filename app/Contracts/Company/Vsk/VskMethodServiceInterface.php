<?php


namespace App\Contracts\Company\Vsk;


use App\Contracts\Company\CompanyMethodServiceInterface;
use App\Models\InsuranceCompany;

interface VskMethodServiceInterface extends CompanyMethodServiceInterface
{
    /**
     * Метод обработки колбеков от ВСК
     * Для каждого сервиса свой метод обработки колбека
     *
     * @param InsuranceCompany $company - объект компании
     * @param array $token_data - информация о токене (метод и сам токен)
     * @param array $parsed_response - ответ в виде массива
     * @return array
     */
    public function processCallback(InsuranceCompany $company, array $token_data, array $parsed_response): array;
}
