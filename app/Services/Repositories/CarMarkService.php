<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CarMarkRepositoryContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Exceptions\GuidesNotFoundException;

class CarMarkService implements CarMarkServiceContract
{
    protected $carMarkRepository;

    public function __construct(CarMarkRepositoryContract $carMarkRepository)
    {
        $this->carMarkRepository = $carMarkRepository;
    }

    public function getMarkList()
    {
        $data = $this->carMarkRepository->getMarkList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->jsonSerialize();
    }

    public function getCarMarkName($id)
    {
        $data = $this->carMarkRepository->getCarMarkById($id);

        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->name;
    }

    public function getCompanyMark($id, $companyId)
    {
        $data = $this->carMarkRepository->getCompanyMark($id, $companyId);

        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->codes->first()->reference_mark_code;
    }
}
