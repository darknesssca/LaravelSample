<?php

namespace App\Contracts\Services;

interface PolicyServiceContract
{
    public function getList();

    public function getById($id);

    public function create();
}
