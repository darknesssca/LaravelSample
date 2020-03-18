<?php


namespace App\Services\Company\Tinkoff;


use App\Services\Company\GuidesSourceInterface;

class TinkoffGuidesService extends TinkoffService implements GuidesSourceInterface
{
    public function __construct()
    {
        parent::__construct();
    }


    public function updateGuides(): bool
    {
        // лист 2.11 в файле
    }
}
