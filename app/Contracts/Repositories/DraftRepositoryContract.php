<?php

namespace App\Contracts\Repositories;


interface DraftRepositoryContract
{
    public function getById(int $id, int $agentId);
    public function delete(int $id);
    public function getByFilter(int $agentId, array $attributes);
}
