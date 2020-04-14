<?php


namespace App\Services\Drafts;


use App\Contracts\Repositories\DraftClientRepositoryContract;
use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\DriverRepositoryContract;
use App\Contracts\Repositories\PolicyTypeRepositoryContract;
use App\Contracts\Repositories\Services\DraftServiceContract;
use App\Exceptions\DraftNotFoundException;
use App\Exceptions\GuidesNotFoundException;
use App\Traits\ValueSetterTrait;
use Benfin\Api\GlobalStorage;
use Carbon\Carbon;

class DraftService implements DraftServiceContract
{
    use ValueSetterTrait;

    protected $draftRepository;
    protected $draftClientRepository;
    protected $policyTypeRepository;
    protected $driverRepository;

    public function __construct(
        DraftRepositoryContract $draftRepository,
        PolicyTypeRepositoryContract $policyTypeRepository,
        DraftClientRepositoryContract $draftClientRepository,
        DriverRepositoryContract $driverRepository
    )
    {
        $this->draftRepository = $draftRepository;
        $this->policyTypeRepository = $policyTypeRepository;
        $this->draftClientRepository = $draftClientRepository;
        $this->driverRepository = $driverRepository;
    }

    public function getDraftByAgentId()
    {
        $agentId = GlobalStorage::getUserId();
        $draftList = $this->draftRepository->getDraftsByAgentId($agentId);
        if (!$draftList || !$draftList->count()) {
            throw new DraftNotFoundException('Не найдены черновики для текущего агента');
        }
        return $draftList;
    }

    public function getById($id)
    {
        $agentId = GlobalStorage::getUserId();
        $draft = $this->draftRepository->getById($id, $agentId);
        if (!$draft) {
            throw new DraftNotFoundException('Не найден черновик для текущего агента');
        }
        return $draft;
    }

    public function getByFilter(array $attributes)
    {
        $agentId = GlobalStorage::getUserId();
        return $this->draftRepository->getByFilter($agentId, $attributes);
    }

