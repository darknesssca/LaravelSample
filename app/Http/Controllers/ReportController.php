<?php


namespace App\Http\Controllers;

use App\Contracts\Services\ReportServiceContract;
use App\Contracts\Utils\DeferredResultContract;
use App\Exceptions\Qiwi\ResolutionException;
use App\Http\Requests\DeferredResultRequest;
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
    public function processingStatus()
    {
        try {
            $status = $this->reportService->getProcessingStatus();
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
            return Response::success($this->reportService->createReport($fields));
        } catch (Exception $exception) {
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }

    public function status(DeferredResultRequest $request)
    {
        $deferredResultId = $request->get('id');
        $deferredResultUtil = app(DeferredResultContract::class);
        $result = $deferredResultUtil->get($deferredResultId);
        if (!$result) {
            return $deferredResultUtil->getInitialResponse($deferredResultId, $deferredResultUtil->getErrorStatus());
        }
        return $result;
    }

    public function rerunPayout(int $id)
    {
        try {
            return Response::success($this->reportService->createReport($this->reportService->rerunPayout($id)));
        } catch (Exception $exception) {
            $httpCode = ($exception instanceof AbstractException) ? $exception->getHttpCode() : 400;
            return Response::error($exception->getMessage(), $httpCode);
        }
    }
}
