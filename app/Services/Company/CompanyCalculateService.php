<?php


namespace App\Services\Company;

use App\Models\InsuranceCompany;

abstract class CompanyCalculateService implements CompanyCalculateServiceInterface
{

    abstract public function run(InsuranceCompany $company, $attributes): array;
    abstract public function map(): array;

    public function addRule(&$rule, $parameter = null, $value = null)
    {
        if (strlen($rule)) {
            $rule .= '|';
        }
        if ($parameter) {
            $rule .= $parameter;
        }
        if ($value) {
            if ($parameter) {
                $rule .= ':';
            }
            switch (gettype($value)) {
                case 'string':
                    $rule .= $value;
                    break;
                case 'array':
                    $rule .= array_shift($value);
                    $rule .= implode(',', $value);
                    break;
            }
        }
    }

    public function getRules($fields, $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $field => $settings) {
            $rule = '';
            foreach ($settings as $parameter => $value) {
                switch ($parameter) {
                    case 'required':
                        if ($value) {
                            $this->addRule($rule, $parameter);
                        } else {
                            continue;
                        }
                        break;
                    case 'required_if':
                        $array = $value['value'];
                        array_unshift($array, $value['field']);
                        $this->addRule($rule, $parameter, $array);
                        break;
                    case 'type':
                        if ($value == 'object') {
                            $this->addRule($rule, null, 'array');
                            $rules = array_merge($rules, self::getRules($settings['array'], $prefix.$field . '.'));
                        } elseif ($value == 'array') {
                            $this->addRule($rule, null, 'array');
                            $rules = array_merge($rules, self::getRules($settings['array'], $prefix.$field . '.*.'));
                        } else {
                            $this->addRule($rule, null, $value);
                        }
                        break;
                    case 'format':
                        $this->addRule($rule, $parameter, $value);
                        break;
                    case 'in':
                        $this->addRule($rule, $parameter, implode(',', $value));
                        break;
                    case '':
                        break;
                }
            }
            $rules[$prefix . $field] = $rule;
        }
        return $rules;
    }

    public function prepareData()
    {
        $fields = $this->map();
    }

    public function validationRules(): array
    {
        return $this->getRules($this->map());
    }

    public function validationMessages(): array
    {
        return [];
    }
}
