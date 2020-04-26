<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\PolicyTypeRepositoryContract;
use App\Contracts\Repositories\Services\PolicyTypeServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class PolicyTypeService implements PolicyTypeServiceContract
{
    use CacheTrait;

    protected $policyTypeRepository;

    public function __construct(
        PolicyTypeRepositoryContract $policyTypeRepository
    )
    {
        $this->policyTypeRepository = $policyTypeRepository;
    }

    public function getByCode($code)
    {
        $data = $this->policyTypeRepository->getByCode($code);
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data;
    }

}
