<?php


namespace App\Contracts\Repositories\Services;


interface IntermediateDataServiceContract
{
    public function find($token);

    public function getToken($token, $force = false);

    public function update($token, $data);

    public function create($data);

    public function getByData(string $data);
}
