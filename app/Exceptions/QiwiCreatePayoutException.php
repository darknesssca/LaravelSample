<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class QiwiCreatePayoutException extends AbstractException
{
    const exceptionName = "Не удалось зарегистрировать оплату. Попробуйте позже";
    const exceptionCode = 500;
}
