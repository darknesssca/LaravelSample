<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CarModelRepositoryContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Exceptions\GuidesNotFoundException;
use App\Traits\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class CarModelService implements CarModelServiceContract
{
    use CacheTrait;

    protected $carModelRepository;

    public function __construct(
        CarModelRepositoryContract $carModelRepository
    )
    {
        $this->carModelRepository = $carModelRepository;
    }

    public function getModelListByMarkId($mark_id)
    {
        $tag = $this->getGuidesModelsTag();
        $key = $this->getCacheKey($tag, $mark_id);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($mark_id) {
            return $this->carModelRepository->getModelListByMarkId($mark_id);
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getModelList()
    {
        $tag = $this->getGuidesModelsTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->carModelRepository->getModelList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }
}
