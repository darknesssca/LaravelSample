<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CarCategoryRepositoryContract;
use App\Contracts\Repositories\CarModelRepositoryContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Exceptions\GuidesNotFoundException;

class CarModelService implements CarModelServiceContract
{
    protected $carModelRepository;
    protected $carCategoryRepository;

    public function __construct(
        CarModelRepositoryContract $carModelRepository,
        CarCategoryRepositoryContract $carCategoryRepository
    )
    {
        $this->carModelRepository = $carModelRepository;
        $this->carCategoryRepository = $carCategoryRepository;
    }

    public function getModelListByMarkId($mark_id)
    {
        $data = $this->carModelRepository->getModelListByMarkId($mark_id);

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getModelList()
    {
        $data = $this->carModelRepository->getModelList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getCompanyModel($mark_id, $id, $companyId)
    {
        $data = $this->carModelRepository->getCompanyModel($mark_id, $id, $companyId);

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

    /**
     * @param $mark_id
     * @param $categoryId
     * @param $name
     * @param $companyId
     * @param bool $needOther
     * @return array|null[]|string[]
     * @throws GuidesNotFoundException
     */
    public function getCompanyModelByName($mark_id, $categoryId, $name, $companyId, $needOther = true)
    {
        $data = $this->carModelRepository->getCompanyModelByName($mark_id, $categoryId, $name, $companyId);

        if ((!$data && $needOther) || ($data && !$data->codes->count() && $needOther)) {
            $result = [
                'model' => null,
            ];
            $otherModel = $this->carModelRepository->getCompanyOtherModel($mark_id, $categoryId, $companyId);
            if ($otherModel) {
                $codes = $otherModel->codes;
                if (!$codes || !$codes->count()) {
                    throw new GuidesNotFoundException('Не найдены данные в справочнике');
                }
                $result['otherModel'] = $otherModel->codes->first()->reference_model_code;
                $category = $otherModel->category;
                if ($category) {
                    $result['category'] = $otherModel->category->name;
                } else {
                    throw new GuidesNotFoundException('Выбранная страховая компания не позволяет страховать указанную модель автомобиля.');
                }
            } else {
                throw new GuidesNotFoundException('В справочнике автомобилей не найдено совпадений для выбранной страховой компании');
            }

            return $result;
        } else if ((!$data && !$needOther) || ($data && !$data->codes->count() && !$needOther)) {
            $category = $this->carCategoryRepository->getCategoryById($categoryId);
            if (!$category) {
                throw new GuidesNotFoundException('Не найдены данные в справочнике');
            }
            return [
                'model' => null,
                'otherModel' => 'Прочие',
                'category' => $category->name
            ];
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
            'category' => $data->category->name
        ];
    }
}
