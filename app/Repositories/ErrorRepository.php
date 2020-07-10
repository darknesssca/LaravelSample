<?php


namespace App\Repositories;


use App\Contracts\Repositories\ErrorRepositoryContract;

class ErrorRepository implements ErrorRepositoryContract
{
    public function getReportErrorByCode(int $code)
    {
        $errors = [
            1001 => 'Уважаемый Пользователь, проверьте корректность платежных данных (ИНН, номер карты) в Профайле и предоставьте разрешение Киви-банку на обработку ИНН и регистрацию дохода в Мой налог.',
            1002 => 'Уважаемый Пользователь, Вы не предоставили Киви-банку разрешение на обработку ИНН и регистрацию дохода. Повторно ознакомьтесь с инструкцией в Профайле и предоставьте разрешение в Мой налог.',
        ];
        
        return $errors[$code] ?? '';
    }
}
