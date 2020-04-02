<?php

namespace App\Contracts\Repositories;


interface DraftRepositoryContract
{
    public function getById(int $id, int $agentId);
}
