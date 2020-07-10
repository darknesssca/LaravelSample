<?php


namespace App\Exceptions\Qiwi;


use Benfin\Requests\Exceptions\AbstractException;

class CreatePayoutException extends AbstractException
{
    const exceptionName = "Не удалось зарегистрировать оплату. Попробуйте позже";
    const exceptionCode = 500;
}
