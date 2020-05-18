<?php


namespace App\Traits;


use App\Exceptions\CompanyException;
use App\Exceptions\MethodNotFoundException;
use App\Exceptions\NotAvailableCommissionException;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Carbon\Carbon;

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

    public function checkCommissionAvailable($companyId, $formData) {
        $insurerId = $formData['policy']['insurantId'];
        $insurer = [];
        $needleAddress = [];

        foreach ($formData['subjects'] as $subject) {
            if ($subject['id'] == $insurerId) {
                $insurer = $subject['fields'];
            }
        }
        if (!empty($insurer) && !empty($insurer['addresses'])) {
            foreach ($insurer['addresses'] as $address) {
                if ($address['address']['addressType'] == 'registration') {
                    $needleAddress = $address['address'];
                }
            }
        }


        $params = [
            'insurance_company_id' => $companyId,
            'policy_date' => Carbon::now()->format('Y-m-d'),
            'kladr_id' => $needleAddress['regionKladr']
        ];
        $isAvailable = app(CommissionCalculationMicroserviceContract::class)->checkCommissionAvailable($params);
        if (!$isAvailable || isset($isAvailable['content']['status']) &&  $isAvailable['content']['status'] === false) {
            throw new NotAvailableCommissionException('Оформление невозможно в данном регионе');
        }
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
