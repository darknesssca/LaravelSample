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
    /** @var AutocodReportService $engine */
    private $engine;

    public function __construct()
    {
        $this->engine = new AutocodReportService();
    }

    /**Получение отчета с ожиданием
     * @param AutocodRequestReportRequest $request
     * @return JsonResponse
     */
    public function requestReport(AutocodRequestReportRequest $request)
    {
        try {
            $params = $request->validated();
            $result = $this->engine->readReportAutocompleteSync($params['vin'], $params['needSave'] ?? false); //ожидаем генерации отчета
            return Response::success($result);
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
     * @param AutocodRequestReportRequest $request
     * @return JsonResponse
     */
    public function checkTaxi(AutocodRequestReportRequest $request)
    {
        try {
            $params = $request->validated();
            $result = $this->engine->checkTaxi($params['vin']);
            return Response::success($result);
        } catch (ClientException $cle) {
            return Response::error($cle->getMessage(), 500);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

}
