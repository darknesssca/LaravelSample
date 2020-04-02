<?php

//TODO Переписать методы получения наград и полисов после реализации api полисов
//TODO Переписать xls после реализации полисов

namespace App\Services\Qiwi;


use App\Contracts\Repositories\ReportRepositoryContract;
use App\Contracts\Repositories\ReportsRepositoryContract;
use App\Models\File;
use App\Models\Policy;
use App\Models\Report;
use App\Repositories\ReportRepository;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


class ReportService
{
    private $reportRepository;
    private $qiwi;

    /** @var AuthMicroserviceContract $auth_mks  */
    private $auth_mks;
    /** @var NotifyMicroserviceContract $notify_mks  */
    private $notify_mks;
    /** @var LogMicroserviceContract $log_mks  */
    private $log_mks;


    public function __construct(ReportRepositoryContract $reportRepository)
    {
        $this->reportRepository = $reportRepository;
        $this->qiwi = new Qiwi();

        $this->auth_mks = app(AuthMicroserviceContract::class);
        $this->notify_mks = app(NotifyMicroserviceContract::class);
        $this->log_mks = app(LogMicroserviceContract::class);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function getReportInfo($id)
    {   $report = $this->reportRepository->getById($id);
        $report_info = $report->toArray();
        $report_info['creator'] = $this->getCreator($report->creator_id);

        return $report_info;
    }

    public function createReport($fields)
    {
        $report = new Report();
        $fields['reward'] = $this->getReward($fields['policies'], GlobalStorage::getUserId());
        $report->fill($fields)->save();
        $report->policies()->sync($fields['policies']);

        $message = "Создан отчет на выплату {$report->id}";
        $this->log_mks->sendLog($message, config('api_sk.logMicroserviceCode'), GlobalStorage::getUserId());

        return Response::success('Отчет успешно создан');
    }



    //TODO Удалить как закончу с qiwi
    public function test(){
        $response = $this->qiwi->createPayment(1, 1111111111111111);
        return $response;
    }

    /**
     * @param $user_id
     * @return array
     * @throws Exception
     */
    private function getCreator($user_id)
    {
        $user_info = $this->auth_mks->userInfo($user_id);

        return [
            'id' => $user_info['id'],
            'full_name' => $user_info['full_name']
        ];
    }

    private function getReward(array $policies_ids, $user_id)
    {
        $reward_sum = 0;
        $policy_collection = Policy::whereIn('id', $policies_ids)->get();

        if (count($policy_collection) != count($policies_ids)) { //Найдены не все полисы
            throw new Exception('Отсутсвтуют некоторые полисы');
        }

        if (env('APP_DEBUG') == true){
            return 1000;
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
}
