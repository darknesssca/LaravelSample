<?php


namespace App\Http\Controllers;


use App\Contracts\Company\Vsk\VskCallbackServiceContract;
use App\Http\Requests\VSK\CallbackRequest;
use App\Http\Requests\VSK\SignRequest;
use App\Traits\CompanyServicesTrait;
use Illuminate\Http\Response;

class VSKController extends Controller
{
    use CompanyServicesTrait;

    private $callbackService;

    public function __construct(VskCallbackServiceContract $callbackService)
    {
        $this->callbackService = $callbackService;
    }

    public function callback(CallbackRequest $request)
    {
        $fields = $request->validated();
        $this->callbackService->runNextStep($fields);
        Response::success(true);
    }

    public function sign(SignRequest $request)
    {
        $fields = $request->validate();
        $company = $this->getCompany('vsk');
        $this->runService($company, $fields, 'creating');
        return Response::success(['status' => 'processing']);
    }
}
