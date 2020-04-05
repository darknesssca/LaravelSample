<?php


namespace App\Http\Controllers;

use App\Contracts\Services\ReportServiceContract;
use App\Http\Requests\Reports\CreateReportRequest;
use App\Models\File;
use App\Models\Policy;
use App\Models\Report;
use Benfin\Requests\Exceptions\AbstractException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ReportController extends Controller
{
    private $httpErrorCode = 400;
    private $reportService;

    public function __construct(ReportServiceContract $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $report = $this->reportService->getReportInfo($id);
            return Response::success($report);
        } catch (Exception $exception) {
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        try {
            $reports = $this->reportService->getReportsInfo();
            return Response::success($reports);
        } catch (Exception $exception) {
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }


    /**
     * @param CreateReportRequest $request
     * @return JsonResponse
     */
    public function create(CreateReportRequest $request)
    {
        try {
            $fields = $request->validated();
            return $this->reportService->createReport($fields);
        } catch (Exception $exception) {
            return Response::error($exception->getMessage(), $this->httpErrorCode);
        }
    }
}
