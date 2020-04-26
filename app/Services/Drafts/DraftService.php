<?php


namespace App\Services\Drafts;


use App\Contracts\Repositories\DraftClientRepositoryContract;
use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\DriverRepositoryContract;
use App\Contracts\Repositories\PolicyTypeRepositoryContract;
use App\Contracts\Repositories\Services\DraftServiceContract;
use App\Exceptions\DraftNotFoundException;
use App\Exceptions\GuidesNotFoundException;
use App\Models\DocType;
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
        $attributes["page"] = isset($attributes["page"]) && $attributes["page"] > 0 ? $attributes["page"] : '1';
        $attributes["count"] = isset($attributes["count"]) && $attributes["count"] > 0 ? $attributes["count"] : '20';

        $agentId = GlobalStorage::getUserId();
        return $this->draftRepository->getByFilter($agentId, $attributes);
    }

    public function create($attributes): int
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

        //drivers
        if (
            isset($attributes['drivers']) &&
            $attributes['drivers'] &&
            is_array($attributes['drivers']) &&
            isset($attributes['policy']['isMultidrive']) &&
            !$attributes['policy']['isMultidrive']
        ) {
            foreach ($attributes['drivers'] as $driver) {
                $driverSubject = collect($attributes["subjects"])
                    ->filter(function ($item) use ($driver) {
                        return $item["id"] == $driver["driver"]["driverId"];
                    })
                    ->first();

                $driverSubject["fields"]["drivingLicenseIssueDateOriginal"] = isset($driver["driver"]["drivingLicenseIssueDateOriginal"]) ? $driver["driver"]["drivingLicenseIssueDateOriginal"] : null;
                $driverData = [];
                $this->prepareDriver($driverData, $driverSubject["fields"]);
                $draft->drivers()->create($driverData);
            }
        }

        return $draft->id;
    }

    public function update($draftId, $attributes): void
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
        if ( isset($attributes['drivers']) && isset($attributes["policy"]["isMultidrive"]) ) {
            $driverDataUpdate = [];
            if ( !$attributes["policy"]["isMultidrive"] && isset($attributes['drivers']) && is_array($attributes['drivers'])) {
                foreach ($attributes['drivers'] as $driver) {
                    $driverSubject = collect($attributes["subjects"])
                        ->filter(function ($item) use ($driver) {
                            return $item["id"] == $driver["driver"]["driverId"];
                        })
                        ->first();
                    $driverData = [];
                    $driverSubject["fields"]["drivingLicenseIssueDateOriginal"] = isset($driver["driver"]["drivingLicenseIssueDateOriginal"]) ? $driver["driver"]["drivingLicenseIssueDateOriginal"] : null;

                    $this->prepareDriver($driverData, $driverSubject['fields']);
                    $driverDataUpdate[] = $driverData;
                }
            }
            $iterator = 0;
            foreach ($driverDataUpdate as $driverData) {

                if (isset($oldDraft->drivers[$iterator])) {
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

    public function delete($draftId): void
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
        $this->setValuesByArrayWithEmptyString($policyData, [
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
        $this->setValuesByArrayWithEmptyString($subjectData, [
            'last_name' => 'lastName',
            'first_name' => 'firstName',
            'patronymic' => 'middleName',
            'birth_place' => 'birthPlace',
            'gender_id' => 'gender',
            'address' => 'addressString',
            'phone' => 'phone',
            'email' => 'email',
            'citizenship_id' => 'citizenship',
            'is_russian' => 'isResident',
        ], $subject['fields']);

        if (isset($subject['fields']['documents']) && $subject['fields']['documents'] && is_array($subject['fields']['documents'])) {
            foreach ($subject['fields']['documents'] as $document) {
                if (isset($document['document']['documentType']) && $document['document']['documentType'] == 'passport') {
                    $this->setValuesByArrayWithEmptyString($subjectData, [
                        'passport_series' => 'series',
                        'passport_number' => 'number',
                        'passport_issuer' => 'issuedBy',
                        'passport_unit_code' => 'subdivisionCode',
                    ], $document['document']);

                    if (isset($document['document']['dateIssue']) && $document['document']['dateIssue']) {
                        $subjectData['passport_date'] = Carbon::createFromFormat('Y-m-d', $document['document']['dateIssue']);
                    }

                } else {
                    continue;
                }
            }

        }


        if (isset($subject['fields']['birthdate']) && $subject['fields']['birthdate']) {
            $subjectData['birth_date'] = Carbon::createFromFormat('Y-m-d', $subject['fields']['birthdate']);
        }

        if (isset($subject['fields']['addresses'][0]['address']) && $subject['fields']['addresses'][0]['address']) {
            $subjectData['address_json'] = $subject['fields']['addresses'][0]['address'];
        }
    }

    protected function prepareCarData(&$policyData, $attributes)
    {
        if (isset($attributes['car']) && $attributes['car']) {
            $this->setValuesByArrayWithEmptyString($policyData, [
                'vehicle_model' => 'model',
                'vehicle_category_id' => 'category',
                'vehicle_mark_id' => 'maker',
                'vehicle_engine_power' => 'enginePower',
                'vehicle_vin' => 'vin',
                'irregular_vin' => 'isIrregularVIN',
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
                $this->setValuesByArrayWithEmptyString($policyData, [
                    'vehicle_reg_doc_type_id' => 'documentType',
                    'vehicle_doc_series' => 'series',
                    'vehicle_doc_number' => 'number',
                ], $attributes['car']['document']);
            }

            if (isset($policyData["vehicle_reg_doc_type_id"]) && !empty($policyData["vehicle_reg_doc_type_id"])) {
                $policyData["vehicle_reg_doc_type_id"] = DocType::query()->where('code', $policyData["vehicle_reg_doc_type_id"])->first()->id;
            }

            if (isset($attributes['car']['document']['dateIssue']) && $attributes['car']['document']['dateIssue']) {
                $policyData['vehicle_doc_issued'] = Carbon::createFromFormat('Y-m-d', $attributes['car']['document']['dateIssue']);
            }

            if (isset($attributes['car']['inspection']) && $attributes['car']['inspection']) {
                $this->setValuesByArrayWithEmptyString($policyData, [
                    'vehicle_inspection_doc_series' => 'series',
                    'vehicle_inspection_doc_number' => 'number',
                ], $attributes['car']['inspection']);

                if (isset($attributes['car']['inspection']['dateIssue']) && $attributes['car']['inspection']['dateIssue']) {
                    $policyData['vehicle_inspection_issued_date'] = Carbon::createFromFormat('Y-m-d', $attributes['car']['inspection']['dateIssue']);
                }

                if (isset($attributes['car']['inspection']['dateEnd']) && $attributes['car']['inspection']['dateEnd']) {
                    $policyData['vehicle_inspection_end_date'] = Carbon::createFromFormat('Y-m-d', $attributes['car']['inspection']['dateEnd']);
                }
            }
        }
    }

    protected function prepareDriver(&$driverData, $attributes)
    {
        $this->setValuesByArrayWithEmptyString($driverData, [
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'patronymic' => 'middleName',
            'address' => 'addressString',
            'gender_id' => 'gender',
            'citizenship_id' => 'citizenship'
        ], $attributes);


        if (isset($attributes['birthdate']) && $attributes['birthdate']) {
            $driverData['birth_date'] = Carbon::createFromFormat('Y-m-d', $attributes['birthdate']);
        }


        if (isset($attributes['addresses'][0]['address']) && $attributes['addresses'][0]['address']) {
            $driverData['address_json'] = $attributes['addresses'][0]['address'];
        }

        if (isset($attributes['documents']) && $attributes['documents'] && is_array($attributes['documents'])) {
            foreach ($attributes['documents'] as $document) {
                if ($document['document']['documentType'] == 'license') {
                    $this->setValuesByArrayWithEmptyString($driverData, [
                        'license_is_russian' => 'isRussian',
                        'license_series' => 'series',
                        'license_number' => 'number',
                    ], $document['document']);
                    if (isset($document['document']['dateIssue']) && $document['document']['dateIssue']) {
                        $driverData['license_date'] = Carbon::createFromFormat('Y-m-d', $document['document']['dateIssue']);
                    }
                }
            }

        }

        if (isset($attributes['drivingLicenseIssueDateOriginal']) && $attributes['drivingLicenseIssueDateOriginal']) {
            $driverData['exp_start_date'] = Carbon::createFromFormat('Y-m-d', $attributes['drivingLicenseIssueDateOriginal']);
        }
    }

}
