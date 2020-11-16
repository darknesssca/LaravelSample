<?php


namespace App\Contracts\Repositories\Services;


interface KaskoTariffServiceContract
{
    public function getList($fields);

    public function getActiveTariffs();

    public function getById($id);

    public function update($id, $data);

    public function getTariffsList($fields);
}
