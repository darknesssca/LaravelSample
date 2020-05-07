<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class NotAvailableCommissionException extends AbstractException
{
    const exceptionName = "commission not found";
    const exceptionCode = 400;

}
