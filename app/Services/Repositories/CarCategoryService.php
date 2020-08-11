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
        $data = $this->carCategoryRepository->getCategoryList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->jsonSerialize();
    }


    public function getCompanyCategory($categoryId, $isUsedWithTrailer, $companyCode)
    {
        $category = $this->getCategoryById($categoryId);

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

    public function getCategoryById($categoryId) {
        return $this->carCategoryRepository->getCategoryById($categoryId);
    }
}
