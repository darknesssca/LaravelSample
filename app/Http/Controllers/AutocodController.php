<?php

namespace App\Http\Controllers;

use App\Services\CarInfo\Autocod\AutocodReportService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $params = $this->validate($request, ['vin' => 'required'], ['vin.required' => 'не задано поле vin']); //todo добавить регулярку на вин
            $result = $this->engine->readReportAutocompleteSync($params['vin']); //ожидаем генерации отчета
            return response()->json(['error' => false, 'content' => $result]);
        } catch (ValidationException $exception) {
            return $this->error($exception->errors(), 400);
        } catch (ClientException $cle) {
            return response()->json(['error' => true, 'errors' => ['message' => $cle->getMessage()]], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'errors' => ['message' => $e->getMessage()]], 500);
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
                return response()->json(['error' => true, 'errors' => ['message' => 'Отчет не найден']], 404);
            }
            return response()->json(['error' => false, 'content' => $result]);
        } catch (ClientException $cle) {
            return response()->json(['error' => true, 'errors' => $cle->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'errors' => ['message' => $e->getMessage()]], 500);
        }
    }

    /**проверка регистраци машины в такси
     * @param Request $request
     * @return JsonResponse
     */
    public function checkTaxi(Request $request)
    {
        try {
            $params = $this->validate($request, ['vin' => 'required'], ['vin.required' => 'не задано поле vin']); //todo добавить регулярку на вин
            $result = $this->engine->checkTaxi($params['vin']);
            return response()->json(['error' => false, 'content' => $result]);
        } catch (ValidationException $exception) {
            return $this->error($exception->errors(), 400);
        } catch (ClientException $cle) {
            return response()->json(['error' => true, 'errors' => ['message' => $cle->getMessage()]], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'errors' => ['message' => $e->getMessage()]], 500);
        }
    }

}
