<?php


namespace App\Exceptions\Qiwi;


use Benfin\Requests\Exceptions\AbstractException;

class PayoutInsufficientFundsException extends AbstractException
{
    const exceptionName = "Недостаточно средств: недостаточно денежных средств на балансе Участника";
    const exceptionCode = 400;
}
