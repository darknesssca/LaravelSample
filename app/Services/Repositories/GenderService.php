<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\GenderRepositoryContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Exceptions\GuidesNotFoundException;

class GenderService implements GenderServiceContract
{
    protected $genderRepository;

    public function __construct(
        GenderRepositoryContract $genderRepository
    )
    {
        $this->genderRepository = $genderRepository;
    }

    public function getGendersList()
    {
        $data = $this->genderRepository->getGendersList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->jsonSerialize();
    }

    public function getCompanyGender($id, $companyId)
    {
        $data = $this->genderRepository->getCompanyGender($id, $companyId);

        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->codes->first()->reference_gender_code;
    }
}
