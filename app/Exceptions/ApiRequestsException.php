<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class ApiRequestsException extends AbstractException
{
    const exceptionName = "api request error";
    const exceptionCode = 500;
}
