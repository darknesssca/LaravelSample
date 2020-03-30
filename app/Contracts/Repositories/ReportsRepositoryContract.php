<?php


namespace App\Contracts\Repositories;


use Illuminate\Database\Eloquent\Model;

interface ReportsRepositoryContract
{
    public function getById(int $id): Model;
}
