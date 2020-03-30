<?php


namespace App\Traits;


use App\Exceptions\TokenException;
use Illuminate\Support\Str;

trait TokenTrait
{
    protected function createToken($data, $try = 0)
    {
        $token = Str::random(32);
        try {
            return $this->intermediateDataRepository->create([
                'token' => $token,
                'data' => json_encode($data)
            ]);
        } catch (\Exception $exception) {
            $try++;
            if ($try > 5) {
                throw new TokenException('Не удалось создать токенза ' . $try . ' попыток: '.$exception->getMessage());
            }
            return self::createToken($data, $try);
        }
    }

    protected function getToken($token)
    {
        $data = $this->intermediateDataRepository->getToken($token);
        if (!$data) {
            throw new TokenException('Не найден токен');
        }
        if (isset($data['data'])) {
            $data['data'] = json_decode($data['data'], true);
        }
        return json_decode($data['data'], true);
    }

    protected function getTokenData($token, $force = false)
    {
        $object = $this->intermediateDataRepository->getToken($token, $force);
        if (!$object) {
            throw new TokenException('Не найден токен');
        }
        if (!isset($object['data'])) {
            throw new TokenException('Не найдены данные процесса в токене');
        }
        return json_decode($object['data'], true);
    }

    protected function getTokenStatus($token)
    {
        $data = $this->getTokenData($token);
        if (!isset($data['status'])) {
            throw new TokenException('Не найдены данные о статусе в токене');
        }
        return $data['status'];
    }

    protected function getTokenForm($token)
    {
        $data = $this->getTokenData($token);
        if (!isset($data['form'])) {
            throw new TokenException('Не найдена форма в токене');
        }
        return $data['form'];
    }

    protected function getTokenDataByCompany($token, $companyCode, $force = false)
    {
        $data = $this->getTokenData($token, $force);
        if (!isset($data[$companyCode]) || !$data[$companyCode]) {
            throw new TokenException('Не найдены данные выбранной компании в токене');
        }
        return $data[$companyCode];
    }

    protected function pushForm(&$attributes)
    {
        $form = $this->getTokenForm($attributes['token']);
        $attributes = array_merge($attributes, $form);
    }
}
