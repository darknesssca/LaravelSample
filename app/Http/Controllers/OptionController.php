<?php


namespace App\Http\Controllers;


use App\Models\Option;
use App\Repositories\OptionRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    private $optionRepository;

    public function __construct()
    {
        $this->optionRepository = new OptionRepository();
    }

    public function index()
    {
        try {
            $options_info = $this->optionRepository->getAll()->toArray();
            return response()->json($options_info, 200, [], JSON_UNESCAPED_UNICODE);
        } catch (ModelNotFoundException $exception) {
            return $this->error($exception->getMessage(), 404);
        } catch
        (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            if (intval($id) > 0){
                $option_info = $this->optionRepository->getById($id)->toArray();
            } else {
                $option_info = $this->optionRepository->getByCode($id)->toArray();
            }

            return response()->json($option_info, 200, [], JSON_UNESCAPED_UNICODE);
        } catch (ModelNotFoundException $exception) {
            return $this->error($exception->getMessage(), 404);
        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $validation_result = $this->validate(
                $request,
                $this->getValidationRules(),
                $this->getValidationRulesMessages()
            );

            $option = $this->optionRepository->getById($id);
            $option->forceFill($validation_result);
            $option->save();
            return $this->success('Настройка успешно обновлена');
        } catch (ModelNotFoundException $exception) {
            return $this->error($exception->getMessage(), 404);
        } catch
        (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    public function create(Request $request)
    {
        try {
            $validation_result = $this->validate(
                $request,
                $this->getValidationRules(),
                $this->getValidationRulesMessages()
            );

            $option = new Option;
            $option->forceFill($validation_result);
            $option->save();
            return $this->success('Настройка успешно создана');
        } catch (ModelNotFoundException $exception) {
            return $this->error($exception->getMessage(), 404);
        } catch
        (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    public function delete($id)
    {
        try {
            $option = $this->optionRepository->getById($id);
            $option->forceDelete();
            return $this->success('Настройка успешно удалена');
        } catch (ModelNotFoundException $exception) {
            return $this->error($exception->getMessage(), 404);
        } catch
        (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }


    //Вспомогательные методы

    private function getValidationRules()
    {
        return [
            'code' => 'required',
            'name' => 'required',
            'value' => 'required',
        ];
    }

    private function getValidationRulesMessages()
    {
        return [
            'code.required' => 'Поле Код является обязательным',
            'name.required' => 'Поле Название является обязательным',
            'value.required' => 'Поле Значение является обязательным',
        ];
    }
}
