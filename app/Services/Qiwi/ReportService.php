<?php

//TODO Переписать методы получения наград и полисов после реализации api полисов
//TODO Переписать xls после реализации полисов
//TODO Сделать получение пользователей из кэша

namespace App\Services\Qiwi;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\ReportRepositoryContract;
use App\Contracts\Services\ReportServiceContract;
use App\Exceptions\TaxStatusNotServiceException;
use App\Models\File;
use App\Models\Report;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


class ReportService implements ReportServiceContract
{
    private $reportRepository;
    private $policyRepository;
    private $qiwi;

    /** @var AuthMicroserviceContract $auth_mks */
    private $auth_mks;
    /** @var NotifyMicroserviceContract $notify_mks */
    private $notify_mks;
    /** @var LogMicroserviceContract $log_mks */
    private $log_mks;
    /** @var CommissionCalculationMicroserviceContract $commission_mks */
    private $commission_mks;


    public function __construct(
        ReportRepositoryContract $reportRepository,
        PolicyRepositoryContract $policyRepository
    ) {
        $this->reportRepository = $reportRepository;
        $this->policyRepository = $policyRepository;

        $this->auth_mks = app(AuthMicroserviceContract::class);
        $this->notify_mks = app(NotifyMicroserviceContract::class);
        $this->log_mks = app(LogMicroserviceContract::class);
        $this->commission_mks = app(CommissionCalculationMicroserviceContract::class);
    }

    public function reportRepository(): ReportRepositoryContract
    {
        return $this->reportRepository;
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function getReportInfo($id): array
    {
        $report = $this->reportRepository->getById($id);
        $report_info = $report->toArray();
        $report_info['creator'] = $this->getCreator($report->creator_id);

        return $report_info;
    }

    public function getReportsInfo($fields)
    {
        if (GlobalStorage::userIsAdmin()) {
            $reports = $this->reportRepository->getAll($fields);
        } else {
            $reports = $this->reportRepository->getByCreatorId(GlobalStorage::getUserId(), $fields);
        }

        return $reports;
    }

    /**
     * @param array $fields
     * @return mixed
     * @throws TaxStatusNotServiceException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createReport(array $fields)
    {
        $fields['creator_id'] = GlobalStorage::getUserId();
        $fields['reward'] = $this->getReward($fields['policies'], $fields['creator_id']);

        $creator = $this->getCreator($fields['creator_id']);

        $report = $this->reportRepository->create($fields);
        $report->policies()->sync($fields['policies']);
        $report->save();

        $this->createXls($report->id, $this->getPoliciesForXls());

        $this->qiwi = new Qiwi($creator['requisites'], $creator['tax_status']['code']);
        $this->qiwi->makePayout($fields['reward']);

        $message = "Создан отчет на выплату {$report->id}";
        $this->log_mks->sendLog($message, config('api_sk.logMicroserviceCode'), $fields['creator_id']);
        return Response::success('Отчет успешно создан');
    }

    /**
     * @param $user_id
     * @return array
     * @throws Exception
     */
    private function getCreator($user_id)
    {
        return $this->auth_mks->userInfo($user_id);
    }

    /**
     * @param array $policies_ids
     * @param $user_id
     * @return float|int
     * @throws Exception
     */
    private function getReward(array $policies_ids, $user_id)
    {
        $reward_sum = 0;
        $policy_collection = $this->policyRepository->getList(['policy_ids' => $policies_ids]);

        if (count($policy_collection) != count($policies_ids)) { //Найдены не все полисы
            throw new Exception('Отсутсвтуют некоторые полисы');
        }

        $filter = [

        ];

        $rewards = $this->commission_mks->getRewards($filter);

        if (empty($rewards)) {
            throw new Exception('Ошибка получения доступных наград');
        }

//        foreach ($policy_collection as $policy) {
//            if ($policy->paid == false) {
//                throw new Exception(sprintf('Полис %s не оплачен', $policy->number));
//            }
//
//            $reward_sum += floatval($rewards[$policy->id]['value']);
//        }
//
//        return $reward_sum;
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

        foreach ($policies as $key => $policy) {
            $n_str = intval($key) + 1;
            $sheet->setCellValue('A' . $n_str, $policy['number']);
            $sheet->setCellValue('B' . $n_str, $policy['product_type']);
            $sheet->setCellValue('C' . $n_str, $policy['dogovor_number']);
            $sheet->setCellValue('D' . $n_str, $policy['bco_number']);
            $sheet->setCellValue('E' . $n_str, $policy['strahovatel']);
            $sheet->setCellValue('F' . $n_str, $policy['crete_date']);
            $sheet->setCellValue('G' . $n_str, $policy['premia']);
            $sheet->setCellValue('H' . $n_str, $policy['kv_percent']);
            $sheet->setCellValue('I' . $n_str, $policy['kv_rub']);
            $sheet->setCellValue('J' . $n_str, $policy['status_igs']);
            $sheet->setCellValue('K' . $n_str, $policy['address']);
            $sheet->setCellValue('L' . $n_str, $policy['prodavec_fio']);
            $sheet->setCellValue('M' . $n_str, $policy['prodavec_email']);
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

        if ($result != '1') {
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
     * @return array
     */
    private function getPoliciesForXls()
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
    }
}
