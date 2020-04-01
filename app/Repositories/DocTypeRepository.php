<?php


namespace App\Repositories;


use App\Contracts\Repositories\DocTypeRepositoryContract;
use App\Models\DocType;

class DocTypeRepository implements DocTypeRepositoryContract
{
    public function getDocTypesList()
    {
        return DocType::select(["id", "code", "name"])->get();
    }

    public function getCompanyDocTypeByCode($code, $companyId)
    {
        return DocType::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            }
        ])
            ->where('code', $code)->first();
    }
}
