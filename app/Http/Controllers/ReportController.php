<?php


namespace App\Http\Controllers;

use App\Contracts\Services\ReportServiceContract;
use App\Http\Requests\Reports\CreateReportRequest;
use App\Http\Requests\Reports\GetListReportsRequest;
use App\Services\Qiwi\ReportService;
use Benfin\Requests\Exceptions\AbstractException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /** @var ReportService $reportService  */
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
     * @param GetListReportsRequest $request
     * @return JsonResponse
     */
    public function index(GetListReportsRequest $request)
    {
        try {
            $fields = $request->validated();
            $reports = $this->reportService->getReportsInfo($fields);
            return Response::success($reports);
        } catch (Exception $exception) {
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }

    /**
     * @return JsonResponse
     */
    public function status()
    {
        try {
            $status = $this->reportService->getStatus();
            return Response::success($status);
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
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }

    public function rerunPayout(int $id)
    {
        try {
            return $this->reportService->rerunPayout($id);
        } catch (Exception $exception) {
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }
}