    public function create($attributes):int
    {
        $policyType = $this->policyTypeRepository->getByCode('osago');
        if (!$policyType) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике типов полисов');
        }
        $policyData = [
            'agent_id' => GlobalStorage::getUserId(),
            'type_id' => $policyType->id,
        ];
        $this->preparePolicyData($policyData, $attributes);
        //subjects
        if (
            isset($attributes['subjects']) && $attributes['subjects'] &&
            (
                (isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId']) ||
                (isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'])
            )
        ) {
            foreach ($attributes['subjects'] as $subject) {
                $subjectData = [];
                $this->prepareSubjects($subjectData, $subject);
                if (
                    isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId'] &&
                    isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'] &&
                    ($attributes['policy']['insurantId'] == $attributes['policy']['ownerId']) &&
                    ($subject['id'] == $attributes['policy']['ownerId'])
                ) {
                    $result = $this->draftClientRepository->create($subjectData);
                    $policyData['client_id'] = $result->id;
                    $policyData['insurant_id'] = $result->id;
                } elseif (isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId'] && ($subject['id'] == $attributes['policy']['ownerId'])) { // если это овнер
                    $result = $this->draftClientRepository->create($subjectData);
                    $policyData['client_id'] = $result->id;
                } elseif (isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'] && ($subject['id'] == $attributes['policy']['insurantId'])) {
                    $result = $this->draftClientRepository->create($subjectData);
                    $policyData['insurant_id'] = $result->id;
                } else {
                    continue;
                }
            }
        }
        //car
        $this->prepareCarData($policyData, $attributes);
        $draft = $this->draftRepository->create($policyData);
        if (isset($attributes['drivers']) && $attributes['drivers']) {
            foreach ($attributes['drivers'] as $driver) {
                $driverData = [];
                $this->prepareDriver($driverData, $driver);
                $draft->drivers()->create($driverData);
            }
        }
        return $draft->id;
    }

    public function update($draftId, $attributes):void
    {
        $draftId = (int)$draftId;
        if (!$draftId) {
            throw new DraftNotFoundException('Передан некорректный id черновика', 400);
        }
        //old data
        $agentId = GlobalStorage::getUserId();
        $oldDraft = $this->draftRepository->getById($draftId, $agentId);
        if (!$oldDraft) {
            throw new DraftNotFoundException('Черновик не найден');
        }
        $policyType = $this->policyTypeRepository->getByCode('osago');
        if (!$policyType) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике типов полисов');
        }
        //policy
        $policyData = [
            'agent_id' => GlobalStorage::getUserId(),
            'type_id' => $policyType->id,
        ];
        $this->preparePolicyData($policyData, $attributes);
        //subjects
        $isNeedDeleteOwner = false;
        $isNeedDeleteInsurer = false;
        if (
            isset($attributes['subjects']) && $attributes['subjects'] &&
            (
                (isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId']) ||
                (isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'])
            )
        ) {
            foreach ($attributes['subjects'] as $subject) {
                $subjectData = [];
                $this->prepareSubjects($subjectData, $subject);
                if ((!isset($attributes['policy']['ownerId']) || !$attributes['policy']['ownerId']) && $oldDraft->client_id) {
                    $isNeedDeleteOwner = true;
                    $policyData['client_id'] = null;
                }
                if ((!isset($attributes['policy']['insurantId']) || !$attributes['policy']['insurantId']) && $oldDraft->insurant_id) {
                    $isNeedDeleteInsurer = true;
                    $policyData['insurant_id'] = null;
                }
                if (
                    isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId'] &&
                    isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'] &&
                    ($attributes['policy']['insurantId'] == $attributes['policy']['ownerId']) &&
                    ($subject['id'] == $attributes['policy']['ownerId'])
                ) {
                    $this->draftClientRepository->update($oldDraft->client_id, $subjectData);
                    if ($oldDraft->client_id != $oldDraft->insurant_id) {
                        $isNeedDeleteInsurer = true;
                    }
                    $policyData['client_id'] = $oldDraft->client_id;
                    $policyData['insurant_id'] = $oldDraft->client_id;
                } elseif (isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId'] && ($subject['id'] == $attributes['policy']['ownerId'])) { // если это овнер
                    if ($oldDraft->client_id) {
                        $this->draftClientRepository->update($oldDraft->client_id, $subjectData);
                        $policyData['client_id'] = $oldDraft->client_id;
                    } else {
                        $result = $this->draftClientRepository->create($subjectData);
                        $policyData['client_id'] = $result->id;
                    }
                } elseif (isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'] && ($subject['id'] == $attributes['policy']['insurantId'])) {
                    if ($oldDraft->insurant_id && ($oldDraft->insurant_id != $oldDraft->client_id)) {
                        $this->draftClientRepository->update($oldDraft->insurant_id, $subjectData);
                        $policyData['insurant_id'] = $oldDraft->insurant_id;
                    } else {
                        $result = $this->draftClientRepository->create($subjectData);
                        $policyData['insurant_id'] = $result->id;
                    }
                } else {
                    continue;
                }
            }
        }
        //car
        $this->prepareCarData($policyData, $attributes);
        $draft = $this->draftRepository->update($oldDraft->id, $policyData);
        if (isset($attributes['drivers']) && $attributes['drivers']) {
            $driverDataUpdate = [];
            foreach ($attributes['drivers'] as $driver) {
                $driverData = [];
                $this->prepareDriver($driverData, $driver);
                $driverDataUpdate[] = $driverData;
            }
            $iterator = 0;
            foreach ($driverDataUpdate as $driverData) {
                if (isset($oldPolicy->drivers[$iterator])) {
                    $this->driverRepository->update($oldDraft->drivers[$iterator]->id, $driverDataUpdate[$iterator]);
                } else {
                    $oldDraft->drivers()->create($driverDataUpdate[$iterator]);
                }
                $iterator++;
            }
            if ($iterator < count($oldDraft->drivers)) {
                for ($i = $iterator; $i < count($oldDraft->drivers); $i++) {
                    $this->driverRepository->delete($oldDraft->drivers[$i]->id);
                }
            }
        }
        if ($isNeedDeleteOwner) {
            $this->draftClientRepository->delete($oldDraft->owner_id);
        }
        if ($isNeedDeleteInsurer) {
            $this->draftClientRepository->delete($oldDraft->insurant_id);
        }
    }

    public function delete($draftId):void
    {
        $draftId = (int)$draftId;
        if (!$draftId) {
            throw new DraftNotFoundException('Передан некорректный id черновика', 400);
        }
        $agentId = GlobalStorage::getUserId();
        $draft = $this->draftRepository->getById($draftId, $agentId);
        if (!$draft) {
            throw new DraftNotFoundException('Черновик не найден');
        }
        $draft->drivers()->delete();
        $draft->delete();
    }

    protected function preparePolicyData(&$policyData, $attributes)
    {
        $this->setValuesByArray($policyData, [
            'region_id' => 'policyProcessingRegion',
            'is_multi_drive' => 'isMultidrive',
        ], $attributes['policy']);
        if (isset($attributes['policy']['beginDate']) && $attributes['policy']['beginDate']) {
            $policyData['start_date'] = Carbon::createFromFormat('Y-m-d', $attributes['policy']['beginDate']);
        }
        if (isset($attributes['policy']['endDate']) && $attributes['policy']['endDate']) {
            $policyData['end_date'] = Carbon::createFromFormat('Y-m-d', $attributes['policy']['endDate']);
        }
    }

    protected function prepareSubjects(&$subjectData, $subject)
    {
        $this->setValuesByArray($subjectData, [
            'last_name' => 'lastName',
            'first_name' => 'firstName',
            'patronymic' => 'middleName',
            'birth_place' => 'birthPlace',
            'gender_id' => 'gender',
            'address' => 'address',
            'phone' => 'phone',
            'email' => 'email',
            'citizenship_id' => 'citizenship',
            'is_russian' => 'isResident',
        ], $subject['fields']);
        if (isset($subject['fields']['passport']) && $subject['fields']['passport']) {
            $this->setValuesByArray($subjectData, [
                'passport_series' => 'series',
                'passport_number' => 'number',
                'passport_date' => 'dateIssue',
                'passport_issuer' => 'issuedBy',
                'passport_unit_code' => 'subdivisionCode',
            ], $subject['fields']['passport']);
        }
        if (isset($subject['fields']['birthdate']) && $subject['fields']['birthdate']) {
            $subjectData['birth_date'] = Carbon::createFromFormat('Y-m-d', $subject['fields']['birthdate']);
        }
    }

    protected function prepareCarData(&$policyData, $attributes)
    {
        if (isset($attributes['car']) && $attributes['car']) {
            $this->setValuesByArray($policyData, [
                'vehicle_model_id' => 'model',
                'vehicle_engine_power' => 'enginePower',
                'vehicle_vin' => 'vin',
                'vehicle_reg_number' => 'regNumber',
                'vehicle_reg_country' => 'countryOfRegistration',
                'vehicle_made_year' => 'year',
                'vehicle_unladen_mass' => 'minWeight',
                'vehicle_loaded_mass' => 'maxWeight',
                'vehicle_count_seats' => 'seats',
                'vehicle_mileage' => 'mileage',
                'vehicle_cost' => 'vehicleCost',
                'vehicle_acquisition' => 'sourceAcquisition',
                'vehicle_usage_target' => 'vehicleUsage',
                'vehicle_with_trailer' => 'isUsedWithTrailer',
            ], $attributes['car']);
            if (isset($attributes['car']['document']) && $attributes['car']['document']) {
                $this->setValuesByArray($policyData, [
                    'vehicle_reg_doc_type_id' => 'documentType',
                    'vehicle_doc_series' => 'series',
                    'vehicle_doc_number' => 'number',
                    'vehicle_doc_issued' => 'dateIssue',
                ], $attributes['car']['document']);
            }
            if (isset($attributes['car']['inspection']) && $attributes['car']['inspection']) {
                $this->setValuesByArray($policyData, [
                    'vehicle_inspection_doc_series' => 'series',
                    'vehicle_inspection_doc_number' => 'number',
                    'vehicle_inspection_issued_date' => 'dateIssue',
                    'vehicle_inspection_end_date' => 'dateEnd',
                ], $attributes['car']['inspection']);
            }
        }
    }

    protected function prepareDriver(&$driverData, $attributes)
    {
        $this->setValuesByArray($driverData, [
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'patronymic' => 'middleName',
            'license_series' => 'license_series',
            'license_number' => 'license_number',
        ], $attributes);
        if (isset($attributes['birthdate']) && $attributes['birthdate']) {
            $driverData['birth_date'] = Carbon::createFromFormat('Y-m-d', $attributes['birthdate']);
        }
        if (isset($attributes['license_date']) && $attributes['license_date']) {
            $driverData['license_date'] = Carbon::createFromFormat('Y-m-d', $attributes['license_date']);
        }
        if (isset($attributes['drivingLicenseIssueDateOriginal']) && $attributes['drivingLicenseIssueDateOriginal']) {
            $driverData['exp_start_date'] = Carbon::createFromFormat('Y-m-d', $attributes['drivingLicenseIssueDateOriginal']);
        }
    }

}
