<?php


namespace App\Contracts\Repositories;


interface PolicyTypeRepositoryContract
{
    public function getByCode($code);
}
