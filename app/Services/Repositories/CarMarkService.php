<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CarMarkRepositoryContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Exceptions\GuidesNotFoundException;
use App\Traits\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class CarMarkService implements CarMarkServiceContract
{
    use CacheTrait;

    protected $carMarkRepository;

    public function __construct(
        CarMarkRepositoryContract $carMarkRepository
    )
    {
        $this->carMarkRepository = $carMarkRepository;
    }

    public function getMarkList()
    {
        $tag = $this->getGuidesMarksTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->carMarkRepository->getMarkList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getCompanyMark($id, $companyId)
    {
        $tag = $this->getGuidesMarksTag();
        $key = $this->getCacheKey($tag, $id, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($id, $companyId){
            return $this->carMarkRepository->getCompanyMark($id, $companyId);
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_mark_code;
    }
}
