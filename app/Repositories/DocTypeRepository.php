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
}
