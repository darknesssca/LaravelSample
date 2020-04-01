<?php


namespace App\Contracts\Repositories;


interface IntermediateDataRepositoryContract
{

    /**
     * Получает промежуточные данные из БД
     *
     * @param $token - токен
     * @return mixed
     */
    public function find($token);

    /**
     * Обновляет промежуточные данные в БД
     *
     * @param $token - токен
     * @param array $data - массив данных
     * @return mixed
     */
    public function update($token, $data);

    /**
     * @param $data
     * @return mixed
     */
    public function create($data);
}
