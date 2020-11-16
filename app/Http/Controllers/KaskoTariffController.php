<?php


namespace App\Http\Controllers;


use App\Contracts\Repositories\Services\KaskoTariffServiceContract;
use App\Http\Requests\KaskoTariffs\GetKaskoTariffListRequest;
use App\Http\Requests\KaskoTariffs\UpdateKaskoTariffRequest;
use Illuminate\Http\Response;

class KaskoTariffController extends Controller
{
    public function kaskoTariffs(GetKaskoTariffListRequest $request)
    {
        $fields = $request->validated();
        $service = app(KaskoTariffServiceContract::class);
        $data = $service->getTariffsList($fields);
        return Response::success($data);
    }

    public function getById($id)
    {
        $service = app(KaskoTariffServiceContract::class);
        $data = $service->getById($id);
        return Response::success($data);
    }

    public function update($id, UpdateKaskoTariffRequest $request)
    {
        $fields = $request->validated();
        $service = app(KaskoTariffServiceContract::class);
        $data = $service->update($id, $fields);
        return Response::success($data);
    }
}
