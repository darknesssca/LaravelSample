<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PolicyServiceContract;
use App\Http\Requests\Policies\PolicyStatisticRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PoliciesController extends Controller
{
    public function list(Request $request)
    {
        return response()->json(app(PolicyServiceContract::class)->getList($request->all()));
    }

    public function statistic(PolicyStatisticRequest $request)
    {
        return Response::success(app(PolicyServiceContract::class)->statistic($request->validated()));
    }
}
