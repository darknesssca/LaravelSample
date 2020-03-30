<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutocodRequestReportRequest;
use App\Services\CarInfo\Autocod\AutocodReportService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AutocodController extends Controller
{
    private $engine;

    public function __construct()
    {
        $this->engine = new AutocodReportService();
    }

    /**Получение отчета с ожиданием
     * @param Request $request
     * @return JsonResponse
     */
    public function requestReport(Request $request)
    {
        try {
            $params = $this->validate($request, AutocodRequestReportRequest::getRules(), AutocodRequestReportRequest::getMessages());
            $result = $this->engine->readReportAutocompleteSync($params['vin']); //ожидаем генерации отчета
            return Response::success($result);
        } catch (ValidationException $exception) {
            return $this->error($exception->errors(), 400);
        } catch (ClientException $cle) {
            return Response::error($cle->getMessage(), 500);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**чтение уже готового отчета
     * @param $report_id
     * @return JsonResponse
     */
    public function readReport($report_id)
    {
        try {
            $result = $this->engine->readReport($report_id);
            if ($result['size'] == 0) {
                return Response::error('Отчет не найден', 404);
            }
            return Response::success($result);
        } catch (ClientException $cle) {
            return Response::error($cle->getMessage(), 500);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**проверка регистраци машины в такси
     * @param Request $request
     * @return JsonResponse
     */
    public function checkTaxi(Request $request)
    {
        try {
            $params = $this->validate($request, AutocodRequestReportRequest::getRules(), AutocodRequestReportRequest::getMessages());
            $result = $this->engine->checkTaxi($params['vin']);
            return Response::success($result);
        } catch (ValidationException $exception) {
            return $this->error($exception->errors(), 400);
        } catch (ClientException $cle) {
            return Response::error($cle->getMessage(), 500);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

}
