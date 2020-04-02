<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CarModelRepositoryContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
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

    public function getCompanyModel($mark_id, $id, $companyId)
    {
        $tag = $this->getGuidesModelsTag();
        $key = $this->getCacheKey($tag, $id, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($mark_id, $id, $companyId){
            return $this->carModelRepository->getCompanyModel($mark_id, $id, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $category = $data->category;
        if (!$category) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return [
            'model' => $data->codes->first()->reference_model_code,
            'category' => $data->category->code
        ];
    }

    public function getCompanyModelByName($mark_id, $name, $companyId)
    {
        $tag = $this->getGuidesModelsTag();
        $key = $this->getCacheKey($tag, $name, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($mark_id, $name, $companyId){
            return $this->carModelRepository->getCompanyModelByName($mark_id, $name, $companyId);
        });
        if (!$data) {
            return null;
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            return null;
        }
        $category = $data->category;
        if (!$category) {
            return null;
        }
        return [
            'model' => $data->codes->first()->reference_model_code,
            'category' => $data->category->name
        ];
    }
}
