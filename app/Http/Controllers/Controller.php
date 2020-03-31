<?php

namespace App\Http\Controllers;

use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Services\AuthMicroservice;
use Benfin\Api\Traits\HttpRequest;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use HttpRequest;
}
