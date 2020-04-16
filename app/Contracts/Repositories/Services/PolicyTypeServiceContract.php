<?php


namespace App\Contracts\Repositories\Services;


interface PolicyTypeServiceContract
{
    public function getByCode($code);
}
