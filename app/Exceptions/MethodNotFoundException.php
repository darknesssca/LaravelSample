<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class MethodNotFoundException extends AbstractException
{
    const exceptionName = "method not found";
    const exceptionCode = 404;
}
