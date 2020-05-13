<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class PolicyDuplicateException extends AbstractException
{
    const exceptionName = "policy duplicate error";
    const exceptionCode = 500;
}
