<?php


namespace App\Repositories;


use App\Models\Option;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class OptionRepository
{

    /**
     * @param integer $id
     * @return Option
     */
    public function getById($id)
    {
        $id = intval($id);

        if ($id <= 0){
            throw new InvalidArgumentException('Указан не корректный id');
        }

        $option = Option::find($id);

        if (empty($option)){
            throw new ModelNotFoundException(sprintf('Не найдены настройки с id %s', $id));
        }

        return $option;
    }

    /**
     * @return Option[]|Collection
     */
    public function getAll()
    {
        $option = Option::all();

        if (empty($option)){
            throw new ModelNotFoundException('Настройки не найдены');
        }

        return $option;
    }
}
