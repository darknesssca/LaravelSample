<?php

//TODO Сделать получение пользователей из кэша

namespace App\Services\Qiwi;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\ReportRepositoryContract;
use App\Contracts\Services\ReportServiceContract;
use App\Exceptions\QiwiCreatePayoutException;
use App\Models\File;
use App\Models\Report;
use App\Repositories\PolicyRepository;
use App\Repositories\ReportRepository;
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
    /** @var ReportRepository $reportRepository */
    private $reportRepository;
    /** @var PolicyRepository $policyRepository */
    private $policyRepository;
    private $insuranceCompanyRepository;

    /** @var  Qiwi $qiwi */
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
    private $available_reward;


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
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    public function createReport(array $fields)
    {
        $this->available_reward = $this->commission_mks->getAvailableReward();

        if (isset($this->available_reward['available']) && $this->available_reward['available'] <= 0) {
            throw new Exception('Исчерпан лимит наград');
        }
        $used_policies = $this->getReportedPoliciesIds(GlobalStorage::getUserId());
        $intersect = array_intersect($used_policies, $fields['policies']);
        if (count($intersect) > 0) {
            $intersected = implode(',', $intersect);
            throw new Exception("Для этих полисов уже была запрошена выплата: $intersected");
        }

        $fields['creator_id'] = GlobalStorage::getUserId();
        $fields['reward'] = $this->getReward($fields['policies']);

        $this->creator = $this->getCreator($fields['creator_id']);
        $this->clients = $this->getClients($this->clients);

        $report = $this->reportRepository->create($fields);
        $report->policies()->sync($fields['policies']);
        $report->save();
        try {
            $this->createPayout($report);
        } catch (QiwiCreatePayoutException $exception) { //если при регистрации платежа произошла ошибка, то отменяем создание отчета
            $report->policies()->detach();
            $report->forceDelete();
            throw $exception;
        }
        $this->createXls($report->id); //если все хорошо, то создаем и сохраняем отчет

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

    private function getRewardsList(array $policies_ids)
    {
        $filter = [
            'policy_id' => $policies_ids
        ];

        return $this->commission_mks->getRewards($filter)['content'];
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

        $rewards = $this->getRewardsList(array_keys($this->policies));

        if (empty($rewards)) {
            throw new Exception('Ошибка получения доступных наград');
        }

        foreach ($rewards as $reward) {
            if ($reward['paid'] == false && $reward['requested'] == false && $this->policies[$reward['policy_id']]->paid == true) {
                $reward_sum += floatval($reward['value']);
                $this->rewards[$reward['policy_id']] = $reward;
            }
        }

        if ($reward_sum <= 0) {
            throw new Exception('Отстутствуют доступные для вывода награды');
        }

        if (isset($this->available_reward['available']) && $reward_sum > $this->available_reward['available']) {
            throw new Exception('Размер награды превышает допустимое значение');
        }

        return $reward_sum;
    }

    private function getClients(array $clients_ids)
    {
        $new_clients = [];
        $clients = $this->commission_mks->getClients(['client_id' => $clients_ids])['content'];
        if (!empty($clients)) {
            foreach ($clients as $client) {
                $new_clients[$client['id']] = $client;
            }
        }

        return $new_clients;
    }

    /** cоздает файл отета и отправляет в минио
     * @param int $report_id
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    private function createXls($report_id)
    {
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

        $n_str = 1;
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
        $cloud_file_path = '/reports/' . md5($file_name) . '.xls';

        $writer = new Xls($spreadsheet);
        $writer->save($tmp_file_path);
        $result = Storage::cloud()->put($cloud_file_path, file_get_contents($tmp_file_path));

        if ($result != '1') {
            throw new Exception('Не удалось сохранить файл');
        } else {
            $file_params = [
                'name' => $file_name,
                'dir' =>  config('filesystems.disks.minio.bucket') . $cloud_file_path,
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

            $xls_policy['strahovatel'] = $this->clients[$policy->client_id]['full_name'] ?? '-';

            $xls_policy['create_date'] = $policy->registration_date;

            $xls_policy['premia'] = $policy->premium;

            if ($policy->agent_id == $this->creator['id']) {
                $xls_policy['kv_percent'] = $this->rewards[$policy->id]['commission']['agent_reward'];
            } else {
                $xls_policy['kv_percent'] = $this->rewards[$policy->id]['commission']['subagent_reward'];
            }

            $xls_policy['kv_rub'] = $this->rewards[$policy->id]['value'];

            $xls_policy['status_sk'] = ($policy->paid == true ? 'Оплачен' : 'Не оплачен');

            if ($policy->agent_id == $this->creator['id']) {
                $xls_policy['prodavec_fio'] = $this->creator['full_name'];
            } else {
                $xls_policy['prodavec_fio'] = '-';
            }

            if ($policy->agent_id == $this->creator['id']) {
                $xls_policy['prodavec_email'] = $this->creator['email'];
            } else {
                $xls_policy['prodavec_email'] = '-';
            }

            $xls_policies[] = $xls_policy;
        }

        return $xls_policies;
    }

    public function initQiwi($user_requisites, $tax_status_code, $description = 'Перевод')
    {
        if (empty($this->qiwi)) {
            $this->qiwi = new Qiwi($user_requisites, $tax_status_code, $description);
        }

        return $this->qiwi;
    }

    /**
     * @param $report
     * @throws Exception
     * @throws QiwiCreatePayoutException
     */
    public function createPayout($report)
    {
        if (is_integer($report)) {
            $report = $this->reportRepository->getById($report);
        }

        if (empty($this->creator)) {
            $this->creator = $this->getCreator(GlobalStorage::getUserId());
        }

        $this->initQiwi($this->creator['requisite'], $this->creator['tax_status']['code']);
        $payout_id = $this->qiwi->createPayout($report->reward);

        if (!empty($payout_id)) {
            $report->payout_id = $payout_id;
            $report->requested = true;
            $report->save();

            if (empty($this->rewards)) {
                $policies_ids = array_column($report->policies()->get(['id'])->toArray(), 'id');
                $this->rewards = $this->getRewardsList($policies_ids);
            }

            $fields = [
                'reward_id' => array_column($this->rewards, 'id'),
                'requested' => true
            ];

            $this->commission_mks->massUpdateRewards($fields);
        } else {
            throw new QiwiCreatePayoutException('Не удалось зарегистрировать оплату. Попробуйте позже');
        }

        $this->executePayout($report);
    }

    /**
     * @param $report
     * @throws Exception
     */
    public function executePayout($report)
    {
        if (is_integer($report)) {
            $report = $this->reportRepository->getById($report);
        }

        if ($this->qiwi) {
            $this->initQiwi([], '');
        }

        $execute_status = $this->qiwi->executePayout($report->payout_id);

        if ($execute_status == true) {
            $report->is_payed = true;
            $report->save();

            if (empty($this->rewards)) {
                $policies_ids = array_column($report->policies()->get(['id'])->toArray(), 'id');
                $this->rewards = $this->getRewardsList($policies_ids);
            }

            $fields = [
                'reward_id' => array_column($this->rewards, 'id'),
                'paid' => true
            ];

            $this->commission_mks->massUpdateRewards($fields);
        }
    }

    /**возвращает список id полисов, по которым пользователь запросил вывод средств
     * @param $agent_id
     * @return array
     */
    public function getReportedPoliciesIds(int $agent_id)
    {
        $reports = $this->reportRepository->getByCreatorId($agent_id, []);
        $exclude_policy_ids = $reports->reduce(function ($carry, $report) {
            $arr = $report['policies']->map(function ($polic) {
                return $polic['id'];
            })->toArray();
            return array_merge($arr, $carry);
        }, []);
        return array_unique($exclude_policy_ids);
    }
}
