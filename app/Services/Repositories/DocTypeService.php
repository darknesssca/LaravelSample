<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\DocTypeRepositoryContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
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

    public function getCompanyDocTypeByCode($code, $companyId)
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code, $companyId){
            return $this->docTypeRepository->getCompanyDocTypeByCode($code, $companyId);
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_doctype_code;
    }
}
