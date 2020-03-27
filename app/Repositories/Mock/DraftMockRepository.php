<?php

namespace App\Repositories\Mock;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Models\Draft;
use Illuminate\Database\Eloquent\Model;

class DraftMockRepository implements DraftRepositoryContract
{

    public function getById(int $id): Model
    {
        $draft = new Draft();
        $draft->fill(
            [
                'id' => 1,
                'agent_id' => 3
            ]
        );

        return $draft;
    }
}
