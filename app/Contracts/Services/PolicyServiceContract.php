<?php

namespace App\Contracts\Services;

interface PolicyServiceContract
{
    public function getList(array $filter = []);

    public function create(array $fields, int $draftId = null);
}
