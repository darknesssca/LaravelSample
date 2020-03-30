<?php


namespace App\Contracts\Repositories;


interface RequestProcessRepositoryContract extends AbstractRepositoryInterface
{
    /**
     * Получение пула полисов, находящихся на этапе процессинга
     *
     * @param $state - этап процессинга
     * @param $count - ограничение на выборку
     * @return mixed
     */
    public function getPool($state, $count);
}
