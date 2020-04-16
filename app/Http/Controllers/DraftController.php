<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\Services\DraftServiceContract;
use App\Http\Requests\CreateDraftRequest;
use App\Http\Requests\FilterDraftRequest;
use App\Http\Requests\GetDraftRequest;
use Exception;
use Illuminate\Http\Response;

class DraftController extends Controller
{
    protected $draftService;

    public function __construct(DraftServiceContract $draftService)
    {
        $this->draftService = $draftService;
    }

    public function index(GetDraftRequest $request)
    {
        return Response::success($this->draftService->getDraftByAgentId());
    }

    public function show($draftId, GetDraftRequest $request)
    {
        return Response::success($this->draftService->getById($draftId));
    }

    public function filter(FilterDraftRequest $request)
    {
        try {
            $drafts = $this->draftService->getByFilter($request->validated());
            return Response::success($drafts);
        } catch (Exception $exception) {
            return Response::error($exception->getMessage(), 400);
        }
    }

    public function store(CreateDraftRequest $request)
    {
        return Response::success([
            'id' => $this->draftService->create($request->validated()),
        ], 201);
    }

    public function update($draftId, CreateDraftRequest $request)
    {
        $this->draftService->update($draftId, $request->validated());
        return Response::success(null, 200);
    }

    public function delete($draftId, GetDraftRequest $request)
    {
        $this->draftService->delete($draftId);
        return Response::success(null, 204);
    }
}
