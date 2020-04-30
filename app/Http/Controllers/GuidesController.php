<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\SourceAcquisitionServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Http\Requests\GuidesInsuranceCompaniesRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GuidesController extends Controller
{
    public function marks()
    {
        $service = app(CarMarkServiceContract::class);
        $data = $service->getMarkList();
        return Response::success($data);
    }

    public function models($mark_id)
    {
        $service = app(CarModelServiceContract::class);
        $data = $service->getModelListByMarkId($mark_id);
        return Response::success($data);
    }

    public function modelsAll()
    {
        $service = app(CarModelServiceContract::class);
        $data = $service->getModelList();
        return Response::success($data);
    }

    public function categories()
    {
        $service = app(CarCategoryServiceContract::class);
        $data = $service->getCategoryList();
        return Response::success($data);
    }

    public function countriesAll()
    {
        $service = app(CountryServiceContract::class);
        $data = $service->getCountryList();
        return Response::success($data);
    }

    public function countries($country_id)
    {
        $service = app(CountryServiceContract::class);
        $data = $service->getCountryById($country_id);
        return Response::success($data);
    }

    public function genders()
    {
        $service = app(GenderServiceContract::class);
        $data = $service->getGendersList();
        return Response::success($data);
    }

    public function docTypes()
    {
        $service = app(DocTypeServiceContract::class);
        $data = $service->getDocTypesList();
        return Response::success($data);
    }

    public function usageTargets()
    {
        $service = app(UsageTargetServiceContract::class);
        $data = $service->getUsageTargetList();
        return Response::success($data);
    }

    public function insuranceCompanies(GuidesInsuranceCompaniesRequest $request)
    {
        $params = $request->validated();
        $service = app(InsuranceCompanyServiceContract::class);
        $data = $service->getInsuranceCompanyList(boolval($params['checkActive']??true));
        return Response::success($data);
    }

    public function insuranceCompany($id)
    {
        /** @var InsuranceCompanyServiceContract $service */
        $service = app(InsuranceCompanyServiceContract::class);
        $data = $service->getCompanyById($id);
        return Response::success($data);
    }

    public function sourceAcquisitions()
    {
        $service = app(SourceAcquisitionServiceContract::class);
        $data = $service->getSourceAcquisitionsList();
        return Response::success($data);
    }

}
