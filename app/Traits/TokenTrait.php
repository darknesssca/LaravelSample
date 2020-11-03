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
            return $this->intermediateDataService->create([
                'token' => $token,
                'data' => json_encode($data)
            ]);
        } catch (\Exception $exception) {
            $try++;
            if ($try > 5) {
                throw new TokenException('Не удалось создать токен за ' . $try . ' попыток: '.$exception->getMessage());
            }
            return self::createToken($data, $try);
        }
    }

    protected function getToken($token, $force = false)
    {
        $object = $this->intermediateDataService->getToken($token, $force);
        if (!$object) {
            throw new TokenException('Не найден токен');
        }
        return $object;
    }

    protected function getTokenData($token, $force = false)
    {
        $object = $this->getToken($token, $force);
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

    protected function getPrevTokenDataByCompany($token, $companyCode)
    {
        $data = $this->getTokenData($token);
        if (!isset($data['prevData'][$companyCode]) || !$data['prevData'][$companyCode]) {
            return null;
        }
        return $data['prevData'][$companyCode];
    }

    protected function pushForm(&$attributes)
    {
        $form = $this->getTokenForm($attributes['token']);
        $attributes = array_merge($attributes, $form);
    }

    protected function destroyToken($token)
    {
        $this->intermediateDataService->delete($token);
    }

    protected function getByData($data)
    {
        $object = $this->intermediateDataService->getByData($data);
        if (!$object) {
            throw new TokenException('Данные не найдены');
        }
        return $object;
    }
}
