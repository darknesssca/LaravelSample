<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Validator;

abstract class AbstractRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    protected $validator;

    abstract public function rules():array;

    public function validate()
    {
        $instance = $this->getValidatorInstance();
        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        } elseif (! $instance->passes()) {
            $this->failedValidation($instance);
        }
    }

    /**
     * Получение инстанса валидатора для текущего реквеста
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }
        $factory = app(Factory::class);
        if (method_exists($this, 'validator')) {
            $validator = $this->validator($factory);
        } else {
            $validator = $this->createDefaultValidator($factory);
        }
        $this->setValidator($validator);
        return $this->validator;
    }

    /**
     * Метод создает валидатор, используемый по умолчанию, если дочерний класс не имеет собственного валидатора
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(Factory $factory)
    {
        return $factory->make(
            $this->validationData(),
            $this->rules(),
            $this->messages()
        );
    }

    /**
     * метод возвращает данные, которые необходимо валидировать
     *
     * @return array
     */
    public function validationData():array
    {
        return $this->all();
    }

    /**
     * Метод сохраняет валидатор для дальнейшегго использования
     *
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Метод возвращает массив правил валидации
     *
     * @return array
     */
    public function validated():array
    {
        return $this->validator->validated();
    }

    /**
     * метод выбрасывает кастомное исключение для дальнейшей обработки
     *
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator->errors()->messages());
    }

    /**
     * Метод возвращает массив текстов ошибок
     *
     * @return array
     */
    public function messages():array
    {
        return [];
    }
}
