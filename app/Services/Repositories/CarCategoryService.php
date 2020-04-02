<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CarCategoryRepositoryContract;
use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class CarCategoryService implements CarCategoryServiceContract
{
    use CacheTrait;

    protected $carCategoryRepository;

    public function __construct(
        CarCategoryRepositoryContract $carCategoryRepository
    )
    {
        $this->carCategoryRepository = $carCategoryRepository;
    }

    public function getCategoryList()
    {
        $tag = $this->getGuidesCategoriesTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->carCategoryRepository->getCategoryList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }
}
