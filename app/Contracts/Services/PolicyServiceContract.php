<?php

namespace App\Contracts\Services;

interface PolicyServiceContract
{
    public function getList(array $filter = [], string $sort = 'id', string $order = 'asc', int $page = 1, int $perPage = 20, string $search = null);

    public function getById(int $id);

    public function statistic(array $filter = []);

    public function create(array $fields, int $draftId = null);

    public function createPolicyFromCustomData($company, $attributes);

    public function update($id, $data);

    public function getNotPaidPolicyByPaymentNumber($policyNumber);

    public function getNotPaidPolicies($limit, $daysToSubtract = 2);

    public function searchOldPolicyByPolicyNumber($companyId, $attributes);
}
