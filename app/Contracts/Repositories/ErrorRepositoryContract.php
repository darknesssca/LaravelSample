<?php


namespace App\Contracts\Repositories;


interface ErrorRepositoryContract
{
    public function getReportErrorByCode(int $code);
}
