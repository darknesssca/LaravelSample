<?php

namespace App\Services\Qiwi;

use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\ReportRepositoryContract;
use App\Contracts\Services\ReportServiceContract;
use App\Contracts\Utils\DeferredResultContract;
use App\Exceptions\Qiwi\CreatePayoutException;
use App\Exceptions\ReportNotFoundException;
use App\Exceptions\TaxStatusNotServiceException;
use App\Jobs\Qiwi\QiwiCreatePayoutJob;
use App\Jobs\Qiwi\QiwiExecutePayoutJob;
use App\Jobs\Qiwi\QiwiGetPayoutStatusJob;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
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
    )
    {
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
        if ($report->creator_id !== GlobalStorage::getUserId() && !GlobalStorage::userIsAdmin()) {
            throw new Exception('Ошибка доступа');
        }
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
     * @throws Exception
     */
    public function createReport(array $fields)
    {
        $this->available_reward = $this->commission_mks->getAvailableReward()["content"];
        if (isset($this->available_reward['available']) && $this->available_reward['available'] <= 0) {
            throw new Exception('Исчерпан лимит вывода на текущий год');
        }

        $used_policies = $this->getReportedPoliciesIds(GlobalStorage::getUserId());
        $intersect = array_intersect($used_policies, $fields['policies']);
        if (count($intersect) > 0) {
            $intersected = implode(', ', $intersect);
            throw new Exception("Для этих полисов уже была запрошена выплата: $intersected");
        }

        $fields['creator_id'] = GlobalStorage::getUserId();
        $fields['reward'] = $this->getReward($fields['policies']);

        $this->creator = $this->getCreator($fields['creator_id']);

        if ($this->creator['tax_status']['code'] == 'individual') {
            throw new TaxStatusNotServiceException();
        }

        $report = $this->reportRepository->create($fields);
        $report->policies()->sync($fields['policies']);
        $report->save();

        $deferredResultUtil = app(DeferredResultContract::class);

        $deferredResultId = $deferredResultUtil->getId('report', $report->id);

        $params = [
            'user' => GlobalStorage::getUser(),
            'report_id' => $report->id,
        ];

        $deferredResultUtil->process($deferredResultId, $report->id);

        dispatch((new QiwiCreatePayoutJob($params))->onQueue('QiwiCreatePayout'));

        $message = "Создан отчет на выплату {$report->id}";
        $this->log_mks->sendLog($message, config('api_sk.logMicroserviceCode'), $fields['creator_id']);

        return $deferredResultUtil->getInitialResponse($deferredResultId);
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
     * @param bool $check_reward
     * @return int
     * @throws Exception
     */
    private function getReward(array $policies_ids, $check_reward = true)
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
            if ($check_reward == false) {
                $this->rewards[$reward['policy_id']] = $reward;
            } else {
                if ($reward['paid'] == false && $reward['requested'] == false && $this->policies[$reward['policy_id']]->paid == true) {
                    $reward_sum += floatval($reward['value']);
                    $this->rewards[$reward['policy_id']] = $reward;
                }
            }
        }

        if ($reward_sum <= 0 && $check_reward == true) {
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
     * @param Report $report
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    public function createXls(Report $report)
    {
        $n_str = 2;
        $policies_ids = $report->policies()->get(['id'])->toArray();
        $this->getReward($policies_ids, false);
        $this->clients = $this->getClients($this->clients);
        $this->creator = $this->getCreator(GlobalStorage::getUserId());

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
            $n_str += 1;
        }

        $file_name = sprintf('report_%s.xls', $report->id);
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
                'dir' => config('filesystems.disks.minio.bucket') . $cloud_file_path,
                'content_type' => mime_content_type($tmp_file_path),
                'size' => filesize($tmp_file_path),
            ];

            $file = File::query()->create($file_params);

            $report->file()->associate($file->id)->save();
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

    public function initQiwi($user_requisites, $tax_status_code, $description = 'Услуги по заключению договора страхования')
    {
        if (empty($this->qiwi)) {
            $this->qiwi = new Qiwi($user_requisites, $tax_status_code, $description);
        }

        return $this->qiwi;
    }

    /**
     * @param $report
     * @throws Exception
     * @throws CreatePayoutException
     */
    public function createPayout($report)
    {
        if (is_integer($report)) {
            $report = $this->reportRepository->getById($report);
        }

        if ($report->is_payed || $report->creator_id !== GlobalStorage::getUserId()) {
            throw new Exception('По этому отчету уже была произведена выплата или вы не являетесь создателем отчета');
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
                'requested' => true,
                'processing' => 10,
            ];

            $this->commission_mks->massUpdateRewards($fields);
        } else {
            throw new CreatePayoutException('Не удалось зарегистрировать оплату. Попробуйте позже');
        }
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
        if ($report->is_payed || $report->creator_id !== GlobalStorage::getUserId()) {
            throw new Exception('По этому отчету уже была произведена выплата или вы не являетесь создателем отчета');
        }

        if (!$this->qiwi) {
            $this->initQiwi([], '');
        }

        $executeResult = $this->qiwi->executePayout($report->payout_id);

        $queueParams = [
            'user' => GlobalStorage::getUser(),
            'report_id' => $report->id
        ];

        if ($executeResult['status'] === 'progress') {
            Queue::later(
                Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                new QiwiGetPayoutStatusJob($queueParams),
                '',
                'QiwiGetPayoutStatus'
            );
        } elseif ($executeResult['status'] === 'expired') {
            dispatch(new QiwiCreatePayoutJob($queueParams))->onQueue('QiwiCreatePayoutJob');
        } elseif ($executeResult['status'] === 'done') {
            $this->sendCheckToAdmin($executeResult['checkUrl']);
            $report->is_payed = true;
            $report->save();

            if (empty($this->rewards)) {
                $policies_ids = array_column($report->policies()->get(['id'])->toArray(), 'id');
                $this->rewards = $this->getRewardsList($policies_ids);
            }

            $fields = [
                'reward_id' => array_column($this->rewards, 'id'),
                'paid' => true,
                'processing' => 20,
            ];

            $this->commission_mks->massUpdateRewards($fields);
            return true;
        }
        return false;
    }

    public function getPayoutStatus($report)
    {
        if (is_integer($report)) {
            $report = $this->reportRepository->getById($report);
        }
        if ($report->is_payed || $report->creator_id !== GlobalStorage::getUserId()) {
            throw new Exception('По этому отчету уже была произведена выплата или вы не являетесь создателем отчета');
        }

        if (!$this->qiwi) {
            $this->initQiwi([], '');
        }

        $result = $this->qiwi->getPayoutStatus($report->payout_id);

        $queueParams = [
            'user' => GlobalStorage::getUser(),
            'report_id' => $report->id
        ];

        if ($result['status'] === 'progress') {
            Queue::later(
                Carbon::now()->addSeconds(config('api.qiwi.requestInterval')),
                new QiwiGetPayoutStatusJob($queueParams),
                '',
                'QiwiGetPayoutStatus'
            );
        } elseif ($result['status'] === 'expired') {
            dispatch(new QiwiCreatePayoutJob($queueParams))->onQueue('QiwiCreatePayoutJob');
        } elseif ($result['status'] === 'done') {
            $this->sendCheckToAdmin($result['checkUrl']);
            $report->is_payed = true;
            $report->save();

            if (empty($this->rewards)) {
                $policies_ids = array_column($report->policies()->get(['id'])->toArray(), 'id');
                $this->rewards = $this->getRewardsList($policies_ids);
            }

            $fields = [
                'reward_id' => array_column($this->rewards, 'id'),
                'paid' => true,
                'processing' => 20,
            ];

            $this->commission_mks->massUpdateRewards($fields);
            return true;
        }
        return false;
    }
    /**
     * @param $report
     * @return mixed
     * @throws ReportNotFoundException
     */
    public function rerunPayout($report)
    {
        if (is_integer($report)) {
            $report = $this->reportRepository->getById($report);
        }

        $params = [
            'user' => GlobalStorage::getUser(),
            'report_id' => $report->id,
        ];

        $deferredResultUtil = app(DeferredResultContract::class);

        $deferredResultId = $deferredResultUtil->getId('report', $report->id);

        $deferredResultUtil->process($deferredResultId);

        if ($report->requested == false && $report->is_payed == false) {
            $report->processing = 1;
            $report->save();
            dispatch((new QiwiCreatePayoutJob($params))->onQueue('QiwiCreatePayout'));
        } elseif ($report->requested == true && $report->is_payed == false) {
            $report->processing = 10;
            $report->save();
            dispatch((new QiwiExecutePayoutJob($params))->onQueue('QiwiExecutePayout'));
        }

        return $deferredResultUtil->getInitialResponse($deferredResultId);
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

    public function getProcessingStatus(): array
    {
        $reports = $this->reportRepository->getProcessingReports();
        return [
            'count' => $reports->count(),
            'sum' => $reports->sum('reward'),
        ];
    }

    private function sendCheckToAdmin($checkUrl): void
    {
        if (!$checkUrl) {
            return;
        }
        $adminEmails = config('api.qiwi.adminEmails');
        if (!$adminEmails) {
            return;
        }
        $adminEmailsArray = explode(',', $adminEmails);
        if (empty($adminEmailsArray)) {
            return;
        }
        foreach ($adminEmailsArray as $email) {
            try {
                $email = trim($email);
                $notify = app(NotifyMicroserviceContract::class);
                $data = [
                    'link' => $checkUrl,
                ];
                $notify->sendMail($email, $data, 'qiwi-check');
            } catch (\Exception $exception) {
                // ignore
            }
        }
    }

    public function getBalance()
    {
        if (!$this->qiwi) {
            $this->initQiwi([], '');
        }

        return $this->qiwi->getBalance();
    }
}
