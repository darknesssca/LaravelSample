<?php


namespace App\Exceptions\Qiwi;


use Benfin\Requests\Exceptions\AbstractException;

class PayoutAlreadyExistException extends AbstractException
{
    const exceptionName = "Выплата с таким id уже существует";
    const exceptionCode = 400;
}
