<?php


namespace App\Contracts\Repositories;


interface IntermediateDataRepositoryContract extends AbstractRepositoryInterface
{
    /**
     * Получает промежуточные данные из БД
     *
     * @param $token - токен
     * @param $force - флаг, принудительный запрос в БД, даже если запись есть в локальном хранилище. По умолчанию false
     * @return mixed
     */
    public function getToken($token, $force);

    /**
     * Обновляет промежуточные данные в БД
     *
     * @param $token - токен
     * @param array $data - массив данных
     * @return mixed
     */
    public function update($token, $data);
}
