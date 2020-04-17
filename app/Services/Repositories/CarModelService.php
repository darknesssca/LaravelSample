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

    public function getCompanyModelByName($mark_id, $categoryId, $name, $companyId)
    {
        $data = $this->carModelRepository->getCompanyModelByName($mark_id, $name, $companyId);

        if (!$data) {
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
                    $categoryData = $this->carCategoryRepository->getCategoryById($categoryId);
                    if (!$categoryData) {
                        throw new GuidesNotFoundException('Не найдены данные в справочнике');
                    }
                    $result['category'] = $categoryData->name;
                }
            } else {
                $result['otherModel'] = '';
                $categoryData = $this->carCategoryRepository->getCategoryById($categoryId);
                if (!$categoryData) {
                    throw new GuidesNotFoundException('Не найдены данные в справочнике');
                }
                $result['category'] = $categoryData->name;
            }

            return $result;
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
