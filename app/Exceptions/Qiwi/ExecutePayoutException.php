<?php


namespace App\Exceptions\Qiwi;


use Benfin\Requests\Exceptions\AbstractException;

class ExecutePayoutException extends AbstractException
{
    const exceptionName = "Не удалось исполнить оплату. Попробуйте позже";
    const exceptionCode = 500;
}
