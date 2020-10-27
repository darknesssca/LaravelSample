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
        $user['city'] = $insurer['addresses'][0]['address']['city'];
        $user['city_kladr'] = $insurer['addresses'][0]['address']['cityKladr'];
        $user['password'] = $this->generatePassword();
        $user['confirm_password'] = $user['password'];
        $user['temp_user'] = true;

        if (!empty($formData['referral'])) {
            $user['referral_code'] = $formData['referral'];
        }

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
            for ($i = 0; $i < $length; $i++) {
                $pass .= $allowedCharacters[random_int(0, $max)];
            }
            $checkPass = $this->checkPassword($pass);
        }
        return $pass;
    }

    private function checkPassword($pass)
    {
        if (!preg_match_all("/[a-z]+/", $pass))
            return false;
        if (!preg_match_all("/[A-Z]+/", $pass))
            return false;
        if (!preg_match_all("/[0-9]+/", $pass))
            return false;
        if (!preg_match_all("/[\.\,\:\;\?\!\*\+\%\-\<\>\@\[\]\{\}\/\\\_\{\}\$\#]+/", $pass))
            return false;
        return true;
    }
}
