<?php

namespace App\Traits;

trait UserTrait
{

    protected function prepareUserRegistrationData($formData)
    {
        foreach ($formData['subjects'] as $subject) {
            if ($subject['id'] == $formData['policy']['insurantId']) {
                $insurer = $subject['fields'];
                break;
            }
        }
        $user['first_name'] = $insurer['firstName'];
        $user['last_name'] = $insurer['lastName'];
        $user['patronymic'] = $insurer['middleName'];
        $user['email'] = $insurer['email'];
        $user['phone'] = $insurer['phone'];
        $user['offer_accepted'] = true;
        $user['personal_data_agree'] = true;
        $user['phone_confirm'] = $formData['phone_confirm'];
        $user['city'] = \GuzzleHttp\json_decode($insurer['addresses'])['city'];
        $user['city_kladr'] = \GuzzleHttp\json_decode($insurer['addresses'])['cityKladr'];
        $user['password'] = $this->generatePassword();
        $user['confirm_password'] = $user['password'];

        return $user;
    }

    private function generatePassword()
    {
        $checkPass = false;
        $allowedCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.,:;?!*+%-<>@[]{}/_{}$#';
        $pass = '';
        $length = 8;
        $max = mb_strlen($allowedCharacters, '8bit') - 1;
        while(!$checkPass) {
            for ($i = 0; $i < $length; ++$i) {
                $pass .= $allowedCharacters[random_int(0, $max)];
            }
            $checkPass = $this->checkPassword($pass);
        }
        return $pass;
    }

    private function checkPassword($pass)
    {
        if (!preg_match("/[a-z]+/g", $pass))
            return false;
        if (!preg_match("/[A-Z]+/g", $pass))
            return false;
        if (!preg_match("/[0-9]+/g", $pass))
            return false;
        if (!preg_match("/[\.\,\:\;\?\!\*\+\%\-\<\>\@\[\]\{\}\/\\\_\{\}\$\#]+/g", $pass))
            return false;
        return true;
    }
}
