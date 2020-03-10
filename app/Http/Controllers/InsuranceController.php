<?php

namespace App\Http\Controllers;

use App\Contracts\Company\CompanyServiceContract;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsuranceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index($code, $method, Request $request)
    {
        $company = $this->checkCompany($code);
        if (!$company->count()) {
            return $this->error('Компания не найдена', 404);
        }
        $method = strtolower((string)$method);
        if (!method_exists($this, $method)) {
            return $this->error('Метод не найден', 404);
        }
        try
        {
            return $this->$method($company, $request);
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        }
        catch (BindingResolutionException $exception)
        {
            return $this->error('Не найден обработчик компании: '.$exception->getMessage(), 404);
        }
        catch (\Exception $exception)
        {
            return $this->error($exception->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try
        {
            $controller = app(CompanyServiceContract::class);
            $attributes = $this->validate(
                $request,
                $controller->validationRulesForm(),
                $controller->validationMessagesForm()
            );
            $data = [
                'form' => $attributes,
            ];
            $token = IntermediateData::createToken($data);
            return ['token' => $token];
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        }
        catch (BindingResolutionException $exception)
        {
            return $this->error('Не найден обработчик компании: '.$exception->getMessage(), 404);
        }
        catch (\Exception $exception)
        {
            return $this->error($exception->getMessage(), 500);
        }
    }

    private function calculate($company, $request)
    {
        $methodData = [
            'policesId' => [],
        ];
        $calculateData = $this->runService($company, $request, 'Calculate');
        $additionalData = [
            'isCheckSegment' => true,
            'calculationId' => [],
        ];
        foreach ($calculateData as $data) {
            $additionalData['calculationId'][] = $data['calculationId'];
        }
        $createData = $this->runService($company, $request, 'Create', $additionalData);
        foreach ($createData as $calculationId => $data) {
            $methodData['policesId'][] = [
                'calculationId' => $calculationId,
                'policeId' => $data['policyId'],
            ];
        }
        $hash = $this->saveIntermediateData($methodData);
        return ['hash' => $hash];
    }

//    private function saveIntermediateData($data, $try = 0)
//    {
//        $token = Str::random(32);
//        try {
//            IntermediateData::create([
//                'token' => $token,
//                'data' => \GuzzleHttp\json_encode($data)
//            ]);
//            return $token;
//        } catch (\Exception $exception) {
//            $try += 1;
//            if ($try) {
//                throw new \Exception('fail create token: '.$exception->getMessage());
//            }
//            return $this->saveIntermediateData($data, $try);
//        }
//    }

    private function runService($company, $request, $serviceMethod, $additionalData = [])
    {
        $controller = $this->getCompanyController($company, $serviceMethod);
        $validatedFields = $this->validate(
            $request,
            $controller->validationRulesProcess(),
            $controller->validationMessagesProcess()
        );
        $attributes = IntermediateData::getData($validatedFields['token']);
        $attributes['token'] = $validatedFields['token'];
        return $this->useCompanyController($controller, $company, $attributes, $additionalData);
    }

    public function checkCompany($code)
    {
        return InsuranceCompany::getCompany($code);
    }

    protected function error($messages, $httpCode = 500)
    {
        $message = [
            'error' => true,
            'errors' => $messages,
        ];
        return response()->json($message, $httpCode);
    }


    protected function getCompanyController($company, $method)
    {
        $company = ucfirst(strtolower($company->code));
        $method = ucfirst(strtolower($method));
        $contract = 'App\\Contracts\\Company\\'.$company.'\\'.$company.$method.'ServiceContract';
        return app($contract);
    }

    protected function useCompanyController($controller, $company, $attributes, $additionalData)
    {
        return $controller->run($company, $attributes, $additionalData);
    }
}
