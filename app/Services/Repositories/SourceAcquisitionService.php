<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\Services\SourceAcquisitionServiceContract;
use App\Contracts\Repositories\SourceAcquisitionRepositoryContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class SourceAcquisitionService implements SourceAcquisitionServiceContract
{
    use CacheTrait;

    protected $sourceAcquisitionRepository;

    public function __construct(
        SourceAcquisitionRepositoryContract $sourceAcquisitionRepository
    )
    {
        $this->sourceAcquisitionRepository = $sourceAcquisitionRepository;
    }

    public function getSourceAcquisitionsList()
    {
        $tag = $this->getGuidesSourceAcquisitionsTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->sourceAcquisitionRepository->getSourceAcquisitionsList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getCompanySourceAcquisitions($id, $companyId)
    {
        $tag = $this->getGuidesSourceAcquisitionsTag();
        $key = $this->getCacheKey($tag, $id, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($id, $companyId){
            return $this->sourceAcquisitionRepository->getCompanySourceAcquisitions($id, $companyId);
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_acquisition_code;
    }
}
