<?php

namespace App\Contracts\Services;

interface PolicyServiceContract
{
    public function getList(array $filter = []);

    public function getById($id);

    public function create(array $fields, int $draftId = null);
}
