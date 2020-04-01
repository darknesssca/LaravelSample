<?php


namespace App\Http\Controllers;


use App\Http\Requests\Reports\CreateReportRequest;
use App\Models\File;
use App\Models\Policy;
use App\Models\Report;
use App\Services\Qiwi\ReportService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ReportController extends Controller
{
    private $httpErrorCode = 400;
    private $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
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


//            $reward = $this->getReward($validation_result['policies'], $user_id);
//
//            if ($reward > 0) {
//                $report = Report::create([
//                    'name' => $validation_result['name'],
//                    'creator_id' => $user_id,
//                    'create_date' => Carbon::now(),
//                    'reward' => $reward
//                ]);
//
//                $report->policies()->sync($validation_result['policies']);
//
//                $policies = $this->getPoliciesForXls($validation_result['policies']);
//                $this->createXls($report->id, $policies);
//
//                $this->sendLog('Создан отчет', 'create_report', $user_id);
//                return $this->success();
//            } else {
//                throw new Exception('Отсутствует доступное вознаграждение');
//            }
        } catch (Exception $exception) {
            return Response::error($exception->getMessage(), $this->httpErrorCode);
        }

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validation_result = $this->validate(
                $request,
                ['agent_id' => 'required'],
                ['agent.required' => 'Пользователь не авторизован']
            );
            $reports = [];
            $agent_id = $this->getUserId($validation_result['agent_id']);
            $report_collection = Report::where('creator_id', $agent_id)->get();

            if (count($report_collection) > 0) {
                $creator = $this->getCreator($agent_id);
                foreach ($report_collection as $report) {
                    $reports[] = $this->getReportInfo($report, $creator);
                }
                return response()->json($reports, 200);
            } else {
                $this->httpErrorCode = 404;
                throw new Exception('Отчеты не найдены');
            }


        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), $this->httpErrorCode);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $response = $this->reportService->test();
            return $response;
