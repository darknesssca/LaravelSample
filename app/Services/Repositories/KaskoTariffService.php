<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\KaskoTariffRepositoryContract;
use App\Contracts\Repositories\Services\KaskoTariffServiceContract;
use App\Exceptions\GuidesNotFoundException;
use App\Models\KaskoTariff;
use Illuminate\Database\Eloquent\Collection;

class KaskoTariffService implements KaskoTariffServiceContract
{
    protected $kaskoTariffRepository;

    public function __construct(KaskoTariffRepositoryContract $kaskoTariffRepository)
    {
        $this->kaskoTariffRepository = $kaskoTariffRepository;
    }

    public function getList()
    {
        /** @var Collection $tariffs */
        $tariffs = $this->kaskoTariffRepository->getList();

        if ($tariffs->isEmpty()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $tariffs->toArray();
    }

    public function getActiveTariffs()
    {
        /** @var Collection $tariffs */
        $tariffs = $this->kaskoTariffRepository->getActiveTariffs();

        if ($tariffs->isEmpty()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $tariffs->toArray();
    }

    public function getById($id)
    {
        /** @var KaskoTariff $tariff */
        $tariff = $this->kaskoTariffRepository->getById($id);

        if (empty($tariff)) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $tariff->toArray();
    }

    public function update($id, $data)
    {
        return $this->kaskoTariffRepository->update($id, $data);
    }

    public function getTariffsList($fields)
    {
        if (!empty($fields['active'])) {
            return $this->getActiveTariffs();
        } else {
            return $this->getList();
        }
    }
}
