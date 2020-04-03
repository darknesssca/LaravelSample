<?php

namespace App\Contracts\Services;

interface PolicyServiceContract
{
    public function getList(array $filter = []);

    public function statistic(array $filter = []);

    public function create(array $fields, int $draftId = null);

    public function createPolicyFromCustomData($company, $attributes);
}