//            $report_info = $this->qiwiReportService->getReportInfo($id);
//            return response()->json($report_info, 200);
        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), $this->httpErrorCode);
        }
    }


    //Вспомогательные методы

    /**
     * @param array $policies_ids
     * @param $user_id
     * @return int
     * @throws Exception
     */
    private function getReward(array $policies_ids, $user_id)
    {
        $reward_sum = 0;
        $policy_collection = Policy::whereIn('id', $policies_ids)->get();

        if (count($policy_collection) != count($policies_ids)) { //Найдены не все полисы
            throw new Exception('Отсутсвтуют некоторые полисы');
        }

        if (env("APP_DEBUG")) {
            return 1;
        }

        $url = 'api/v1/commission-calculation/rewards';
        $params = ['user_id' => $user_id];
        $response = $this->sendRequest('GET', $url, $params);

        if (empty($response['content'])){
            throw new Exception('Ошибка получения данных');
        }

        $content = json_decode($response['content'], true, 512,  JSON_OBJECT_AS_ARRAY);

        $rewards = $this->indexRewards($content);

        foreach ($policy_collection as $policy) {
            if ($policy->paid == false){
                throw new Exception(sprintf('Полис %s не оплачен', $policy->number));
            }

            $reward_sum += floatval($rewards[$policy->id]['value']);
        }

        return $reward_sum;
    }

    /**
     * @param $report
     * @return array
     * @throws Exception
     */
    private function getPolicies($report)
    {
        $policies = [];
        $policies_collection = $report->policies;

        if (count($policies_collection) <= 0) {
            throw new Exception(sprintf('У отчета %s отсутствуют полисы', $report->name));
        }

        foreach ($policies_collection as $policy) {
            $policies[] = [
                'id' => $policy->id,
                'number' => $policy->number,
            ];
        }

        return $policies;
    }

    /**
     * @param $policy_ids
     * @return array
     * @throws Exception
     */
    private function getPoliciesForXls($policy_ids)
    {
        if (env("APP_DEBUG")) {
            $policies = [
                0 => [
                    'number' => '1',
                    'product_type' => 'Осаго',
                    'dogovor_number' => 'CL123549132',
                    'bco_number' => 'ХХХ 110500609',
                    'strahovatel' => 'Белоцветов С.А.',
                    'crete_date' => '05.02.2020',
                    'premia' => '1106.92',
                    'kv_percent' => '30%',
                    'kv_rub' => '332.08',
                    'status_igs' => 'Оплачен',
                    'address' => 'Москва',
                    'prodavec_fio' => 'Иванов И.И.',
                    'prodavec_email' => 'test@test.ru',
                ],
                1 => [
                    'number' => '2',
                    'product_type' => 'Осаго',
                    'dogovor_number' => 'CL123549132',
                    'bco_number' => 'ХХХ 110500609',
                    'strahovatel' => 'Белоцветов С.А.',
                    'crete_date' => '05.02.2020',
                    'premia' => '1106.92',
                    'kv_percent' => '30%',
                    'kv_rub' => '332.08',
                    'status_igs' => 'Оплачен',
                    'address' => 'Москва',
                    'prodavec_fio' => 'Иванов И.И.',
                    'prodavec_email' => 'test@test.ru',
                ]
            ];
            return $policies;
        }

        // TODO реализовать получение информации и парсинг для xls
        $params = ['policy_ids' => $policy_ids];
        $url = 'api/v1/car-insurance/policies/';
        $response = $this->sendRequest('GET', $url, $params);

        if (empty($response['content'])) {
            throw new Exception('Ошибка получения данных');
        }

        $policies = json_decode($response['content'], true, 512, JSON_OBJECT_AS_ARRAY);

    }

    /**
     * @param int $report_id
     * @param array $policies
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    private function createXls($report_id, $policies)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Отчет');

        //Заголовки
        $sheet->setCellValue('A1', '№');
        $sheet->setCellValue('B1', 'Тип продукта');
        $sheet->setCellValue('C1', 'Номер договора');
        $sheet->setCellValue('D1', 'Номер БСО');
        $sheet->setCellValue('E1', 'Страхователь');
        $sheet->setCellValue('F1', 'Дата оформления');
        $sheet->setCellValue('G1', 'Сумма премии в рублях');
        $sheet->setCellValue('H1', 'КВ, %');
        $sheet->setCellValue('I1', 'КВ, руб.');
        $sheet->setCellValue('J1', 'Статус оплаты в ИГС');
        $sheet->setCellValue('K1', 'Адрес точки продаж');
        $sheet->setCellValue('L1', 'Продавец(ФИО)');
        $sheet->setCellValue('M1', 'Продавец(email)');

        //Автосайзинг ширины колонок
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);

        foreach ($policies as $policy){
            $n_str = intval($policy['number']) + 1;
            $sheet->setCellValue('A'. $n_str, $policy['number']);
            $sheet->setCellValue('B'. $n_str, $policy['product_type']);
            $sheet->setCellValue('C'. $n_str, $policy['dogovor_number']);
            $sheet->setCellValue('D'. $n_str, $policy['bco_number']);
            $sheet->setCellValue('E'. $n_str, $policy['strahovatel']);
            $sheet->setCellValue('F'. $n_str, $policy['crete_date']);
            $sheet->setCellValue('G'. $n_str, $policy['premia']);
            $sheet->setCellValue('H'. $n_str, $policy['kv_percent']);
            $sheet->setCellValue('I'. $n_str, $policy['kv_rub']);
            $sheet->setCellValue('J'. $n_str, $policy['status_igs']);
            $sheet->setCellValue('K'. $n_str, $policy['address']);
            $sheet->setCellValue('L'. $n_str, $policy['prodavec_fio']);
            $sheet->setCellValue('M'. $n_str, $policy['prodavec_email']);
        }


        $file_name = sprintf('report_%s.xls', $report_id);
        $file_path = '/tmp/' . $file_name;

        $writer = new Xls($spreadsheet);
        $writer->save($file_path);

        $result = Storage::cloud()->put('reports/' . $file_name, file_get_contents($file_path));
        $size = filesize($file_path);
        $content_type = mime_content_type($file_path);
        $cloud_file_path = Storage::cloud()->url('reports/' . $file_name);
        unlink($file_path);

        if ($result != '1'){
            throw new Exception('Не удалось сохранить файл');
        } else {
            $file = File::create([
                'name' => $file_name,
                'dir' => $cloud_file_path,
                'content_type' => $content_type,
                'size' => $size,
            ]);

            Report::find($report_id)->file()->associate($file->id)->save();
        }


    }

    /**
     * @param array $rewards
     * @return mixed
     */
    private function indexRewards(array $rewards)
    {
        $new_rewards = [];
        foreach ($rewards as $reward){
            $new_rewards[$rewards['policy']['id']] = $reward;
        }

        return $new_rewards;
    }
}
