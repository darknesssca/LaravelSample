<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PolicyServiceContract;
use App\Http\Requests\Policies\PolicyListRequest;
use App\Http\Requests\Policies\PolicyStatisticRequest;
use App\Http\Requests\Policies\PolicyWithRewardsRequest;
use App\Services\PolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PoliciesController extends Controller
{
    /** @var PolicyService $policyService */
    private $policyService = null;

    public function __construct()
    {
        $this->policyService = app(PolicyServiceContract::class);
    }

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

    /**возвращает список полисов и вознаграждений
     * @param PolicyWithRewardsRequest $request
     * @return JsonResponse
     */
    public function listWithRewards(PolicyWithRewardsRequest $request)
    {
        try {
            return Response::success($this->policyService->listWithRewards($request->validated()));
        } catch (\Exception $exception) {
            return Response::error($exception->getMessage(), 500);
        }
    }

    public function statistic(PolicyStatisticRequest $request)
    {
        return Response::success(app(PolicyServiceContract::class)->statistic($request->validated()));
    }
}
