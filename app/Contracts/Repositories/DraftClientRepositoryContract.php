<?php

namespace App\Contracts\Repositories;


interface DraftClientRepositoryContract
{
    public function create($attributes);
    public function update($id, $attributes);
    public function delete($id);
}
