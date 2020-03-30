<?php


namespace App\Contracts\Repositories\Services;


interface RequestProcessServiceContract
{
    public function updateCheckCount($token);
    public function getPool($state, $limit);
    public function find($token);
    public function delete($token);
    public function update($token, $data);
    public function create($data);
}
