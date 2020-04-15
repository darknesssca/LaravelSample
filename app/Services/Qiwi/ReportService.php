<?php

//TODO Сделать логику с киви чтобы можно было перезапустить запрос на выплату
//TODO Сделать получение пользователей из кэша

namespace App\Services\Qiwi;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
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
    private $insuranceCompanyRepository;
    private $qiwi;

    /** @var AuthMicroserviceContract $auth_mks */
    private $auth_mks;
    /** @var NotifyMicroserviceContract $notify_mks */
    private $notify_mks;
    /** @var LogMicroserviceContract $log_mks */
    private $log_mks;
    /** @var CommissionCalculationMicroserviceContract $commission_mks */
    private $commission_mks;

    private $policies;
    private $creator;
    private $rewards;
    private $clients;


    public function __construct(
        ReportRepositoryContract $reportRepository,
        PolicyRepositoryContract $policyRepository,
        InsuranceCompanyRepositoryContract $insuranceCompanyRepository
    ) {
        $this->reportRepository = $reportRepository;
        $this->policyRepository = $policyRepository;
        $this->insuranceCompanyRepository = $insuranceCompanyRepository;

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
     * @throws Exception
     */
    public function createReport(array $fields)
    {
        $fields['creator_id'] = GlobalStorage::getUserId();
        $fields['reward'] = $this->getReward($fields['policies']);

        $this->creator = $this->getCreator($fields['creator_id']);
        $this->clients = $this->getClients($this->clients);

        $report = $this->reportRepository->create($fields);
        $report->policies()->sync($fields['policies']);
        $report->save();

        $this->createXls($report->id);

//        $this->qiwi = new Qiwi($this->creator['requisite'], $this->creator['tax_status']['code']);
//        $this->qiwi->makePayout($fields['reward']);

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
        return $this->auth_mks->userInfo($user_id)['content'];
    }

    /**
     * @param array $policies_ids
     * @return int
     * @throws Exception
     */
    private function getReward(array $policies_ids)
    {
        $reward_sum = 0;

        /**@var array $policy_collection */
        $policy_collection = $this->policyRepository->getList(['policy_ids' => $policies_ids]);

        if (count($policy_collection) != count($policies_ids)) { //Найдены не все полисы
            throw new Exception('Переданы некорректные идентификаторы полисов');
        }

        $this->policies = $this->reIndexPolicies($policy_collection);

        $filter = [
            'policy_id' => array_keys($this->policies)
        ];

        $rewards = $this->commission_mks->getRewards($filter)['content'];

        if (empty($rewards)) {
            throw new Exception('Ошибка получения доступных наград');
        }

        foreach ($rewards as $reward) {
            if ($reward['paid'] == false && $this->policies[$reward['policy_id']]->paid == true) {
                $reward_sum += floatval($reward['value']);
                $this->rewards[$reward['policy_id']] = $reward;
            }
        }

        return $reward_sum;
    }

    private function getClients(array $clients_ids)
    {
        $new_clients = [];
        $clients = $this->commission_mks->getClients(['client_id' => $clients_ids])['content'];

        foreach ($clients as $client) {
            $new_clients[$client['id']] = $client;
        }

        return $new_clients;
    }

    /**
     * @param int $report_id
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    private function createXls($report_id)
    {
        $n_str = 0;
        $policies = $this->preparePoliciesForXls();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Отчет');

        //Заголовки
        $sheet->setCellValue('A1', '№');
        $sheet->setCellValue('B1', 'Тип продукта');
        $sheet->setCellValue('C1', 'Номер договора');
        $sheet->setCellValue('D1', 'Страховая компания');
        $sheet->setCellValue('E1', 'Страхователь');
        $sheet->setCellValue('F1', 'Дата оформления');
        $sheet->setCellValue('G1', 'Сумма премии в рублях');
        $sheet->setCellValue('H1', 'КВ, %');
        $sheet->setCellValue('I1', 'КВ, руб.');
        $sheet->setCellValue('J1', 'Статус оплаты в СК');
        $sheet->setCellValue('K1', 'Продавец(ФИО)');
        $sheet->setCellValue('L1', 'Продавец(email)');

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

        foreach ($policies as $policy) {
            $n_str++;
            $sheet->setCellValue('A' . $n_str, $n_str);
            $sheet->setCellValue('B' . $n_str, $policy['product_type']);
            $sheet->setCellValue('C' . $n_str, $policy['dogovor_number']);
            $sheet->setCellValue('D' . $n_str, $policy['sk']);
            $sheet->setCellValue('E' . $n_str, $policy['strahovatel']);
            $sheet->setCellValue('F' . $n_str, $policy['create_date']);
            $sheet->setCellValue('G' . $n_str, $policy['premia']);
            $sheet->setCellValue('H' . $n_str, $policy['kv_percent']);
            $sheet->setCellValue('I' . $n_str, $policy['kv_rub']);
            $sheet->setCellValue('J' . $n_str, $policy['status_sk']);
            $sheet->setCellValue('K' . $n_str, $policy['prodavec_fio']);
            $sheet->setCellValue('L' . $n_str, $policy['prodavec_email']);
        }


        $file_name = sprintf('report_%s.xls', $report_id);
        $tmp_file_path = '/tmp/' . $file_name;
        $cloud_file_path = 'reports/' . $file_name;

        $writer = new Xls($spreadsheet);
        $writer->save($tmp_file_path);
        $result = Storage::cloud()->put($cloud_file_path, file_get_contents($tmp_file_path));

        if ($result != '1') {
            throw new Exception('Не удалось сохранить файл');
        } else {
            $file_params = [
                'name' => $file_name,
                'dir' => Storage::cloud()->url($cloud_file_path),
                'content_type' => mime_content_type($tmp_file_path),
                'size' => filesize($tmp_file_path),
            ];

            $file = File::create($file_params);

            Report::find($report_id)->file()->associate($file->id)->save();
            unlink($tmp_file_path);
        }
    }

    private function reIndexPolicies($policies)
    {
        $newPolicies = [];

        foreach ($policies as $policy) {
            $newPolicies[$policy->id] = $policy;
            $this->clients[] = $policy->client_id;
        }

        return $newPolicies;
    }

    private function preparePoliciesForXls()
    {
        $xls_policies = [];

        foreach ($this->policies as $policy) {

            $xls_policy['product_type'] = 'Осаго';

            if ($policy->agent_id == $this->creator['id']) {
                $xls_policy['dogovor_number'] = $policy->number;
            } else {
                $xls_policy['dogovor_number'] = '-';
            }

            $xls_policy['sk'] = $this->insuranceCompanyRepository->getById($policy->insurance_company_id)->name;

            $xls_policy['strahovatel'] = $this->clients[$policy->client_id]['full_name'];

            $xls_policy['create_date'] = $policy->registration_date;

            $xls_policy['premia'] = $policy->premium;

            if ($policy->agent_id == $this->creator['id']) {
                $xls_policy['kv_percent'] = $this->rewards[$policy->id]['commission']['agent_reward'];
            } else {
                $xls_policy['kv_percent'] = $this->rewards[$policy->id]['commission']['subagent_reward'];
            }

            $xls_policy['kv_rub'] = $this->rewards[$policy->id]['value'];

            $xls_policy['status_sk'] = ($policy->paid == true ? 'Оплачен' : 'Не оплачен');

            if ($policy->agent_id == $this->creator['id']){
                $xls_policy['prodavec_fio'] = $this->creator['full_name'];
            } else {
                $xls_policy['prodavec_fio'] = '-';
            }

            if ($policy->agent_id == $this->creator['id']){
                $xls_policy['prodavec_email'] = $this->creator['email'];
            } else {
                $xls_policy['prodavec_email'] = '-';
            }
            
            $xls_policies[] = $xls_policy;
        }

        return $xls_policies;
    }
}
