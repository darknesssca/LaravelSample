<?php


namespace App\Traits;


use App\Exceptions\CompanyException;
use App\Exceptions\MethodNotFoundException;

trait CompanyServicesTrait
{
    protected function getCompany($code)
    {
        $company = $this->insuranceCompanyService->getCompany($code);
        if (!$company) {
            throw new CompanyException('Компания ' . $code . ' не найдена или не доступна');
        }
        return $company;
    }

    protected function getCompanyById($id)
    {
        $company = $this->insuranceCompanyService->getCompanyById($id);
        if (!$company) {
            throw new CompanyException('Компания id=' . $id . ' не найдена или не доступна');
        }
        return $company;
    }

    protected function runService($company, $attributes, $serviceMethod)
    {
        $service = $this->getCompanyService($company);
        if (!method_exists($service, $serviceMethod)) {
            throw new MethodNotFoundException('Метод не найден');
        }
        return $service->$serviceMethod($company, $attributes);
    }

    protected function getCompanyService($company)
    {
        $company = ucfirst(strtolower($company->code));
        $contract = 'App\\Contracts\\Company\\' . $company . '\\' . $company . 'MasterServiceContract';
        return app($contract);
    }
}
