<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\DocTypeRepositoryContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Exceptions\GuidesNotFoundException;
use App\Traits\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class DocTypeService implements DocTypeServiceContract
{
    use CacheTrait;

    protected $docTypeRepository;

    public function __construct(
        DocTypeRepositoryContract $docTypeRepository
    )
    {
        $this->docTypeRepository = $docTypeRepository;
    }

    public function getDocTypesList()
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->docTypeRepository->getDocTypesList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }
}
