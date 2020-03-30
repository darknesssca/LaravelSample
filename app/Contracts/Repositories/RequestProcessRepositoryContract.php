<?php


namespace App\Contracts\Repositories;


interface RequestProcessRepositoryContract
{
    /**
     * Получение пула полисов, находящихся на этапе процессинга
     *
     * @param $state - этап процессинга
     * @param $count - ограничение на выборку
     * @return mixed
     */
    public function getPool($state, $count);
    public function update($token, $data);
    public function find($token);
    public function create($data);
    public function delete($token);
}
