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


    public function getCompanyCategory($categoryId, $isUsedWithTrailer, $companyCode)
    {
        $tag = $this->getGuidesCategoriesTag();
        $key = $this->getCacheKey($tag, $categoryId);
        $category = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($categoryId) {
            return $this->carCategoryRepository->getCategoryById($categoryId);
        });
        if (!$category) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $data = $this->carCategoryRepository->getCompanyCategory($category->code, $companyCode);
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        if (gettype($data) == 'array') {
            if ($isUsedWithTrailer) {
                if (isset($data['trailer'])) {
                    return $data['trailer'];
                } elseif (isset($data['default'])) {
                    return $data['default'];
                } else {
                    throw new GuidesNotFoundException('Не найдены данные в справочнике');
                }
            } else {
                if (isset($data['default'])) {
                    return $data['default'];
                } else {
                    throw new GuidesNotFoundException('Не найдены данные в справочнике');
                }
            }
        }
        return $data;
    }
}