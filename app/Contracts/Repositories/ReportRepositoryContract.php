<?php


namespace App\Contracts\Repositories;


use App\Models\Report;

interface ReportRepositoryContract
{
    public function getById(int $id): Report;

    public function getAll(array $filter);

    public function getByCreatorId(int $creator_id, array $filter);

    public function create(array $fields): Report;
}
