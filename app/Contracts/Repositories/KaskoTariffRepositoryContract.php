<?php


namespace App\Contracts\Repositories;


interface KaskoTariffRepositoryContract
{
    public function getList($fields);

    public function getActiveTariffs();

    public function getById($id);

    public function update($id, $data);
}
