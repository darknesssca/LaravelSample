<?php

namespace App\Http\Controllers;

use App\Models\CarCategory;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\Country;
use App\Models\DocType;
use App\Models\Gender;
use App\Models\InsuranceCompany;
use App\Models\SourceAcquisition;
use App\Models\UsageTarget;
use Illuminate\Http\Response;

class GuidesController extends Controller
{
    public function marks()
    {
        $data = CarMark::select(["id", "code", "name"])->get()->jsonSerialize();
        return Response::success($data);
    }

    public function models($mark_id)
    {
        $data = CarModel::select(["id", "code", "name", "category_id", "mark_id"])->where("mark_id", $mark_id)->get()->jsonSerialize();
        return Response::success($data);
    }

    public function modelsAll()
    {
        $data = CarModel::select(["id", "code", "name", "category_id", "mark_id"])->get()->jsonSerialize();
        return Response::success($data);
    }

    public function categories()
    {
        $data = CarCategory::select(["id", "code", "name"])->get()->jsonSerialize();
        return Response::success($data);
    }

    public function countriesAll()
    {
        $data = Country::select(["id", "code", "name", "short_name", "alpha2", "alpha3"])->get()->jsonSerialize();
        return Response::success($data);
    }

    public function countries($country_id)
    {
        $data = Country::select(["id", "code", "name", "short_name", "alpha2", "alpha3"])->where("id", $country_id)->get()->jsonSerialize();
        return Response::success($data);
    }

    public function genders()
    {
        $data = Gender::select(["id", "code", "name"])->get()->jsonSerialize();
        return Response::success($data);
    }

    public function docTypes()
    {
        $data = DocType::select(["id", "code", "name"])->get()->jsonSerialize();
        return Response::success($data);
    }


    public function usageTargets()
    {
        $data = UsageTarget::select(["id", "code", "name"])->get()->jsonSerialize();
        return Response::success($data);
    }

    public function insuranceCompanies()
    {
        $data = InsuranceCompany::select(["id", "code", "name"])->where("active", true)->get()->jsonSerialize();
        return Response::success($data);
    }

    public function sourceAcquisitions()
    {
        $data = SourceAcquisition::select(["id", "code", "name"])->get()->jsonSerialize();
        return Response::success($data);
    }

}
