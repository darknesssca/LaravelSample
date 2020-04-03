<?php


namespace App\Contracts\Repositories;


use Illuminate\Database\Eloquent\Model;

interface ReportRepositoryContract
{
    public function getById(int $id): Model;
}
