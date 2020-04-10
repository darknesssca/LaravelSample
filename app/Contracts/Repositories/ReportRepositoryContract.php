<?php


namespace App\Contracts\Repositories;


use App\Models\Report;

interface ReportRepositoryContract
{
    public function getById(int $id): Report;

    public function getAll(): Report;

    public function getByCreatorId(int $creator_id): Report;

    public function create(array $fields): Report;
}
