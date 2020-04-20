<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class TaxStatusNotServiceException extends AbstractException
{
    const exceptionName = "Данный налоговый статус не обслуживается";
    const exceptionCode = 400;
}
