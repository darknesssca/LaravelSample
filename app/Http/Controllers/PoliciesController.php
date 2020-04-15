<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PolicyServiceContract;
use App\Http\Requests\Policies\PolicyListRequest;
use App\Http\Requests\Policies\PolicyStatisticRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PoliciesController extends Controller
{
    public function list(PolicyListRequest $request)
    {
        return Response::success(app(PolicyServiceContract::class)->getList(
            $request->only([
                'agent_ids',
                'client_ids',
                'company_ids',
                'paid',
                'from',
                'to'
            ]),
            $request->get('sort') ?? 'id',
            $request->get('order') ?? 'asc',
            $request->get('page') ?? 1,
            $request->get('per_page') ?? 20,
            $request->get('search')
        ));
    }

    public function statistic(PolicyStatisticRequest $request)
    {
        return Response::success(app(PolicyServiceContract::class)->statistic($request->validated()));
    }
}
