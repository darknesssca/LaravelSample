<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\Services\SourceAcquisitionServiceContract;
use App\Contracts\Repositories\SourceAcquisitionRepositoryContract;
use App\Exceptions\GuidesNotFoundException;

class SourceAcquisitionService implements SourceAcquisitionServiceContract
{
    protected $sourceAcquisitionRepository;

    public function __construct(
        SourceAcquisitionRepositoryContract $sourceAcquisitionRepository
    )
    {
        $this->sourceAcquisitionRepository = $sourceAcquisitionRepository;
    }

    public function getSourceAcquisitionsList()
    {
        $data = $this->sourceAcquisitionRepository->getSourceAcquisitionsList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->jsonSerialize();
    }

    public function getCompanySourceAcquisitions($id, $companyId)
    {
        $data = $this->sourceAcquisitionRepository->getCompanySourceAcquisitions($id, $companyId);

        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_acquisition_code;
    }
}
