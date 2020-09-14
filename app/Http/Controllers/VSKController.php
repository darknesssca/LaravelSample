<?php


namespace App\Http\Controllers;


use App\Contracts\Company\Vsk\VskCallbackServiceContract;
use App\Http\Requests\VSK\CallbackRequest;
use Illuminate\Http\Response;

class VSKController extends Controller
{
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
}
