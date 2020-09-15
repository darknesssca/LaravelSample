<?php


namespace App\Providers;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //Адрес обязательный для водителей с иностранным ВУ
        Validator::extend('driver_foreign_license', function ($attribute, $value, $parameters, $validator) {
            /** @var \Illuminate\Validation\Validator $validator */
            $subject = self::getSubject($value, $validator->attributes());
            if (empty($subject)) return false;
            $license = array_filter($subject['fields']['documents'], function ($item) use ($value) {
                return $item['document']['documentType'] == 'license';
            });
            $license = array_pop($license);
            if (empty($license)) return false;
            if ($license['document']['isRussian'])
                return true;
            return self::checkAddress($subject);
        }, 'Адрес обязательный для водителей с иностранным ВУ. Обязательные поля адреса: country, region, district, building, city или populatedCenter');

        //адрес обязательный для страхователя и для собственника
        Validator::extend('address_required', function ($attribute, $value, $parameters, $validator) {
            /** @var \Illuminate\Validation\Validator $validator */
            $subject = self::getSubject($value, $validator->attributes());
            if (empty($subject)) return false;
            return self::checkAddress($subject);
        }, 'Адрес обязательный для страхователя и для собственника. Обязательные поля адреса: country, region, district, building, city или populatedCenter');

        //место рождения обязательно для страхователя и для собственника
        Validator::extend('birthplace_required', function ($attribute, $value, $parameters, $validator) {
            /** @var \Illuminate\Validation\Validator $validator */
            $subject = self::getSubject($value, $validator->attributes());
            if (empty($subject)) return false;
            return !empty($subject['fields']['birthPlace']);
        }, 'Место рождения обязательно для страхователя и для собственника');

        //email обязательно для страхователя и для собственника
        Validator::extend('email_required', function ($attribute, $value, $parameters, $validator) {
            /** @var \Illuminate\Validation\Validator $validator */
            $subject = self::getSubject($value, $validator->attributes());
            if (empty($subject)) return false;
            return !empty($subject['fields']['email']);
        }, 'Email обязателен для страхователя и для собственника');

        //Recaptcha
        Validator::extend('recaptcha', function ($attribute, $value, $parameters, $validator) {
            $client = new Client;
            $response = $client->post('https://www.google.com/recaptcha/api/siteverify',
                [
                    'form_params' =>
                        [
                            'secret' => env('GOOGLE_RECAPTCHA_SECRET'),
                            'response' => $value
                        ]
                ]
            );

            $body = json_decode((string)$response->getBody());
            return $body->success;
        });
    }

    /**проверка корректности адреса у клиента
     * @param $subject
     * данные о клиенте
     * @return bool
     */
    private static function checkAddress($subject): bool
    {
        $address = array_pop($subject['fields']['addresses']);
        if (empty($address)) return false;
        return
            !empty($address['address']['country']) &&
            !empty($address['address']['region']) &&
            !empty($address['address']['district']) &&
            (!empty($address['address']['city']) || !empty($address['address']['populatedCenter'])) &&
            !empty($address['address']['building']);
    }

    /**получить клиента по id
     * @param int $id
     * id клиента
     * @param array $params
     * входящие параметры запроса
     * @return mixed
     */
    private static function getSubject(int $id, array $params)
    {
        $subject = array_filter($params['subjects'], function ($item) use ($id) {
            return $item['id'] == $id;
        });
        return array_pop($subject);
    }

    public function boot()
    {
    }
}
