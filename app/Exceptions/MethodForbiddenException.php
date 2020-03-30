<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class MethodForbiddenException extends AbstractException
{
    const exceptionName = "forbidden method";
    const exceptionCode = 403;
}
