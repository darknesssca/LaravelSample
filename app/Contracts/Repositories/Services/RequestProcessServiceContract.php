<?php


namespace App\Contracts\Repositories\Services;


interface RequestProcessServiceContract
{
    public function updateCheckCount($token, $companyCode);
    public function getPool($state, $limit);
    public function find($token, $companyCode);
    public function delete($token, $companyCode);
    public function update($token, $companyCode, $data);
    public function create($data);
}
