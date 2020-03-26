<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PolicyServiceContract;
use Illuminate\Http\Request;

class PoliciesController extends Controller
{
    public function list(Request $request)
    {
        return response()->json(app(PolicyServiceContract::class)->getList());
    }

    public function getById($id)
    {
        return response()->json(app(PolicyServiceContract::class)->getById($id));
    }

    public function create()
    {
        app(PolicyServiceContract::class)->create();
    }
}
