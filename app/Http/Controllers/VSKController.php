<?php


namespace App\Http\Controllers;


use App\Contracts\Company\Vsk\VskCallbackServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Http\Requests\VSK\CallbackRequest;
use App\Http\Requests\VSK\ResendCodeRequest;
use App\Http\Requests\VSK\SignRequest;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;
use Illuminate\Http\Response;

class VSKController extends Controller
{
    use CompanyServicesTrait;

    private $callbackService;
    private $insuranceCompanyService;

    public function __construct(
        VskCallbackServiceContract $callbackService,
        InsuranceCompanyServiceContract $insuranceCompanyService
    ) {
        $this->callbackService = $callbackService;
        $this->insuranceCompanyService = $insuranceCompanyService;
    }

    public function callback(CallbackRequest $request)
    {
        $fields = $request->validated();
        $this->callbackService->runNextStep($fields);
        Response::success(true);
    }

    public function sign(SignRequest $request)
    {
        $fields = $request->validated();
        $company = $this->getCompany('vsk');
        $this->runService($company, $fields, 'creating');
        return Response::success(['status' => 'processing']);
    }

    public function resendCode(ResendCodeRequest $request)
    {
        $fields = $request->validated();
        $fields['nextMethod'] = 'create';
        $company = $this->getCompany('vsk');
        $this->runService($company, $fields, 'calculate');
        return Response::success(['status' => 'processing']);
    }
}
